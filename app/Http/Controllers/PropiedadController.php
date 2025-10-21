<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Propiedad;

class PropiedadController extends Controller
{
    public function index(Request $request)
    {
        $q = Propiedad::query();

        if ($request->filled('tipo')) $q->where('tipo', $request->tipo);
        if ($request->filled('estado')) $q->where('estado', $request->estado);
        if ($request->filled('capacidad_min')) $q->where('capacidad', '>=', (int)$request->capacidad_min);
        if ($request->filled('max_precio')) $q->where('precio_noche', '<=', (float)$request->max_precio);
        if ($request->filled('q')) $q->where('nombre', 'like', '%'.$request->q.'%');

        return response()->json($q->get());
    }

    public function store(Request $request)
    {
        $request->validate([
            'tipo' => 'required|in:cabaña,casa,departamento',
            'codigo' => 'nullable|string|max:50|unique:propiedades,codigo',
            'nombre' => 'required|string|max:100',
            'descripcion' => 'nullable|string|max:500',
            'capacidad' => 'required|integer|min:1',
            'precio_noche' => 'required|numeric',
            'ubicacion' => 'nullable|string|max:200',
            'servicios' => 'nullable|string|max:500',
            'estado' => 'nullable|in:disponible,ocupada,mantenimiento',
            'ruta_img' => 'nullable|string|max:200',
        ]);

        $prop = Propiedad::create($request->all());
        return response()->json($prop, 201);
    }

    public function show($id)
    {
        $prop = Propiedad::find($id);
        if (!$prop) return response()->json(['message' => 'Propiedad no encontrada'], 404);
        return response()->json($prop);
    }

    public function update(Request $request, $id)
    {
        $prop = Propiedad::find($id);
        if (!$prop) return response()->json(['message' => 'Propiedad no encontrada'], 404);

        $request->validate([
            'tipo' => 'sometimes|in:cabaña,casa,departamento',
            'codigo' => 'sometimes|string|max:50|unique:propiedades,codigo,'.$id,
            'nombre' => 'sometimes|string|max:100',
            'descripcion' => 'nullable|string|max:500',
            'capacidad' => 'sometimes|integer|min:1',
            'precio_noche' => 'sometimes|numeric',
            'ubicacion' => 'nullable|string|max:200',
            'servicios' => 'nullable|string|max:500',
            'estado' => 'sometimes|in:disponible,ocupada,mantenimiento',
            'ruta_img' => 'nullable|string|max:200',
        ]);

        $prop->update($request->all());
        return response()->json($prop);
    }

    public function destroy($id)
    {
        $prop = Propiedad::find($id);
        if (!$prop) return response()->json(['message' => 'Propiedad no encontrada'], 404);
        $prop->delete();
        return response()->json(['message' => 'Propiedad eliminada']);
    }
}