<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cookie;
use App\Models\User;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required','email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();

            $user = Auth::user();
            if ($user->baneado ?? false) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                return response()->view('auth.account-banned', [
                    'email' => $credentials['email'] ?? null,
                ], 403);
            }

            return redirect()->intended(route('dashboard'));
        }

        return back()->withErrors(['email' => 'Credenciales inválidas.'])->withInput();
    }

    public function register(Request $request)
    {
        $data = $request->validate([
            'nombre' => 'required|string|max:100',
            'apellido' => 'required|string|max:100',
            'email' => 'required|email|unique:usuarios,email',
            'password' => 'required|string|min:6|confirmed',
        ], [
            'nombre.required' => 'El nombre es obligatorio.',
            'nombre.max' => 'El nombre no puede exceder :max caracteres.',
            'apellido.required' => 'El apellido es obligatorio.',
            'apellido.max' => 'El apellido no puede exceder :max caracteres.',
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'Introduce un correo electrónico válido.',
            'email.unique' => 'El correo electrónico ya está en uso.',
            'password.required' => 'La contraseña es obligatoria.',
            'password.min' => 'La contraseña debe tener al menos :min caracteres.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
        ]);

        $user = User::create([
            'nombre' => $data['nombre'],
            'apellido' => $data['apellido'] ?? null,
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'rol' => 'cliente',
            'area' => null,
        ]);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('dashboard');
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();
        Cookie::queue(Cookie::forget('remember_web_' . sha1('web')));

        return redirect()->route('login');
    }
}