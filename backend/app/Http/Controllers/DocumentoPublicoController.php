<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

// Resoluciones y ordenanzas del Consejo (config/documentos.php). El navegador no
// puede leerlas directo de portal.munipuno.gob.pe ni de gob.pe (CORS) y en Android
// un PDF en iframe no se ve: se traen acá, se guardan y se sirven desde el sitio.
class DocumentoPublicoController extends Controller
{
    // Tope por archivo: una resolución pesa cientos de KB; esto evita guardar
    // cualquier cosa enorme si el portal responde algo inesperado.
    private const MAX_BYTES = 30 * 1024 * 1024;

    public function index()
    {
        return response()->json(
            collect(config('documentos.lista'))->map(fn ($doc, $slug) => [
                'slug' => $slug,
                'titulo' => $doc['titulo'],
                'fecha' => $doc['fecha'],
                'descripcion' => $doc['descripcion'],
                'url_original' => $doc['url'],
            ])->values()
        );
    }

    public function show(Request $request, string $slug)
    {
        $doc = config("documentos.lista.{$slug}");
        abort_unless($doc, 404);

        $disco = Storage::disk('local');
        $ruta = "documentos/{$slug}.pdf";

        $vencido = ! $disco->exists($ruta)
            || $disco->lastModified($ruta) < now()->subDays(config('documentos.cache_dias'))->getTimestamp();

        if ($vencido && $pdf = $this->bajar($slug, $doc['url'])) {
            $disco->put($ruta, $pdf);
        }

        // si el portal se cayó se sigue sirviendo la última copia buena
        abort_unless($disco->exists($ruta), 502, 'No se pudo obtener el documento del portal oficial. Intenta más tarde.');

        $disposicion = $request->boolean('descargar') ? 'attachment' : 'inline';

        return response($disco->get($ruta), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => "{$disposicion}; filename=\"{$slug}.pdf\"",
            'Cache-Control' => 'public, max-age=86400',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    // null si falla: nunca se guarda una página de error HTML como si fuera el PDF
    private function bajar(string $slug, string $url): ?string
    {
        try {
            $respuesta = Http::timeout(20)->retry(2, 300, throw: false)->get($url);
        } catch (\Throwable $e) {
            Log::warning("Documento {$slug}: no se pudo conectar con el portal ({$e->getMessage()})");

            return null;
        }

        $cuerpo = $respuesta->body();
        if (! $respuesta->successful() || ! str_starts_with($cuerpo, '%PDF') || strlen($cuerpo) > self::MAX_BYTES) {
            Log::warning("Documento {$slug}: el portal no devolvió un PDF válido (HTTP {$respuesta->status()})");

            return null;
        }

        return $cuerpo;
    }
}
