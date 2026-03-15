<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $users = User::with('tarjeta')->get();

        if ($request->wantsJson()) {
            return response()->json($users);
        }

        return view('users.index', ['users' => $users]);
    }

    public function toggleBloqueo(Request $request, $id)
    {
        $actor = $request->user();
        if (! $actor || ($actor->rol ?? '') !== 'admin') {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        $u = User::find($id);
        if (! $u) return response()->json(['message' => 'Usuario no encontrado'], 404);

        $new = ! (bool) ($u->bloqueo_tarjetas ?? false);

        // persist directly to avoid any model event interference
        DB::table('usuarios')->where('id', $u->id)->update([
            'bloqueo_tarjetas' => $new,
            'intentos_cvv' => $new ? ($u->intentos_cvv ?? 0) : 0,
        ]);

        // reload model for response
        $u->refresh();

        if ($request->wantsJson()) return response()->json(['bloqueo_tarjetas' => (bool)$new, 'intentos_cvv' => $u->intentos_cvv]);

        return redirect()->route('users.index')->with('success', $new ? 'Pagos con tarjeta bloqueados para usuario' : 'Bloqueo de pagos con tarjeta removido');
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
        $actor = $request->user();
        if (! $actor || ($actor->rol ?? '') !== 'admin') {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        $request->validate([
            'nombre' => 'sometimes|string|max:100',
            'apellido' => 'nullable|string|max:100',
            'email' => 'nullable|email|unique:usuarios,email,'.$id,
            'password' => 'nullable|string|min:6|confirmed',
            'rol' => 'nullable|in:admin,recepcionista,cliente',
            'area' => 'nullable|string|max:100',
            'id_tarjeta' => 'nullable|exists:tarjetas_simuladas,id',
            'bloqueo_tarjetas' => 'nullable|boolean',
            'intentos_cvv' => 'nullable|integer|min:0',
        ]);

        $update = $request->only(['nombre','apellido','email','password','rol','area','id_tarjeta','bloqueo_tarjetas','intentos_cvv']);
        if (empty($update['password'])) {
            unset($update['password']);
        }

        // persist bloqueo/intentos directly to avoid model mutators side-effects
        $direct = [];
        if (array_key_exists('bloqueo_tarjetas', $update)) {
            $direct['bloqueo_tarjetas'] = (bool) $update['bloqueo_tarjetas'];
            unset($update['bloqueo_tarjetas']);
        }
        if (array_key_exists('intentos_cvv', $update)) {
            $direct['intentos_cvv'] = (int) $update['intentos_cvv'];
            unset($update['intentos_cvv']);
        }

        $u->update($update);

        if (!empty($direct)) {
            DB::table('usuarios')->where('id', $u->id)->update($direct);
        }

        if ($request->wantsJson()) return response()->json($u);

        return redirect()->route('users.index')->with('success', 'Usuario actualizado');
    }

    public function edit(Request $request, $id)
    {
        $actor = $request->user();
        if (! $actor || ($actor->rol ?? '') !== 'admin') {
            abort(403);
        }

        $u = User::with('tarjeta')->find($id);
        if (! $u) abort(404);

        $tarjetas = \App\Models\TarjetaSimulada::orderBy('numero_tarjeta')->get();
        return view('users.edit', ['user' => $u, 'tarjetas' => $tarjetas]);
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