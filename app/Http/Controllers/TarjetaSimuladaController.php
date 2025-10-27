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
}

function schemaHasColumn($table, $column) {
    try {
        return \Illuminate\Support\Facades\Schema::hasColumn($table, $column);
    } catch (\Throwable $e) {
        return false;
    }
}