<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Throwable;

// Consulta de DNI en RENIEC vía apis.net.pe. La usan el alta de personas (admin)
// y la carga de integrantes de una agrupación (representante).
class Reniec
{
    // ['dni', 'nombre', 'apellido_paterno', 'apellido_materno'] o aborta con 404/503
    public static function consultar(string $dni): array
    {
        abort_unless(config('services.apis_net_pe.token'), 500, 'Falta configurar APIS_NET_PE_TOKEN en el .env.');

        // cada consulta cuesta cuota: un DNI encontrado se guarda 30 días
        if ($guardado = Cache::get("reniec:{$dni}")) {
            return $guardado;
        }

        try {
            $respuesta = Http::withToken(config('services.apis_net_pe.token'))
                ->acceptJson()
                ->connectTimeout(5)
                ->timeout(8)
                ->get(config('services.apis_net_pe.url') . '/v2/reniec/dni', ['numero' => $dni]);
        } catch (Throwable) {
            abort(503, 'No se pudo conectar con RENIEC. Completa los datos a mano.');
        }

        abort_if(in_array($respuesta->status(), [404, 422]), 404, 'El DNI no figura en RENIEC.');
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
