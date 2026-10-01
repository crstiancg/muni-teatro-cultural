<?php

namespace App\Models;

use App\Models\Concerns\TieneAdjunto;
use App\Support\Html;
use Illuminate\Database\Eloquent\Model;

class Actividad extends Model
{
    use TieneAdjunto;

    const COLECCION_ADJUNTO = 'imagen';

    const CARPETA_ADJUNTO = 'actividades';

    protected $table = 'actividades';

    protected $appends = ['imagen_url', 'imagen_nombre_original', 'descripcion_texto'];

    // siempre se necesita para imagen_url; oculto porque ya sale aplanado
    protected $with = ['adjunto'];

    protected $hidden = ['adjunto'];

    protected $fillable = [
        'persona_id',
        'titulo',
        'descripcion',
        'flag_activo',
        'flag_publico',
    ];

    protected function casts(): array
    {
        return [
            'flag_activo' => 'boolean',
            'flag_publico' => 'boolean',
        ];
    }

    public function persona()
    {
        return $this->belongsTo(Persona::class);
    }

    // mismos nombres que cuando eran columnas: el front no cambia.
    // Las URLs externas de los seeders (picsum) las resuelve Archivo::url
    public function getImagenUrlAttribute(): ?string
    {
        return $this->adjunto?->url;
    }

    // para tarjetas y listados: 600 px en vez de la original
    public function getImagenMiniaturaUrlAttribute(): ?string
    {
        return $this->adjunto?->miniatura_url;
    }

    // la descripción sin HTML: para alt de imágenes y vistas previas cortas
    public function getDescripcionTextoAttribute(): string
    {
        return Html::texto($this->descripcion);
    }

    public function getImagenNombreOriginalAttribute(): ?string
    {
        return $this->adjunto?->nombre_original;
    }
}
