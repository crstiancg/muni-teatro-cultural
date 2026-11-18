<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

// Red de seguridad de la baja de usuarios: al dar de baja se revocan todos sus
// tokens (User::cerrarTodasLasSesiones), pero si por cualquier camino quedara
// uno vivo, acá se corta igual y se revocan los que queden. Va en todo el grupo
// "api": cubre también las rutas que se agreguen mañana.
class CuentaActiva
{
    public function handle(Request $request, Closure $next): Response
    {
        $usuario = $request->user('api');

        if ($usuario?->de_baja) {
            $usuario->cerrarTodasLasSesiones();

            return response()->json([
                'message' => 'Tu cuenta fue dada de baja. Comunícate con la Gerencia de Cultura y Turismo.',
                'cuenta_de_baja' => true,
            ], 401);
        }

        return $next($request);
    }
}
