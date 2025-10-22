<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Str;

class UserController extends Controller
{
    public function index()
    {
        return response()->json(User::all());
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:100',
            'apellido' => 'nullable|string|max:100',
            'email' => 'nullable|email|unique:usuarios,email',
            'password' => 'required|string|min:6',
            'rol' => 'nullable|in:admin,recepcionista,cliente',
            'area' => 'nullable|string|max:100',
        ]);

        $data = $request->only(['nombre','apellido','email','password','rol','area']);
        $user = User::create($data);

        return response()->json($user, 201);
    }

    public function show($id)
    {
        $u = User::find($id);
        if (!$u) return response()->json(['message' => 'Usuario no encontrado'], 404);
        return response()->json($u);
    }

    public function update(Request $request, $id)
    {
        $u = User::find($id);
        if (!$u) return response()->json(['message' => 'Usuario no encontrado'], 404);

        $request->validate([
            'nombre' => 'sometimes|string|max:100',
            'apellido' => 'nullable|string|max:100',
            'email' => 'nullable|email|unique:usuarios,email,'.$id,
            'password' => 'nullable|string|min:6',
            'rol' => 'nullable|in:admin,recepcionista,cliente',
            'area' => 'nullable|string|max:100',
        ]);

        $u->update($request->only(['nombre','apellido','email','password','rol','area']));
        return response()->json($u);
    }

    public function destroy($id)
    {
        $u = User::find($id);
        if (!$u) return response()->json(['message' => 'Usuario no encontrado'], 404);
        $u->delete();
        return response()->json(['message' => 'Usuario eliminado']);
    }

    // Cambiar rol/area (edición específica de enums/valores)
    public function changeRol(Request $request, $id)
    {
        $u = User::find($id);
        if (!$u) return response()->json(['message' => 'Usuario no encontrado'], 404);

        $request->validate([
            'rol' => 'required|in:admin,recepcionista,cliente',
            'area' => 'nullable|string|max:100',
        ]);

        $u->rol = $request->rol;
        if ($request->filled('area')) $u->area = $request->area;
        $u->save();

        return response()->json($u);
    }
}