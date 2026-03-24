<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProcessPaymentRequest;
use Illuminate\Http\Request;
use App\Models\Payment;
use App\Models\Reservation;
use App\Models\TarjetaSimulada;
use App\Models\User;
use App\Models\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class PaymentController extends Controller
{
    public function index(Request $request)
    {
        if (! $this->isAdmin(auth()->user())) {
            return redirect()->route('pagos.mine');
        }

        $q = Payment::query()->with(['reservation','tarjeta.assignedUser']);
        $payments = $q->orderByDesc('id')->paginate(15);

        if ($request->wantsJson()) return response()->json($payments);

        $reservaciones = Reservation::orderByDesc('id')->get();
        $tarjetas = TarjetaSimulada::orderByDesc('id')->get();

        return view('pagos.index', compact('payments','reservaciones','tarjetas'));
    }

    public function myPayments(Request $request)
    {
        $user = auth()->user();
        if (! $user) {
            abort(403);
        }

        $query = Payment::query()->with(['reservation.user','reservation.propiedad','tarjeta.assignedUser']);
        $this->applyRolePaymentFilter($query, $user);
        $payments = $query->orderByDesc('id')->paginate(20);

        if ($request->wantsJson()) {
            return response()->json($payments);
        }

        return view('pagos.mine', [
            'payments' => $payments,
            'currentUser' => $user,
            'isAdmin' => $this->isAdmin($user),
        ]);
    }

    public function myCodes(Request $request)
    {
        $user = auth()->user();
        if (! $user) {
            abort(403);
        }

        $query = Payment::query()
            ->with(['reservation.user','reservation.propiedad'])
            ->where('estado', 'pagado')
            ->whereNotNull('codigo_qr');
        $this->applyRolePaymentFilter($query, $user);
        $codes = $query->orderByDesc('id')->paginate(20);

        if ($request->wantsJson()) {
            return response()->json($codes);
        }

        return view('pagos.codes', [
            'codes' => $codes,
            'currentUser' => $user,
            'isAdmin' => $this->isAdmin($user),
        ]);
    }

    public function showCode(Request $request, $id)
    {
        $user = auth()->user();
        if (! $user) {
            abort(403);
        }

        $query = Payment::query()
            ->with(['reservation.user','reservation.propiedad'])
            ->where('estado', 'pagado')
            ->whereNotNull('codigo_qr');
        $this->applyRolePaymentFilter($query, $user);
        $payment = $query->where('id', $id)->first();
        if (! $payment) {
            abort(404);
        }

        if ($request->wantsJson()) {
            return response()->json($payment);
        }

        return view('pagos.code_show', [
            'payment' => $payment,
            'currentUser' => $user,
            'isAdmin' => $this->isAdmin($user),
        ]);
    }

    public function store(Request $request)
    {
        if (! $this->isAdmin(auth()->user())) {
            abort(403);
        }

        $request->validate([
            'reservacion_id' => 'nullable|exists:reservaciones,id',
            'tarjeta_id' => 'nullable|exists:tarjetas_simuladas,id',
            'monto' => 'required|numeric',
            'metodo_pago' => 'required|string|max:50',
            'estado' => 'required|in:pendiente,pagado,cancelado',
            'fecha_pago' => 'nullable|date',
        ]);

        $data = $request->only(['reservacion_id','tarjeta_id','monto','metodo_pago','estado','fecha_pago']);
        $payment = Payment::create($data);
        $this->ensurePaymentQrCode($payment);

        try { Log::entry('pago', 'Pago creado: #' . $payment->id, auth()->id(), 'reservacion', $payment->reservacion_id, route('pagos.show', $payment->id)); } catch (\Throwable $e) {}

        if ($request->wantsJson()) return response()->json($payment, 201);
        return redirect()->route('pagos.index')->with('success','Pago creado');
    }

    public function show(Request $request, $id)
    {
        $query = Payment::query()->with(['reservation','tarjeta']);
        $this->applyRolePaymentFilter($query, auth()->user());
        $p = $query->where('id', $id)->first();
        if (!$p) return $request->wantsJson() ? response()->json(['message'=>'No encontrado'],404) : abort(404);
        $this->ensurePaymentQrCode($p);
        $p->loadMissing(['reservation','tarjeta']);
        if ($request->wantsJson()) return response()->json($p);
        return view('pagos.show', ['payment' => $p]);
    }

    /**
     * Mostrar formulario de pago para una reservación (público autenticado / admin)
     */
    public function form(Request $request, $id)
    {
        $reservacion = Reservation::find($id);
        if (!$reservacion) return abort(404);

        $current = auth()->user();
        $isAdmin = $current && (($current->rol ?? '') === 'admin');

        // Prevent showing the payment form for expired or already-paid reservations
        if ($reservacion->isExpired() || $reservacion->isPaid()) {
            if ($request->wantsJson()) {
                return response()->json(['message' => 'Reservación no disponible para pago'], 403);
            }
            return redirect()->route('reservaciones.show', $reservacion->id)->with('success', 'La reservación no está disponible para pago (expirada o ya pagada).');
        }

        // Non-admin users: only allow paying with their assigned card. If they have none, redirect to tarjetas index.
        if (!$isAdmin) {
            if (!$current) return redirect()->route('login');
            // if user has no assigned card, redirect to tarjetas
            if (empty($current->id_tarjeta)) {
                return redirect()->route('tarjetas.index')->with('success', 'No tienes una tarjeta asociada. Agrega una para poder pagar.');
            }
            $tarjetas = TarjetaSimulada::where('id', $current->id_tarjeta)->get();
        } else {
            // admin may choose any tarjeta
            $tarjetas = TarjetaSimulada::orderByDesc('id')->get();
        }

        return view('pagos.form', [
            'reservacion' => $reservacion,
            'tarjetas' => $tarjetas,
        ]);
    }

    public function update(Request $request, $id)
    {
        if (! $this->isAdmin(auth()->user())) {
            abort(403);
        }

        $p = Payment::find($id);
        if (!$p) return $request->wantsJson() ? response()->json(['message'=>'No encontrado'],404) : abort(404);

        $request->validate([
            'reservacion_id' => 'nullable|exists:reservaciones,id',
            'tarjeta_id' => 'nullable|exists:tarjetas_simuladas,id',
            'monto' => 'sometimes|numeric',
            'metodo_pago' => 'sometimes|string|max:50',
            'estado' => 'sometimes|in:pendiente,pagado,cancelado',
            'fecha_pago' => 'nullable|date',
        ]);

        $p->update($request->only(['reservacion_id','tarjeta_id','monto','metodo_pago','estado','fecha_pago']));
        $this->ensurePaymentQrCode($p);
        try { Log::entry('pago', 'Pago actualizado: #' . $p->id, auth()->id(), 'reservacion', $p->reservacion_id, route('pagos.show', $p->id)); } catch (\Throwable $e) {}
        if ($request->wantsJson()) return response()->json($p);
        return redirect()->route('pagos.index')->with('success','Pago actualizado');
    }

    public function destroy(Request $request, $id)
    {
        if (! $this->isAdmin(auth()->user())) {
            abort(403);
        }

        $p = Payment::find($id);
        if (!$p) return $request->wantsJson() ? response()->json(['message'=>'No encontrado'],404) : abort(404);
        $p->delete();
        try { Log::entry('pago', 'Pago eliminado: #' . $p->id, auth()->id(), 'reservacion', $p->reservacion_id, route('pagos.show', $p->id)); } catch (\Throwable $e) {}
        if ($request->wantsJson()) return response()->json(['message'=>'Eliminado']);
        return redirect()->route('pagos.index')->with('success','Pago eliminado');
    }

    /**
     * Procesar pago desde el formulario público/administración
     */
    public function procesarPago(ProcessPaymentRequest $request)
    {
        $reservacionId = $request->input('reservacion_id');
        $tarjetaId = $request->input('tarjeta_id');
        $usuarioId = $request->input('usuario_id');
        $monto = floatval($request->input('monto'));
        $metodo = $request->input('metodo_pago');

        // Authorization: non-admin users may only pay for themselves
        $current = auth()->user();
        // Prevent CVV-protected actions if user's tarjeta operations are blocked
        if ($current && (($current->rol ?? '') !== 'admin')) {
            if (!empty($current->bloqueo_tarjetas)) {
                $msg = 'Tu cuenta está bloqueada para operaciones con tarjetas. Contacta al administrador.';
                if ($request->wantsJson()) return response()->json(['message' => $msg], 403);
                return back()->withInput()->withErrors(['cvv' => $msg]);
            }
        }
        if ($current && ($current->rol ?? '') !== 'admin') {
            if ($current->id != $usuarioId) {
                if ($request->wantsJson()) return response()->json(['message' => 'No autorizado'], 403);
                abort(403);
            }
        }

        // Procesamiento por método
        try {
            if ($metodo === 'tarjeta') {
                if (empty($tarjetaId)) {
                    return back()->withInput()->withErrors(['tarjeta_id' => 'Selecciona una tarjeta para pagar.']);
                }

                $cvv = $request->input('cvv');
                if (empty($cvv)) {
                    return back()->withInput()->withErrors(['cvv' => 'CVV requerido para pagos con tarjeta.']);
                }

                $result = DB::transaction(function() use ($tarjetaId, $monto, $cvv, $usuarioId, $reservacionId, $metodo) {
                    $tarjeta = TarjetaSimulada::where('id', $tarjetaId)->lockForUpdate()->first();
                    if (!$tarjeta) throw new \Exception('Tarjeta no encontrada');

                    // If current user is not admin, ensure the tarjeta belongs to the reservation's usuario (ownership)
                    $user = User::find($usuarioId);
                    $currentUser = auth()->user();
                    $isCurrentAdmin = $currentUser && (($currentUser->rol ?? '') === 'admin');
                    if (! $isCurrentAdmin) {
                        if ($user && !empty($user->id_tarjeta) && $user->id_tarjeta != $tarjeta->id) {
                            throw new \Exception('La tarjeta seleccionada no pertenece al usuario.');
                        }
                    }

                    if (trim($tarjeta->cvv) !== trim($cvv)) {
                        // signal CVV mismatch to outer scope so we can persist attempts outside transaction
                        throw new \Exception('CVV_MISMATCH');
                    }

                        // Reset attempts on successful CVV check for the acting user
                        $actingUser = auth()->user();
                        if ($actingUser && (($actingUser->rol ?? '') !== 'admin')) {
                            try {
                                \Illuminate\Support\Facades\DB::table('usuarios')->where('id', $actingUser->id)->update(['intentos_cvv' => 0]);
                            } catch (\Throwable $e) {
                                // ignore
                            }
                        }

                        if (floatval($tarjeta->saldo) < $monto) {
                        throw new \Exception('Saldo insuficiente en la tarjeta');
                    }

                    // Debitar
                    $tarjeta->saldo = round(floatval($tarjeta->saldo) - $monto, 2);
                    $tarjeta->save();

                    // Crear pago
                    $p = Payment::create([
                        'reservacion_id' => $reservacionId,
                        'tarjeta_id' => $tarjeta->id,
                        'monto' => $monto,
                        'metodo_pago' => $metodo,
                        'estado' => 'pagado',
                        'fecha_pago' => Carbon::now(),
                        'usuario_id' => $usuarioId,
                    ]);
                    $this->ensurePaymentQrCode($p);

                    // Actualizar reservación
                    $r = Reservation::find($reservacionId);
                    if ($r) {
                        $r->estado_pago = 'pagado';
                        if (($r->estado ?? '') !== 'confirmada') $r->estado = 'confirmada';
                        $r->save();
                    }

                    return $p;
                });

                if ($request->wantsJson()) return response()->json($result);
                try { Log::entry('pago', 'Pago realizado correctamente: #' . ($result->id ?? 'n/a'), $usuarioId, 'reservacion', $reservacionId, isset($result->id) ? route('pagos.show', $result->id) : route('reservaciones.show', $reservacionId)); } catch (\Throwable $e) {}
                return redirect()->route('reservaciones.show', $reservacionId)->with('success', 'Pago realizado correctamente');

            } else {
                // Efectivo u otros métodos: crear pago y marcar reservación
                $p = Payment::create([
                    'reservacion_id' => $reservacionId,
                    'tarjeta_id' => null,
                    'monto' => $monto,
                    'metodo_pago' => $metodo,
                    'estado' => 'pagado',
                    'fecha_pago' => Carbon::now(),
                    'usuario_id' => $usuarioId,
                ]);
                $this->ensurePaymentQrCode($p);

                $r = Reservation::find($reservacionId);
                if ($r) {
                    $r->estado_pago = 'pagado';
                    if (($r->estado ?? '') !== 'confirmada') $r->estado = 'confirmada';
                    $r->save();
                }

                if ($request->wantsJson()) return response()->json($p);
                try { Log::entry('pago', 'Pago registrado (efectivo): #' . $p->id, $usuarioId, 'reservacion', $reservacionId, route('pagos.show', $p->id)); } catch (\Throwable $e) {}
                return redirect()->route('reservaciones.show', $reservacionId)->with('success', 'Pago registrado (efectivo)');
            }

        } catch (\Exception $e) {
            $msg = $e->getMessage() ?: 'Error procesando el pago';

            // Handle CVV mismatch specifically: increment attempts outside transaction so it persists
            if ($msg === 'CVV_MISMATCH') {
                try {
                    $acting = auth()->user();
                    if ($acting && (($acting->rol ?? '') !== 'admin')) {
                        \Illuminate\Support\Facades\DB::table('usuarios')->where('id', $acting->id)->increment('intentos_cvv');
                        $attempts = (int) \Illuminate\Support\Facades\DB::table('usuarios')->where('id', $acting->id)->value('intentos_cvv');
                        if ($attempts >= 5) {
                            \Illuminate\Support\Facades\DB::table('usuarios')->where('id', $acting->id)->update(['bloqueo_tarjetas' => true]);
                            $msg = 'CVV incorrecto. Tu cuenta ha sido bloqueada después de varios intentos. Contacta al administrador.';
                        } else {
                            $remaining = max(0, 5 - $attempts);
                            $msg = "CVV incorrecto. Te quedan {$remaining} intentos antes del bloqueo.";
                        }
                    } else {
                        $msg = 'CVV incorrecto';
                    }
                } catch (\Throwable $ex) {
                    $msg = 'CVV incorrecto';
                }
            }

                try { Log::entry('pago', 'Pago fallido (CVV): reservacion #' . $reservacionId . ' usuario #' . (auth()->id() ?? 'anon') . ' - ' . $msg, auth()->id(), 'reservacion', $reservacionId, route('reservaciones.show', $reservacionId)); } catch (\Throwable $e) {}
                if ($request->wantsJson()) return response()->json(['message' => $msg], 400);
            try { Log::entry('pago', 'Pago fallido: reservacion #' . $reservacionId . ' usuario #' . (auth()->id() ?? 'anon') . ' - ' . $msg, auth()->id(), 'reservacion', $reservacionId, route('reservaciones.show', $reservacionId)); } catch (\Throwable $e) {}
            return back()->withInput()->withErrors(['pagos' => $msg]);
        }
    }

    private function ensurePaymentQrCode(Payment $payment): void
    {
        if (strtolower((string) $payment->estado) !== 'pagado') {
            return;
        }

        if (! empty($payment->codigo_qr)) {
            return;
        }

        $reservationPart = 'R' . (int) ($payment->reservacion_id ?? 0);
        $paymentPart = 'P' . (int) $payment->id;

        for ($i = 0; $i < 5; $i++) {
            $candidate = $reservationPart . '-' . $paymentPart . '-' . strtoupper(Str::random(8));
            $exists = Payment::where('codigo_qr', $candidate)->exists();
            if ($exists) {
                continue;
            }

            $payment->codigo_qr = $candidate;
            $payment->codigo_qr_generado_en = Carbon::now();
            $payment->save();
            return;
        }
    }

    private function isAdmin($user): bool
    {
        return (bool) ($user && (($user->rol ?? '') === 'admin'));
    }

    private function applyRolePaymentFilter($query, $user): void
    {
        if (! $user) {
            $query->whereRaw('1 = 0');
            return;
        }

        if ($this->isAdmin($user)) {
            return;
        }

        $query->where(function ($q) use ($user) {
            $q->where('usuario_id', $user->id)
              ->orWhereHas('reservation', function ($rq) use ($user) {
                  $rq->where('usuario_id', $user->id);
              });
        });
    }
}
