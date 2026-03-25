<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cookie;
use App\Models\User;
use App\Models\Log;

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

            // If "remember" is checked we create a persistent cookie; otherwise
            // create a session cookie (no expiration — cleared when browser closes).
            try {
                if ($request->boolean('remember')) {
                    $minutes = (int) config('session.login_remember_minutes', config('session.lifetime'));

                    Cookie::queue(
                        Cookie::make(
                            config('session.cookie'),
                            session()->getId(),
                            $minutes,
                            config('session.path'),
                            config('session.domain'),
                            config('session.secure'),
                            config('session.http_only'),
                            false,
                            config('session.same_site')
                        )
                    );

                    // store server-side expiry so we can enforce it on every request
                    try {
                        $expiresAt = now()->addMinutes($minutes)->getTimestamp();
                        session(['auth_expires_at' => $expiresAt]);
                    } catch (\Throwable $_e) {
                    }
                } else {
                    // session cookie: minutes = 0 -> no Expires header (cleared when browser closes)
                    Cookie::queue(
                        Cookie::make(
                            config('session.cookie'),
                            session()->getId(),
                            0,
                            config('session.path'),
                            config('session.domain'),
                            config('session.secure'),
                            config('session.http_only'),
                            false,
                            config('session.same_site')
                        )
                    );
                    // remove any previous server-side expiry for session-only logins
                    try {
                        session()->forget('auth_expires_at');
                    } catch (\Throwable $_e) {
                    }
                }
            } catch (\Throwable $e) {
                // non-fatal: if cookie can't be queued, continue normal flow
            }

            $user = Auth::user();
            if ($user->baneado ?? false) {
                try {
                    Log::entry('login', 'usuario', $user->id, $user->id, 'error', 'Inicio de sesion bloqueado por ban', route('login'));
                } catch (\Throwable $e) {
                }
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                return response()->view('auth.account-banned', [
                    'email' => $credentials['email'] ?? null,
                ], 403);
            }

            // For non-remembered logins, create a per-browser session token
            // that must live in sessionStorage. If the browser is closed and
            // reopened, sessionStorage is cleared and the check in the layout
            // will redirect the user to the login page.
            if (! $request->boolean('remember')) {
                try {
                    $token = bin2hex(random_bytes(16));
                    session(['session_browser_token' => $token]);
                } catch (\Throwable $_e) {
                }

                // redirect to intended URL and signal the client to initialize
                // sessionStorage with the token
                $intended = session()->pull('url.intended', route('homepage.index'));
                $sep = str_contains($intended, '?') ? '&' : '?';
                return redirect($intended . $sep . 'session_init=1');
            }

            // Remember logins should not use the browser-only token
            session()->forget('session_browser_token');
            try {
                Log::entry('login', 'usuario', $user?->id, $user?->id, 'success', 'Inicio de sesion exitoso', route('homepage.index'));
            } catch (\Throwable $e) {
            }
            return redirect()->intended(route('homepage.index'));
        }

        // If AJAX / fetch request, return JSON with specific message (email not found vs wrong password)
        $user = \App\Models\User::where('email', $credentials['email'])->first();
        try {
            Log::entry(
                'login',
                'usuario',
                $user?->id,
                $user?->id,
                'error',
                $user ? 'Login fallido: contrasena incorrecta' : 'Login fallido: correo no registrado',
                route('login')
            );
        } catch (\Throwable $e) {
        }
        if ($request->expectsJson() || $request->wantsJson() || $request->ajax()) {
            $msg = $user ? 'Contraseña incorrecta.' : 'No existe una cuenta registrada con ese correo.';
            return response()->json(['message' => $msg], 401);
        }

        $errMsg = $user ? 'Contraseña incorrecta.' : 'No existe una cuenta registrada con ese correo.';
        return back()->withErrors(['email' => $errMsg])->withInput();
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

        try {
            Log::entry('register', 'usuario', auth()->id(), $user->id, 'success', 'Registro de usuario exitoso', route('users.show', $user->id));
        } catch (\Throwable $e) {
        }

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('homepage.index');
    }

    public function logout(Request $request)
    {
        $actorId = auth()->id();
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();
        Cookie::queue(Cookie::forget('remember_web_' . sha1('web')));
        // clear any browser-session token on logout
        try { session()->forget('session_browser_token'); } catch (\Throwable $_e) {}

        try {
            Log::entry('logout', 'usuario', $actorId, $actorId, 'info', 'Cierre de sesion', route('login'));
        } catch (\Throwable $e) {
        }

        return redirect()->route('login');
    }
}