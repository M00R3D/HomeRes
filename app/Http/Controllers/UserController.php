<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $users = User::all();

        if ($request->wantsJson()) {
            return response()->json($users);
        }

        return view('users.index', ['users' => $users]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:100',
            'apellido' => 'nullable|string|max:100',
            'email' => 'nullable|email|unique:usuarios,email',
            'password' => 'required|string|min:6|confirmed',
            'rol' => 'nullable|in:admin,recepcionista,cliente',
            'area' => 'nullable|string|max:100',
        ]);
        $data = $request->only(['nombre','apellido','email','password','rol','area']);
        $user = User::create($data);

        if ($request->wantsJson()) {
            return response()->json($user, 201);
        }

        return redirect()->route('users.index')->with('success', 'Usuario creado');
    }

    public function show(Request $request, $id)
    {
        $u = User::find($id);
        if (!$u) {
            if ($request->wantsJson()) return response()->json(['message' => 'Usuario no encontrado'], 404);
            abort(404);
        }

        if ($request->wantsJson()) return response()->json($u);
        return view('users.show', ['user' => $u]); // opcional vista show
    }

    public function update(Request $request, $id)
    {
        $u = User::find($id);
        if (!$u) {
            if ($request->wantsJson()) return response()->json(['message' => 'Usuario no encontrado'], 404);
            abort(404);
        }

        $request->validate([
            'nombre' => 'sometimes|string|max:100',
            'apellido' => 'nullable|string|max:100',
            'email' => 'nullable|email|unique:usuarios,email,'.$id,
            'password' => 'nullable|string|min:6|confirmed',
            'rol' => 'nullable|in:admin,recepcionista,cliente',
            'area' => 'nullable|string|max:100',
        ]);

        $update = $request->only(['nombre','apellido','email','password','rol','area']);
        if (empty($update['password'])) {
            unset($update['password']);
        }

        $u->update($update);

        if ($request->wantsJson()) return response()->json($u);

        return redirect()->route('users.index')->with('success', 'Usuario actualizado');
    }

    public function destroy(Request $request, $id)
    {
        $u = User::find($id);
        if (!$u) {
            if ($request->wantsJson()) return response()->json(['message' => 'Usuario no encontrado'], 404);
            abort(404);
        }

        $u->delete();

        if ($request->wantsJson()) return response()->json(['message' => 'Usuario eliminado']);

        return redirect()->route('users.index')->with('success', 'Usuario eliminado');
    }
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