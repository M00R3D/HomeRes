<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\TarjetaSimulada;
use App\Models\User;
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
        $t->delete();
        return redirect()->back()->with('success','Tarjeta eliminada');
    }

    public function deposit(Request $request, $id)
    {
        $request->validate(['monto' => 'required|numeric|min:0.01']);
        $m = floatval($request->input('monto'));
        return $this->changeBalance($id, $m, 'Ingreso de saldo');
    }

    public function withdraw(Request $request, $id)
    {
        $request->validate(['monto' => 'required|numeric|min:0.01']);
        $m = floatval($request->input('monto'));
        return $this->changeBalance($id, -$m, 'Retiro de saldo');
    }

    protected function changeBalance($id, $delta, $note = '')
    {
        $t = TarjetaSimulada::find($id);
        if (!$t) return redirect()->back()->with('error','Tarjeta no encontrada');

        DB::transaction(function() use ($t, $delta) {
            $t->saldo = round($t->saldo + $delta, 2);
            $t->save();
        });

        return redirect()->back()->with('success','Saldo actualizado');
    }

    public function assign(Request $request, $id)
    {
        $request->validate(['usuario_id' => 'required|exists:usuarios,id']);
        $t = TarjetaSimulada::find($id);
        if (!$t) return redirect()->back()->with('error','Tarjeta no encontrada');
        if (schemaHasColumn('usuarios', 'id_tarjeta')) {
            $newUserId = (int) $request->usuario_id;

            DB::transaction(function() use ($t, $newUserId) {
                DB::table('usuarios')->where('id_tarjeta', $t->id)->update(['id_tarjeta' => null]);
                DB::table('usuarios')->where('id', $newUserId)->update(['id_tarjeta' => $t->id]);
            });

            return redirect()->back()->with('success','Tarjeta asignada al usuario (usuarios.id_tarjeta actualizada)');
        }
        if (schemaHasColumn('tarjetas_simuladas', 'usuario_id')) {
            $t->usuario_id = $request->usuario_id;
            $t->save();
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