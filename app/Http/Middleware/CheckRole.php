<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  mixed ...$roles
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        if (! $request->user()) {
            return response()->json(['message' => 'No autenticado.'], 401);
        }

        $userRole = $request->user()->rol ?? null;

        if (! $userRole || ! in_array($userRole, $roles)) {
            return response()->json([
                'message' => 'Acceso denegado. No tienes los permisos necesarios para realizar esta acción.'
            ], 403);
        }

        return $next($request);
    }
}
