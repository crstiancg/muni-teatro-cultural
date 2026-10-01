<?php

namespace App\Http\Controllers;

use App\Models\Persona;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Throwable;

// Autocompleta nombre y apellidos desde RENIEC (apis.net.pe) al registrar una
// persona. Primero mira la base: si el DNI ya está registrado, no consulta afuera.
class ConsultaDniController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return static::permisos('personas', ['crear' => ['show']]);
    }

    public function show(string $dni)
    {
        $persona = Persona::where('dni', $dni)->first(['id', 'nombre_completo']);
        if ($persona) {
            return response()->json(['existe' => true, 'persona' => $persona]);
        }

        abort_unless(config('services.apis_net_pe.token'), 500, 'Falta configurar APIS_NET_PE_TOKEN en el .env.');

        // cada consulta a RENIEC cuesta cuota: un DNI encontrado se guarda 30 días
        $datos = Cache::get("reniec:{$dni}") ?? $this->consultarReniec($dni);

        return response()->json(['existe' => false, ...$datos]);
    }

    private function consultarReniec(string $dni): array
    {
        try {
            $respuesta = Http::withToken(config('services.apis_net_pe.token'))
                ->acceptJson()
                ->connectTimeout(5)
                ->timeout(8)
                ->get(config('services.apis_net_pe.url') . '/v2/reniec/dni', ['numero' => $dni]);
        } catch (Throwable) {
            abort(503, 'No se pudo conectar con RENIEC. Completa los datos a mano.');
        }

        abort_if($respuesta->status() === 404 || $respuesta->status() === 422, 404, 'El DNI no figura en RENIEC.');
        abort_unless($respuesta->successful() && $respuesta->json('nombres'), 503, 'RENIEC no respondió. Completa los datos a mano.');

        $datos = [
            'dni' => $dni,
            'nombre' => $respuesta->json('nombres'),
            'apellido_paterno' => $respuesta->json('apellidoPaterno'),
            'apellido_materno' => $respuesta->json('apellidoMaterno'),
        ];

        Cache::put("reniec:{$dni}", $datos, now()->addDays(30));

        return $datos;
    }
}
