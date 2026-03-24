<?php

namespace App\Http\Middleware;

use App\Models\Log;
use Closure;
use Illuminate\Http\Request;

class OperationAuditMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        try {
            $method = strtoupper((string) $request->method());
            if (!in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
                return $response;
            }

            $statusCode = method_exists($response, 'getStatusCode') ? (int) $response->getStatusCode() : 200;
            if ($statusCode < 400) {
                return $response;
            }

            $route = $request->route();
            $uri = $route ? ((string) $route->uri()) : $request->path();
            $actorId = auth()->id();
            $action = strtolower($method) . '_request';

            $message = 'Operacion fallida (' . $statusCode . ')';
            if (method_exists($response, 'getContent')) {
                $raw = (string) $response->getContent();
                if ($raw !== '') {
                    $message .= ': ' . mb_substr(strip_tags($raw), 0, 220);
                }
            }

            Log::entry(
                $action,
                $uri ?: 'http',
                $actorId,
                null,
                'error',
                $message,
                $request->fullUrl()
            );
        } catch (\Throwable $e) {
            // never block request lifecycle
        }

        return $response;
    }
}
