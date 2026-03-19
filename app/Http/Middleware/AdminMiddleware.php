<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ($user->rol ?? '') !== 'admin') {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Debes ser administrador para acceder a este recurso.'], 403);
            }

            return response()->view('errors.403', [
                'message' => 'Debes ser administrador para acceder a esta seccion.',
            ], 403);
        }

        return $next($request);
    }
}
