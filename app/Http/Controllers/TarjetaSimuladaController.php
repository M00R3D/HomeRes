<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\TarjetaSimulada;

class TarjetaSimuladaController extends Controller
{
    public function index(Request $request)
    {
        $q = TarjetaSimulada::query();
        if ($request->wantsJson()) return response()->json($q->get());
        return view('tarjetas.index', ['tarjetas' => $q->paginate(20)]);
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

        $t = TarjetaSimulada::create($data);

        if ($request->wantsJson()) return response()->json($t, 201);
        return redirect()->back()->with('success', 'Tarjeta creada');
    }

    public function show(Request $request, $id)
    {
        $t = TarjetaSimulada::find($id);
        if (!$t) return $request->wantsJson() ? response()->json(['message'=>'No encontrado'],404) : abort(404);
        if ($request->wantsJson()) return response()->json($t);
        return view('tarjetas.show', ['tarjeta' => $t]);
    }

    public function update(Request $request, $id)
    {
        $t = TarjetaSimulada::find($id);
        if (!$t) return $request->wantsJson() ? response()->json(['message'=>'No encontrado'],404) : abort(404);

        $data = $request->validate([
            'numero_tarjeta' => 'sometimes|string|size:16',
            'nombre' => 'sometimes|string|max:100',
            'expiracion' => 'sometimes|string|max:5',
            'cvv' => 'sometimes|string|size:3',
            'saldo' => 'nullable|numeric',
        ]);

        $t->update($data);

        if ($request->wantsJson()) return response()->json($t);
        return redirect()->back()->with('success','Tarjeta actualizada');
    }

    public function destroy(Request $request, $id)
    {
        $t = TarjetaSimulada::find($id);
        if (!$t) return $request->wantsJson() ? response()->json(['message'=>'No encontrado'],404) : abort(404);
        $t->delete();
        if ($request->wantsJson()) return response()->json(['message'=>'Eliminado']);
        return redirect()->back()->with('success','Tarjeta eliminada');
    }
}