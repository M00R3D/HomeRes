<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Log;
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

        try { Log::entry('usuario', 'Bloqueo ' . ($new ? 'activado' : 'removido') . ' para usuario #' . $u->id, $actor->id, 'usuario', $u->id, route('users.show', $u->id)); } catch (\Throwable $e) {}

        if ($request->wantsJson()) return response()->json(['bloqueo_tarjetas' => (bool)$new, 'intentos_cvv' => $u->intentos_cvv]);

        return redirect()->route('users.index')->with('success', $new ? 'Pagos con tarjeta bloqueados para usuario' : 'Bloqueo de pagos con tarjeta removido');
    }

    public function store(Request $request)
    {
        $actor = $request->user();
        if (! $actor || ($actor->rol ?? '') !== 'admin') {
            return response()->json(['message' => 'No autorizado'], 403);
        }

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

        try { Log::entry('usuario', 'Usuario creado: #' . $user->id . ' ' . ($user->email ?? ''), auth()->id(), 'usuario', $user->id, route('users.show', $user->id)); } catch (\Throwable $e) {}

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

        try { Log::entry('usuario', 'Usuario actualizado: #' . $u->id, $actor->id ?? auth()->id(), 'usuario', $u->id, route('users.show', $u->id)); } catch (\Throwable $e) {}

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
        $actor = $request->user();
        if (! $actor || ($actor->rol ?? '') !== 'admin') {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        $u = User::find($id);
        if (!$u) {
            if ($request->wantsJson()) return response()->json(['message' => 'Usuario no encontrado'], 404);
            abort(404);
        }

        $uid = $u->id;
        $u->delete();
        try { Log::entry('usuario', 'Usuario eliminado: #' . $uid, auth()->id(), 'usuario', $uid, route('users.show', $uid)); } catch (\Throwable $e) {}

        if ($request->wantsJson()) return response()->json(['message' => 'Usuario eliminado']);

        return redirect()->route('users.index')->with('success', 'Usuario eliminado');
    }
    public function toggleBan(Request $request, $id)
    {
        $actor = $request->user();
        if (! $actor || ($actor->rol ?? '') !== 'admin') {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        $u = User::find($id);
        if (! $u) return response()->json(['message' => 'Usuario no encontrado'], 404);

        // No se puede banear a otro admin ni a uno mismo
        if (($u->rol ?? '') === 'admin') {
            if ($request->wantsJson()) return response()->json(['message' => 'No se puede banear a un administrador'], 403);
            return redirect()->back()->withErrors(['ban' => 'No se puede banear a un administrador.']);
        }
        if ($u->id === $actor->id) {
            if ($request->wantsJson()) return response()->json(['message' => 'No puedes banearte a ti mismo'], 403);
            return redirect()->back()->withErrors(['ban' => 'No puedes banearte a ti mismo.']);
        }

        $new = ! (bool) ($u->baneado ?? false);

        DB::table('usuarios')->where('id', $u->id)->update(['baneado' => $new]);
        $u->refresh();

        try { Log::entry('usuario', 'Ban ' . ($new ? 'aplicado' : 'removido') . ' para usuario #' . $u->id, $actor->id, 'usuario', $u->id, route('users.show', $u->id)); } catch (\Throwable $e) {}

        if ($request->wantsJson()) return response()->json(['baneado' => (bool)$new]);

        return redirect()->back()->with('success', $new ? 'Usuario baneado correctamente.' : 'Ban removido correctamente.');
    }

    public function changeRol(Request $request, $id)
    {
        $actor = $request->user();
        if (! $actor || ($actor->rol ?? '') !== 'admin') {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        $u = User::find($id);
        if (!$u) return response()->json(['message' => 'Usuario no encontrado'], 404);

        $request->validate([
            'rol' => 'required|in:admin,recepcionista,cliente',
            'area' => 'nullable|string|max:100',
        ]);

        $u->rol = $request->rol;
        if ($request->filled('area')) $u->area = $request->area;
        $u->save();

        try { Log::entry('usuario', 'Rol cambiado para usuario #' . $u->id . ' a ' . $u->rol, auth()->id(), 'usuario', $u->id, route('users.show', $u->id)); } catch (\Throwable $e) {}

        return response()->json($u);
    }
}