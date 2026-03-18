<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Reservation;
use App\Models\User;
use App\Models\Propiedad;
use App\Models\Payment;
use App\Models\Log;
use Carbon\Carbon; 
use Illuminate\Support\Str;

class ReservationController extends Controller
{
    public function index(Request $request)
    {
        $q = Reservation::query();

        if ($request->filled('usuario_id')) $q->where('usuario_id', $request->usuario_id);
        if ($request->filled('propiedad_id')) $q->where('propiedad_id', $request->propiedad_id);
        if ($request->filled('estado')) $q->where('estado', $request->estado);
        $currentUser = auth()->user();
        if ($currentUser && ($currentUser->rol ?? '') !== 'admin') {
            $q->where('usuario_id', $currentUser->id);
        }

        if ($request->wantsJson()) {
            return response()->json($q->with(['user','propiedad'])->get());
        }

        $reservaciones = $q->with(['user','propiedad'])->orderByDesc('created_at')->get();
        $usuarios = User::all();
        $propiedades = Propiedad::all();

        return view('reservaciones.index', compact('reservaciones','propiedades','usuarios','currentUser'));
    }
    public function createForPropiedad(Request $request, $id)
    {
        $prop = Propiedad::findOrFail($id);
        $reservas = Reservation::where('propiedad_id', $id)
            ->where('estado', '!=', 'cancelada')
            ->get(['check_in','check_out']);
        $blocked = [];
        foreach ($reservas as $r) {
            $blocked[] = [
                'from' => (string) $r->check_in,
                'to'   => (string) $r->check_out,
            ];
        }
        return view('propiedades.reservar', ['propiedad' => $prop, 'blockedRanges' => $blocked]);
    }
 
    public function reservedDates($id)
    {
        $reservas = Reservation::where('propiedad_id', $id)
            ->where('estado', '!=', 'cancelada')
            ->get(['check_in','check_out']);
        $blocked = [];
        foreach ($reservas as $r) {
            $blocked[] = ['from' => (string)$r->check_in, 'to' => (string)$r->check_out];
        }
        return response()->json(['blocked' => $blocked]);
    }
 
    public function store(Request $request)
    {
        $request->validate([
            'usuario_id' => 'nullable|exists:usuarios,id',
            'propiedad_id' => 'nullable|exists:propiedades,id',
            'check_in' => 'required|date|after_or_equal:today',
            'check_out' => 'required|date|after:check_in',
            'num_personas' => 'required|integer|min:1',
            'total' => 'required|numeric',
            'estado' => 'nullable|in:pendiente,confirmada,cancelada,completada',
            'nota' => 'nullable|string|max:500',
            'estado_pago' => 'nullable|in:pendiente,pagado,cancelado',
        ]);

        $data = $request->all();
        if (empty($data['usuario_id']) && auth()->check()) {
            $data['usuario_id'] = auth()->id();
        }

        if ($request->filled('propiedad_id')) {
            $pId = $request->propiedad_id;
            $newIn = Carbon::parse($request->check_in)->startOfDay();
            $newOut = Carbon::parse($request->check_out)->startOfDay();
            $overlap = Reservation::where('propiedad_id', $pId)
                ->where('estado', '!=', 'cancelada')
                ->where(function($q) use ($newIn, $newOut) {
                    $q->where('check_in', '<', $newOut->toDateString())
                      ->where('check_out', '>', $newIn->toDateString());
                })->exists();
            if ($overlap) {
                try { Log::entry('reservacion', 'Creación fallida: fechas ocupadas para propiedad #' . $pId, auth()->id(), 'propiedad', $pId, route('propiedades.show', $pId)); } catch (\Throwable $e) {}
                return back()->withInput()->withErrors(['check_in' => 'Las fechas seleccionadas están ocupadas para esa propiedad.']);
            }
        }
 
        $r = Reservation::create([
            'usuario_id'   => $data['usuario_id'] ?? null,
            'propiedad_id' => $data['propiedad_id'] ?? null,
            'check_in'     => $data['check_in'],
            'check_out'    => $data['check_out'],
            'num_personas' => $data['num_personas'],
            'total'        => $data['total'],
            'estado'       => $data['estado'] ?? 'pendiente',
            'nota'         => $data['nota'] ?? null,
            'estado_pago'   => $data['estado_pago'] ?? 'pendiente',
        ]);

        try { Log::entry('reservacion', 'Reservación creada: #' . $r->id, auth()->id(), 'propiedad', $r->propiedad_id, route('reservaciones.show', $r->id)); } catch (\Throwable $e) {}

        if ($request->wantsJson()) {
            return response()->json($r, 201);
        }

        return redirect()->route('reservaciones.index')->with('success', 'Reservación creada');
    }

