<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\TarjetaSimulada;
use App\Models\User;
use App\Models\Log;
use Illuminate\Support\Facades\DB;

class TarjetaSimuladaController extends Controller
{
    public function index(Request $request)
    {
        $q = TarjetaSimulada::with('assignedUser');
        $tarjetas = $q->orderByDesc('id')->paginate(12);

        $tarjetas->getCollection()->transform(function ($t) {
            $t->usuario_asignado_nombre = $t->assignedUser
                ? trim(($t->assignedUser->nombre ?? '') . ' ' . ($t->assignedUser->apellido ?? ''))
                : null;
            return $t;
        });
        $usuarios = User::all();

        return view('tarjetas.index', ['tarjetas' => $tarjetas, 'usuarios' => $usuarios]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'numero_tarjeta' => 'required|string|size:16',
            'nombre' => 'required|string|max:100',
            'expiracion' => 'required|string|max:5',
            'cvv' => 'required|string|size:3',
            'saldo' => 'nullable|numeric',
        ]);

        $data['saldo'] = $data['saldo'] ?? 0;
        $t = TarjetaSimulada::create($data);

        try {
            Log::entry('tarjeta', 'Tarjeta creada: #' . $t->id . ' ' . ($t->numero_tarjeta ?? ''), auth()->id(), 'tarjeta', $t->id, route('tarjetas.show', $t->id));
        } catch (\Throwable $e) {}

        // If requested, assign the created tarjeta to the authenticated user
        if ($request->filled('assign_to_user') && $request->user()) {
            $userId = $request->user()->id;
            if (schemaHasColumn('usuarios', 'id_tarjeta')) {
                \Illuminate\Support\Facades\DB::table('usuarios')->where('id', $userId)->update(['id_tarjeta' => $t->id]);
            } elseif (schemaHasColumn('tarjetas_simuladas', 'usuario_id')) {
                $t->usuario_id = $userId;
                $t->save();
            }
        }

