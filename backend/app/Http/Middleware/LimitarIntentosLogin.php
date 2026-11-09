<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

// Passport trae "throttle" genérico en /oauth/token (60/min por IP). Como la
// clave inicial es el DNI (8 dígitos), se limita además por cuenta: 5 intentos
// fallidos por minuto por correo + IP. Un login correcto reinicia el contador.
// Cubre POST /api/login (el que usa el front) y /oauth/token directo.
class LimitarIntentosLogin
{
    private const MAX_INTENTOS = 5;

    private const BLOQUEO_SEGUNDOS = 60;

    public function handle(Request $request, Closure $next): Response
    {
        $usuario = match (true) {
            $request->is('api/login') => $request->input('email'),
            $request->is('oauth/token') && $request->input('grant_type') === 'password'
                // la subpetición de AuthController ya se contó en api/login
                && ! $request->attributes->get('login_interno') => $request->input('username'),
            default => null,
        };

        if ($usuario === null) {
            return $next($request);
        }

        $clave = 'login:' . Str::lower((string) $usuario) . '|' . $request->ip();

        if (RateLimiter::tooManyAttempts($clave, self::MAX_INTENTOS)) {
            $segundos = RateLimiter::availableIn($clave);

            return response()->json([
                'message' => "Demasiados intentos. Vuelve a intentarlo en {$segundos} segundos.",
            ], 429)->header('Retry-After', $segundos);
        }

        $respuesta = $next($request);

        if ($respuesta->isSuccessful()) {
            RateLimiter::clear($clave);
        } else {
            RateLimiter::hit($clave, self::BLOQUEO_SEGUNDOS);
        }

        return $respuesta;
    }
}
