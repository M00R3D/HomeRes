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
        if ($request->filled('q')) $q->where('nombre', 'like', '%'.$request->q.'%')->orWhere('codigo', 'like', '%'.$request->q.'%');
        if ($request->filled('max_precio')) $q->where('precio_noche', '<=', (float)$request->max_precio);

        $propiedades = $q->paginate(12);

        return view('propiedades.index', [
            'propiedades' => $propiedades,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'tipo' => 'required|in:cabaña,casa,departamento',
            'codigo' => 'required|string|unique:propiedades,codigo',
            'nombre' => 'required|string',
            'descripcion' => 'nullable|string',
            'capacidad' => 'required|integer|min:1',
            'precio_noche' => 'required|numeric|min:0',
            'ubicacion' => 'nullable|string',
            'servicios' => 'nullable|string',
            'estado' => 'nullable|in:disponible,ocupada,mantenimiento',
            'ruta_img' => 'nullable|string',
        ]);

        Propiedad::create($validated);

        return redirect()->route('propiedades.index')->with('success', 'Propiedad creada exitosamente.');
    }

    public function show($id)
    {
        $propiedad = Propiedad::findOrFail($id);
        return view('propiedades.show', ['propiedad' => $propiedad]);
    }

    public function update(Request $request, $id)
    {
        $propiedad = Propiedad::findOrFail($id);

        $validated = $request->validate([
            'tipo' => 'required|in:cabaña,casa,departamento',
            'codigo' => 'required|string|unique:propiedades,codigo,'.$id,
            'nombre' => 'required|string',
            'descripcion' => 'nullable|string',
            'capacidad' => 'required|integer|min:1',
            'precio_noche' => 'required|numeric|min:0',
            'ubicacion' => 'nullable|string',
            'servicios' => 'nullable|string',
            'estado' => 'nullable|in:disponible,ocupada,mantenimiento',
            'ruta_img' => 'nullable|string',
        ]);

        $propiedad->update($validated);

        return redirect()->route('propiedades.index')->with('success', 'Propiedad actualizada exitosamente.');
    }

    public function destroy($id)
    {
        $propiedad = Propiedad::findOrFail($id);
        $propiedad->delete();

        return redirect()->route('propiedades.index')->with('success', 'Propiedad eliminada exitosamente.');
    }
}