<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Comentario;

class ComentarioController extends Controller
{
    public function index()
    {
        return response()->json(Comentario::all());
    }

    public function store(Request $request)
    {
        $request->validate([
            'reservacion_id' => 'required|exists:reservaciones,id',
            'usuario_id' => 'required|exists:usuarios,id',
            'calificacion' => 'required|integer|min:1|max:5',
            'comentario' => 'nullable|string|max:1000',
            'fecha_creacion' => 'nullable|date',
        ]);

        $c = Comentario::create($request->only(['reservacion_id','usuario_id','calificacion','comentario','fecha_creacion']));
        return response()->json($c, 201);
    }

    public function show($id)
    {
        $c = Comentario::find($id);
        if (!$c) return response()->json(['message' => 'Comentario no encontrado'], 404);
        return response()->json($c);
    }

    public function update(Request $request, $id)
    {
        $c = Comentario::find($id);
        if (!$c) return response()->json(['message' => 'Comentario no encontrado'], 404);

        $request->validate([
            'calificacion' => 'sometimes|integer|min:1|max:5',
            'comentario' => 'sometimes|string|max:1000',
            'fecha_creacion' => 'nullable|date',
        ]);

        $c->update($request->only(['calificacion','comentario','fecha_creacion']));
        return response()->json($c);
    }

    public function destroy($id)
    {
        $c = Comentario::find($id);
        if (!$c) return response()->json(['message' => 'Comentario no encontrado'], 404);
        $c->delete();
        return response()->json(['message' => 'Comentario eliminado']);
    }
}