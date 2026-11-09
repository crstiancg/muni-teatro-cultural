<?php

namespace App\Models;

use App\Models\Concerns\TieneAdjunto;
use App\Support\Html;
use Illuminate\Database\Eloquent\Model;

// mismos campos y accessors que Actividad: el front reutiliza ActividadGallery/Form
class AgrupacionActividad extends Model
{
    use TieneAdjunto;

    const COLECCION_ADJUNTO = 'imagen';

    const CARPETA_ADJUNTO = 'agrupaciones/actividades';

    protected $table = 'agrupacion_actividades';

    protected $appends = ['imagen_url', 'imagen_nombre_original', 'descripcion_texto'];

    protected $with = ['adjunto'];

    protected $hidden = ['adjunto'];

    protected $fillable = ['agrupacion_id', 'titulo', 'descripcion', 'flag_activo', 'flag_publico'];

    protected function casts(): array
    {
        return [
            'flag_activo' => 'boolean',
            'flag_publico' => 'boolean',
        ];
    }

    // la tabla archivos es polimórfica (sin FK): la imagen se borra a mano
    protected static function booted(): void
    {
        static::deleting(fn (AgrupacionActividad $actividad) => $actividad->eliminarAdjunto());
    }

    public function agrupacion()
    {
        return $this->belongsTo(Agrupacion::class);
    }

    public function getImagenUrlAttribute(): ?string
    {
        return $this->adjunto?->url;
    }

    public function getImagenMiniaturaUrlAttribute(): ?string
    {
        return $this->adjunto?->miniatura_url;
    }

    public function getImagenNombreOriginalAttribute(): ?string
    {
        return $this->adjunto?->nombre_original;
    }

    public function getDescripcionTextoAttribute(): string
    {
        return Html::texto($this->descripcion);
    }
}
