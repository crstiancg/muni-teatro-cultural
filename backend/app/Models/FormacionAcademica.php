<?php

namespace App\Models;

use App\Models\Concerns\TieneAdjunto;
use Illuminate\Database\Eloquent\Model;

class FormacionAcademica extends Model
{
    use TieneAdjunto;

    const COLECCION_ADJUNTO = 'certificado';

    const CARPETA_ADJUNTO = 'formacion_academica';

    protected $appends = ['archivo_url', 'archivo_nombre_original'];

    // siempre se necesita para archivo_url; oculto porque ya sale aplanado
    protected $with = ['adjunto'];

    protected $hidden = ['adjunto'];

    protected $fillable = [
        'persona_id',
        'tipo',
        'nivel_alcanzado',
        'centro_estudios',
        'profesion',
        'folio',
        'fecha_expedicion',
        'flag_activo',
    ];

    protected function casts(): array
    {
        return [
            'fecha_expedicion' => 'date',
            'flag_activo' => 'boolean',
        ];
    }

    protected function serializeDate(\DateTimeInterface $date): string
    {
        return $date->format('Y-m-d');
    }

    public function persona()
    {
        return $this->belongsTo(Persona::class);
    }

    // mismos nombres que cuando eran columnas: el front no cambia
    public function getArchivoUrlAttribute(): ?string
    {
        return $this->adjunto?->url;
    }

    public function getArchivoNombreOriginalAttribute(): ?string
    {
        return $this->adjunto?->nombre_original;
    }
}
