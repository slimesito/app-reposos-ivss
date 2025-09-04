<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class DirectorUser
{
    public function handle(Request $request, Closure $next)
    {
        // Verifica si el usuario está autenticado y si su COD_CARGO es 2
        if (Auth::check() && Auth::user()->cod_cargo == 2) {
            return $next($request);
        }

        // Si no es Director de Centro Asistencial, redirige al usuario a la vista de Error
        return redirect()->route('error404');
    }
}
