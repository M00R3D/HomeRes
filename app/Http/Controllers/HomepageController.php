<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Homepage;

class HomepageController extends Controller
{
    public function index(Request $request)
    {
        $hp = Homepage::orderByDesc('id')->first();
        if ($request->wantsJson()) return response()->json($hp);
        return view('homepage.index', ['homepage' => $hp]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'banner_image' => 'nullable|string|max:255',
            'image_folder' => 'nullable|string|max:255',
            'ubicacion' => 'nullable|string|max:255',
            'eslogan' => 'nullable|string|max:255',
            'nombre_empresa' => 'nullable|string|max:255',
        ]);

        $hp = Homepage::create($data);

        if ($request->wantsJson()) return response()->json($hp, 201);
        return redirect()->back()->with('success','Homepage creada');
    }

    public function show(Request $request, $id)
    {
        $hp = Homepage::find($id);
        if (!$hp) return $request->wantsJson() ? response()->json(['message'=>'No encontrado'],404) : abort(404);
        if ($request->wantsJson()) return response()->json($hp);
        return view('homepage.show', ['homepage' => $hp]);
    }

    public function update(Request $request, $id)
    {
        $hp = Homepage::find($id);
        if (!$hp) return $request->wantsJson() ? response()->json(['message'=>'No encontrado'],404) : abort(404);

        $data = $request->validate([
            'banner_image' => 'nullable|string|max:255',
            'image_folder' => 'nullable|string|max:255',
            'ubicacion' => 'nullable|string|max:255',
            'eslogan' => 'nullable|string|max:255',
            'nombre_empresa' => 'nullable|string|max:255',
        ]);

        $hp->update($data);

        if ($request->wantsJson()) return response()->json($hp);
        return redirect()->back()->with('success','Homepage actualizada');
    }

    public function destroy(Request $request, $id)
    {
        $hp = Homepage::find($id);
        if (!$hp) return $request->wantsJson() ? response()->json(['message'=>'No encontrado'],404) : abort(404);
        $hp->delete();
        if ($request->wantsJson()) return response()->json(['message'=>'Eliminado']);
        return redirect()->back()->with('success','Homepage eliminada');
    }
}