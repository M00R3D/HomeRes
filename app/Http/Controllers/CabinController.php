<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Cabin;

class CabinController extends Controller
{
    public function index()
    {
        // mostrar cabañas disponibles por defecto
        return response()->json(Cabin::where('estado', 'disponible')->get());
    }

    public function store(Request $request)
    {
        $request->validate([
            'codigo' => 'nullable|string|max:50|unique:cabanas,codigo',
            'nombre' => 'required|string|max:100',
            'descripcion' => 'nullable|string|max:500',
            'capacidad' => 'required|integer|min:1',
            'precio_noche' => 'required|numeric',
            'ubicacion' => 'nullable|string|max:200',
            'servicios' => 'nullable|string|max:500',
            'estado' => 'nullable|in:disponible,ocupada,mantenimiento',
            'ruta_img' => 'nullable|string|max:200',
        ]);

        $cabin = Cabin::create($request->all());

        return response()->json($cabin, 201);
    }

    public function show($id)
    {
        $cabin = Cabin::find($id);
        if (!$cabin) return response()->json(['message' => 'Cabaña no encontrada'], 404);
        return response()->json($cabin);
    }

    public function update(Request $request, $id)
    {
        $cabin = Cabin::find($id);
        if (!$cabin) return response()->json(['message' => 'Cabaña no encontrada'], 404);

        $request->validate([
            'codigo' => 'sometimes|string|max:50|unique:cabanas,codigo,' . $id,
            'nombre' => 'sometimes|string|max:100',
            'descripcion' => 'nullable|string|max:500',
            'capacidad' => 'sometimes|integer|min:1',
            'precio_noche' => 'sometimes|numeric',
            'ubicacion' => 'nullable|string|max:200',
            'servicios' => 'nullable|string|max:500',
            'estado' => 'sometimes|in:disponible,ocupada,mantenimiento',
            'ruta_img' => 'nullable|string|max:200',
        ]);

        $cabin->update($request->all());
        return response()->json($cabin);
    }

    public function destroy($id)
    {
        $cabin = Cabin::find($id);
        if (!$cabin) return response()->json(['message' => 'Cabaña no encontrada'], 404);

        $cabin->delete();

        return response()->json(['message' => 'Cabaña eliminada']);
    }
}

