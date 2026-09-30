<?php

namespace App\Models\Concerns;

use App\Models\Archivo;
use Illuminate\Http\UploadedFile;

// Un archivo adjunto por registro, guardado en la tabla polimórfica "archivos".
// El modelo que lo usa define:
//   const COLECCION_ADJUNTO = 'certificado'; // qué es el archivo
//   const CARPETA_ADJUNTO = 'capacitaciones'; // carpeta dentro del disco public
trait TieneAdjunto
{
    public function archivos()
    {
        return $this->morphMany(Archivo::class, 'archivable');
    }

    public function adjunto()
    {
        return $this->morphOne(Archivo::class, 'archivable')->where('coleccion', static::COLECCION_ADJUNTO);
    }

    // sin archivo no hace nada: en un update, no mandar archivo = conservar el actual
    public function reemplazarAdjunto(?UploadedFile $archivo): void
    {
        if (! $archivo) {
            return;
        }

        $this->eliminarAdjunto();

        $this->archivos()->create([
            'coleccion' => static::COLECCION_ADJUNTO,
            ...Archivo::datosDesdeSubida($archivo, static::CARPETA_ADJUNTO),
        ]);

        // para que la respuesta JSON traiga el adjunto nuevo y no el cacheado
        $this->unsetRelation('adjunto');
    }

    // uno por uno (no delete() masivo) para que el evento deleting de Archivo
    // borre también el archivo del disco
    public function eliminarAdjunto(): void
    {
        $this->archivos()->where('coleccion', static::COLECCION_ADJUNTO)->get()->each->delete();
    }
}
