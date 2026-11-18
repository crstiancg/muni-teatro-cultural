<?php

namespace App\Console\Commands;

use App\Models\Archivo;
use Illuminate\Console\Command;

// Completa las versiones WebP de las imágenes subidas (miniatura 600 px y media
// 1200 px): las genera si faltan y convierte las miniaturas viejas en JPEG.
// Los originales no se tocan. Idempotente: lo que ya está se saltea. Lo corre el
// entrypoint de Dokploy en cada arranque (dokploy/entrypoint.sh).
class GenerarMiniaturas extends Command
{
    protected $signature = 'imagenes:miniaturas';

    protected $description = 'Genera las versiones WebP (miniatura y media) que faltan de las imágenes subidas';

    public function handle(): int
    {
        $hechas = 0;
        $omitidas = 0;

        Archivo::where('mime_type', 'like', 'image/%')
            ->where(fn ($q) => $q->whereNull('path_media')
                ->orWhereNull('path_miniatura')
                ->orWhere('path_miniatura', 'not like', '%.webp'))
            ->orderBy('id')
            ->chunkById(100, function ($archivos) use (&$hechas, &$omitidas) {
                foreach ($archivos as $archivo) {
                    $archivo->generarVersiones() ? $hechas++ : $omitidas++;
                }
            });

        $this->info("{$hechas} imágenes optimizadas" . ($omitidas ? " ({$omitidas} omitidas: archivo faltante o imagen dañada)" : '') . '.');

        return self::SUCCESS;
    }
}