        return redirect()->back()->with('success','Tarjeta creada');
    }

    public function show(Request $request, $id)
    {
        $t = TarjetaSimulada::find($id);
        if (!$t) return abort(404);
        return view('tarjetas.show', ['tarjeta' => $t]);
    }

    public function update(Request $request, $id)
    {
        $t = TarjetaSimulada::find($id);
        if (!$t) return abort(404);

        $data = $request->validate([
            'numero_tarjeta' => 'sometimes|string|size:16',
            'nombre' => 'sometimes|string|max:100',
            'expiracion' => 'sometimes|string|max:5',
            'cvv' => 'nullable|string|size:3',
            'saldo' => 'nullable|numeric',
        ]);

        $t->update($data);

        try {
            Log::entry('tarjeta', 'Tarjeta actualizada: #' . $t->id, auth()->id(), 'tarjeta', $t->id, route('tarjetas.show', $t->id));
        } catch (\Throwable $e) {}

        // If requested, assign the tarjeta to the authenticated user
        if ($request->filled('assign_to_user') && $request->user()) {
            $userId = $request->user()->id;
            if (schemaHasColumn('usuarios', 'id_tarjeta')) {
                \Illuminate\Support\Facades\DB::table('usuarios')->where('id', $userId)->update(['id_tarjeta' => $t->id]);
            } elseif (schemaHasColumn('tarjetas_simuladas', 'usuario_id')) {
                $t->usuario_id = $userId;
                $t->save();
            }
        }

        return redirect()->back()->with('success','Tarjeta actualizada');
    }

    public function destroy(Request $request, $id)
    {
        $t = TarjetaSimulada::find($id);
        if (!$t) return abort(404);
        $num = $t->numero_tarjeta;
        $t->delete();
        try { Log::entry('tarjeta', 'Tarjeta eliminada: #' . $id . ' ' . ($num ?? ''), auth()->id(), 'tarjeta', $id, route('tarjetas.show', $id)); } catch (\Throwable $e) {}
        return redirect()->back()->with('success','Tarjeta eliminada');
    }

    public function deposit(Request $request, $id)
    {
        $actor = $request->user();
        if (! $actor || ($actor->rol ?? '') !== 'admin') {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        $request->validate(['monto' => 'required|numeric|min:0.01']);
        $m = floatval($request->input('monto'));
        return $this->changeBalance($id, $m, 'Ingreso de saldo');
    }

    public function withdraw(Request $request, $id)
    {
        $actor = $request->user();
        if (! $actor || ($actor->rol ?? '') !== 'admin') {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        $request->validate(['monto' => 'required|numeric|min:0.01']);
        $m = floatval($request->input('monto'));
        return $this->changeBalance($id, -$m, 'Retiro de saldo');
    }

    protected function changeBalance($id, $delta, $note = '')
    {
        $t = TarjetaSimulada::find($id);
        if (!$t) return redirect()->back()->with('error','Tarjeta no encontrada');

        $before = $t->saldo;
        DB::transaction(function() use ($t, $delta) {
            $t->saldo = round($t->saldo + $delta, 2);
            $t->save();
        });

        try {
            Log::entry('tarjeta', sprintf('Saldo modificado en tarjeta #%d: %s -> %s (delta %s). %s', $t->id, $before, $t->saldo, $delta, $note), auth()->id(), 'tarjeta', $t->id, route('tarjetas.show', $t->id));
        } catch (\Throwable $e) {}

        return redirect()->back()->with('success','Saldo actualizado');
    }

    public function assign(Request $request, $id)
    {
        $request->validate(['usuario_id' => 'required|exists:usuarios,id']);
        $t = TarjetaSimulada::find($id);
        if (!$t) return redirect()->back()->with('error','Tarjeta no encontrada');
        $current = $request->user();
        if ($current && (($current->rol ?? '') !== 'admin')) {
            if (!empty($current->bloqueo_tarjetas)) {
                return redirect()->back()->with('error','Tu cuenta está bloqueada para operaciones con tarjetas. Contacta al administrador.');
            }
        }
        if (schemaHasColumn('usuarios', 'id_tarjeta')) {
            $newUserId = (int) $request->usuario_id;

            // Optional CVV verification: if 'cvv' provided, ensure it matches; otherwise allow admin or silent assign
            if ($request->filled('cvv')) {
                $cvv = trim($request->input('cvv'));
                if (trim($t->cvv) !== $cvv) {
                    // increment attempts for non-admin acting user
                    if ($current && (($current->rol ?? '') !== 'admin')) {
                        try {
                            \Illuminate\Support\Facades\DB::table('usuarios')->where('id', $current->id)->increment('intentos_cvv');
                            $attempts = (int) \Illuminate\Support\Facades\DB::table('usuarios')->where('id', $current->id)->value('intentos_cvv');
                            if ($attempts >= 5) {
                                \Illuminate\Support\Facades\DB::table('usuarios')->where('id', $current->id)->update(['bloqueo_tarjetas' => true]);
                                try { Log::entry('tarjeta', 'Asignación fallida por CVV - usuario bloqueado: usuario #' . $current->id . ' tarjeta #' . $t->id, $current->id, 'tarjeta', $t->id, route('tarjetas.show', $t->id)); } catch (\Throwable $e) {}
                                return redirect()->back()->with('error','CVV incorrecto. Tu cuenta ha sido bloqueada. Contacta al administrador.');
                            }
                            $remaining = max(0, 5 - $attempts);
                            return redirect()->back()->with('error',"CVV incorrecto. Te quedan {$remaining} intentos antes del bloqueo.");
                        } catch (\Throwable $e) {
                            try { Log::entry('tarjeta', 'Asignación fallida por CVV: usuario #' . ($current->id ?? 'anon') . ' tarjeta #' . $t->id, $current->id ?? null, 'tarjeta', $t->id, route('tarjetas.show', $t->id)); } catch (\Throwable $e) {}
                            return redirect()->back()->with('error','CVV incorrecto');
                        }
                    }
                    return redirect()->back()->with('error','CVV incorrecto');
                }
                // successful verification: reset attempts for acting user
                if ($current && (($current->rol ?? '') !== 'admin')) {
                    try {
                        \Illuminate\Support\Facades\DB::table('usuarios')->where('id', $current->id)->update(['intentos_cvv' => 0]);
                    } catch (\Throwable $e) {}
                }
            }

            DB::transaction(function() use ($t, $newUserId) {
                DB::table('usuarios')->where('id_tarjeta', $t->id)->update(['id_tarjeta' => null]);
                DB::table('usuarios')->where('id', $newUserId)->update(['id_tarjeta' => $t->id]);
            });

            try { Log::entry('tarjeta', 'Tarjeta #' . $t->id . ' asignada a usuario #' . $newUserId, auth()->id(), 'tarjeta', $t->id, route('tarjetas.show', $t->id)); } catch (\Throwable $e) {}
            return redirect()->back()->with('success','Tarjeta asignada al usuario (usuarios.id_tarjeta actualizada)');
        }
        if (schemaHasColumn('tarjetas_simuladas', 'usuario_id')) {
            // Optional CVV verification similar to above
            if ($request->filled('cvv')) {
                $cvv = trim($request->input('cvv'));
                if (trim($t->cvv) !== $cvv) {
                    if ($current && (($current->rol ?? '') !== 'admin')) {
                        try {
                            \Illuminate\Support\Facades\DB::table('usuarios')->where('id', $current->id)->increment('intentos_cvv');
                            $attempts = (int) \Illuminate\Support\Facades\DB::table('usuarios')->where('id', $current->id)->value('intentos_cvv');
                            if ($attempts >= 5) {
                                \Illuminate\Support\Facades\DB::table('usuarios')->where('id', $current->id)->update(['bloqueo_tarjetas' => true]);
                                return redirect()->back()->with('error','CVV incorrecto. Tu cuenta ha sido bloqueada. Contacta al administrador.');
                            }
                            $remaining = max(0, 5 - $attempts);
                            return redirect()->back()->with('error',"CVV incorrecto. Te quedan {$remaining} intentos antes del bloqueo.");
                        } catch (\Throwable $e) {
                            try { Log::entry('tarjeta', 'Asignación fallida por CVV: usuario #' . ($current->id ?? 'anon') . ' tarjeta #' . $t->id, $current->id ?? null, 'tarjeta', $t->id, route('tarjetas.show', $t->id)); } catch (\Throwable $e) {}
                            return redirect()->back()->with('error','CVV incorrecto');
                        }
                    }
                    return redirect()->back()->with('error','CVV incorrecto');
                }
                if ($current && (($current->rol ?? '') !== 'admin')) {
                    try {
                        \Illuminate\Support\Facades\DB::table('usuarios')->where('id', $current->id)->update(['intentos_cvv' => 0]);
                    } catch (\Throwable $e) {}
                }
            }

            $t->usuario_id = $request->usuario_id;
            $t->save();
            try { Log::entry('tarjeta', 'Tarjeta #' . $t->id . ' asignada a usuario #' . $request->usuario_id, auth()->id(), 'tarjeta', $t->id, route('tarjetas.show', $t->id)); } catch (\Throwable $e) {}
            return redirect()->back()->with('success','Tarjeta asignada al usuario (tarjetas_simuladas.usuario_id actualizada)');
        }
        return redirect()->back()->with('error','Ni usuarios.id_tarjeta ni tarjetas_simuladas.usuario_id existen. Agrega una columna para guardar la asignación.');
    }

    /**
     * Buscar tarjeta por número (AJAX)
     */
    public function check(Request $request)
    {
        $numero = $request->query('numero', '');
        $numero = preg_replace('/\D/', '', $numero);
        if (strlen($numero) !== 16) {
            return response()->json(['error' => 'Formato inválido'], 422);
        }
        // Try to find by exact match first, otherwise search by last4 and normalize
        $t = TarjetaSimulada::where('numero_tarjeta', $numero)->first();
        if (!$t) {
            $last4 = substr($numero, -4);
            $candidates = TarjetaSimulada::where('numero_tarjeta', 'like', '%' . $last4)->get();
            foreach ($candidates as $cand) {
                $candNum = preg_replace('/\D/', '', $cand->numero_tarjeta ?? '');
                if ($candNum === $numero) { $t = $cand; break; }
            }
        }
        if (!$t) return response()->json(['found' => false], 404);
        return response()->json([
            'found' => true,
            'tarjeta' => [
                'id' => $t->id,
                'numero_tarjeta' => $t->numero_tarjeta,
                'saldo' => $t->saldo,
                'nombre' => $t->nombre,
                'expiracion' => $t->expiracion,
            ]
        ]);
    }

    /**
     * Crear una tarjeta con número aleatorio único y opcionalmente asignarla al usuario autenticado.
     * Retorna JSON cuando se invoca vía AJAX.
     */
    public function createRandom(Request $request)
    {
        $assign = $request->filled('assign_to_user') && $request->user();

        $attempts = 0;
        $numero = null;
        do {
            $numero = '';
            for ($i = 0; $i < 16; $i++) { $numero .= mt_rand(0,9); }
            $exists = TarjetaSimulada::where('numero_tarjeta', $numero)->exists();
            $attempts++;
        } while ($exists && $attempts < 20);

        if ($exists) {
            try { Log::entry('tarjeta', 'Creación aleatoria fallida: no fue posible generar número único', auth()->id(), null, null); } catch (\Throwable $e) {}
            return response()->json(['error' => 'No fue posible generar un número único'], 500);
        }

        $cvv = str_pad((string)mt_rand(0,999), 3, '0', STR_PAD_LEFT);
        // expiracion aleatoria 24-60 meses en el futuro
        $mm = str_pad((string)mt_rand(1,12),2,'0',STR_PAD_LEFT);
        $yy = date('y', strtotime('+' . mt_rand(24,60) . ' months'));
        $exp = $mm . '/' . $yy;

        $t = TarjetaSimulada::create([
            'numero_tarjeta' => $numero,
            'nombre' => 'Tarjeta creada',
            'expiracion' => $exp,
            'cvv' => $cvv,
            'saldo' => 0,
        ]);

        try { Log::entry('tarjeta', 'Tarjeta creada aleatoriamente: #' . $t->id . ' ' . ($t->numero_tarjeta ?? ''), auth()->id(), 'tarjeta', $t->id, route('tarjetas.show', $t->id)); } catch (\Throwable $e) {}

        if ($assign) {
            $userId = $request->user()->id;
            if (schemaHasColumn('usuarios', 'id_tarjeta')) {
                DB::table('usuarios')->where('id', $userId)->update(['id_tarjeta' => $t->id]);
            } elseif (schemaHasColumn('tarjetas_simuladas', 'usuario_id')) {
                $t->usuario_id = $userId;
                $t->save();
            }
        }

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['created' => true, 'tarjeta' => $t]);
        }

        return redirect()->back()->with('success','Tarjeta creada automáticamente');
    }
}

function schemaHasColumn($table, $column) {
    try {
        return \Illuminate\Support\Facades\Schema::hasColumn($table, $column);
    } catch (\Throwable $e) {
        return false;
    }
}