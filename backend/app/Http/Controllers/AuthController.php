<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

// El front manda solo email + contraseña; el client id/secret de Passport se
// agregan acá, así nunca viajan al navegador.
class AuthController extends Controller
{
    public function login(Request $request)
    {
        $datos = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $cliente = config('passport.password_client');
        abort_unless($cliente['id'] && $cliente['secret'], 500, 'Falta configurar el cliente de Passport en el .env.');

        $subpeticion = Request::create('/oauth/token', 'POST', [
            'grant_type' => 'password',
            'client_id' => $cliente['id'],
            'client_secret' => $cliente['secret'],
            'username' => $datos['email'],
            'password' => $datos['password'],
            'scope' => '',
        ], server: ['REMOTE_ADDR' => $request->ip(), 'HTTP_ACCEPT' => 'application/json']);
        // el límite de intentos ya se aplicó a este request: la subpetición no cuenta doble
        $subpeticion->attributes->set('login_interno', true);

        $respuesta = app()->handle($subpeticion);

        return response($respuesta->getContent(), $respuesta->getStatusCode())
            ->header('Content-Type', 'application/json');
    }
}
