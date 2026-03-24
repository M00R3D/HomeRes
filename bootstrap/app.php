<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;
use Illuminate\Session\TokenMismatchException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'admin' => \App\Http\Middleware\AdminMiddleware::class,
        ]);
        $middleware->append(\App\Http\Middleware\OperationAuditMiddleware::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (ValidationException $e, $request) {
            try {
                \App\Models\Log::entry(
                    'validate',
                    (string) optional($request->route())->uri() ?: 'http',
                    auth()->id(),
                    null,
                    'error',
                    'Validacion fallida: ' . collect($e->errors())->flatten()->take(3)->implode(' | '),
                    $request->fullUrl()
                );
            } catch (\Throwable $ignored) {
            }
            return null;
        });

        $exceptions->render(function (AuthorizationException $e, $request) {
            try {
                \App\Models\Log::entry(
                    'authorize',
                    (string) optional($request->route())->uri() ?: 'http',
                    auth()->id(),
                    null,
                    'error',
                    'Acceso denegado: ' . ($e->getMessage() ?: 'No autorizado'),
                    $request->fullUrl()
                );
            } catch (\Throwable $ignored) {
            }
            return null;
        });

        $exceptions->render(function (AuthenticationException $e, $request) {
            try {
                \App\Models\Log::entry(
                    'authenticate',
                    (string) optional($request->route())->uri() ?: 'http',
                    null,
                    null,
                    'error',
                    'Intento sin autenticacion en recurso protegido',
                    $request->fullUrl()
                );
            } catch (\Throwable $ignored) {
            }
            return null;
        });

        $exceptions->render(function (TokenMismatchException $e, $request) {
            try {
                \App\Models\Log::entry(
                    'session',
                    (string) optional($request->route())->uri() ?: 'http',
                    auth()->id(),
                    null,
                    'error',
                    'Token CSRF invalido o sesion expirada',
                    $request->fullUrl()
                );
            } catch (\Throwable $ignored) {
            }
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Sesion expirada. Recarga la pagina e intenta nuevamente.',
                ], 419);
            }

            return redirect()
                ->back()
                ->withInput($request->except(['password', 'password_confirmation']))
                ->with('error', 'Tu sesion expiro. Intenta nuevamente.');
        });
    })->create();