    public function show($id)
    {
        $r = Reservation::find($id);
        if (!$r) return response()->json(['message' => 'Reservación no encontrada'], 404);
        return response()->json($r);
    }

    public function showView(Request $request, $id)
    {
        $r = Reservation::with(['user','propiedad'])->find($id);
        if (!$r) {
            abort(404);
        }

        if ($request->wantsJson()) {
            return response()->json($r);
        }

        $usuarios = User::all();
        $propiedades = Propiedad::all();
        $currentUser = auth()->user();
        $paidPayment = Payment::where('reservacion_id', $r->id)
            ->where('estado', 'pagado')
            ->orderByDesc('id')
            ->first();
        if ($paidPayment) {
            $this->ensurePaymentQrCode($paidPayment);
        }

        return view('reservaciones.show', compact('r','usuarios','propiedades','currentUser','paidPayment'));
    }

    public function editView(Request $request, $id)
    {
        $currentUser = auth()->user();
        if (! $currentUser || ($currentUser->rol ?? '') !== 'admin') {
            abort(403);
        }

        $r = Reservation::with(['user','propiedad'])->find($id);
        if (!$r) {
            abort(404);
        }

        $usuarios = User::all();
        $propiedades = Propiedad::all();

        return view('reservaciones.edit', compact('r','usuarios','propiedades','currentUser'));
    }

    public function update(Request $request, $id)
    {
        $r = Reservation::find($id);
        if (!$r) return response()->json(['message' => 'Reservación no encontrada'], 404);

        $actor = auth()->user();
        if (! $actor || ($actor->rol ?? '') !== 'admin') {
            if ($request->wantsJson()) return response()->json(['message' => 'No autorizado'], 403);
            abort(403);
        }

        $request->validate([
            'usuario_id' => 'sometimes|exists:usuarios,id',
            'propiedad_id' => 'sometimes|exists:propiedades,id',
            'check_in' => 'sometimes|date',
            'check_out' => 'sometimes|date|after_or_equal:check_in',
            'num_personas' => 'sometimes|integer|min:1',
            'total' => 'sometimes|numeric',
            'estado' => 'sometimes|in:pendiente,confirmada,cancelada,completada',
            'estado_pago' => 'sometimes|in:pendiente,pagado,parcial,fallido,failed',
            'nota' => 'nullable|string|max:500',
        ]);

        $fields = $request->only([
            'usuario_id','propiedad_id','check_in','check_out','num_personas','total','estado','nota','estado_pago'
        ]);
        $r->update($fields);

        try { Log::entry('reservacion', 'Reservación actualizada: #' . $r->id, auth()->id(), 'propiedad', $r->propiedad_id, route('reservaciones.show', $r->id)); } catch (\Throwable $e) {}

        if ($request->wantsJson()) {
            return response()->json($r);
        }

        return redirect()->route('reservaciones.index')->with('success', 'Reservación actualizada');
    }

    public function destroy($id)
    {
        $r = Reservation::find($id);
        if (!$r) return response()->json(['message' => 'Reservación no encontrada'], 404);
        $r->delete();
        try { Log::entry('reservacion', 'Reservación eliminada: #' . $r->id, auth()->id(), 'propiedad', $r->propiedad_id, route('reservaciones.show', $r->id)); } catch (\Throwable $e) {}

        if (request()->wantsJson()) {
            return response()->json(['message' => 'Reservación eliminada']);
        }

        return redirect()->route('reservaciones.index')->with('success', 'Reservación eliminada');
    }

    public function changeEstado(Request $request, $id)
    {
        $r = Reservation::find($id);
        if (!$r) return response()->json(['message' => 'Reservación no encontrada'], 404);

        $request->validate([
            'estado' => 'required|in:pendiente,confirmada,cancelada,completada',
        ]);

        $old = $r->estado;
        $r->estado = $request->estado;
        $r->save();
        try { Log::entry('reservacion', sprintf('Reservación #%d: estado cambiado %s -> %s', $r->id, $old, $r->estado), auth()->id(), 'reservacion', $r->id, route('reservaciones.show', $r->id)); } catch (\Throwable $e) {}

        if ($request->wantsJson()) {
            return response()->json($r);
        }

        return redirect()->route('reservaciones.index')->with('success', 'Estado actualizado');
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
            if (Payment::where('codigo_qr', $candidate)->exists()) {
                continue;
            }

            $payment->codigo_qr = $candidate;
            $payment->codigo_qr_generado_en = now();
            $payment->save();
            return;
        }
    }
}