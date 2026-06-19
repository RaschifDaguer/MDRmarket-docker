<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CorsMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Este middleware está deshabilitado para evitar duplicación de CORS.
        // La configuración oficial de Laravel en config/cors.php maneja los headers.
        return $next($request);
    }
}
