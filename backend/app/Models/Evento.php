<?php

namespace App\Models;

use App\Models\Concerns\TieneAdjunto;
use App\Support\Html;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

// Un evento de la agenda cultural. Distinto de Actividad: una actividad es
// portafolio (lo que el artista ya hizo); un evento es agenda (lo que va a pasar).
class Evento extends Model
{
    use TieneAdjunto;

    const COLECCION_ADJUNTO = 'imagen';

    const CARPETA_ADJUNTO = 'eventos';

    // formato de "hora de pared": la app corre en UTC y no queremos conversiones
    const FORMATO_FECHA = 'Y-m-d H:i';

    // las fechas guardadas son hora local de la ciudad; "hoy" se calcula en esta zona
    const ZONA = 'America/Lima';

    protected $fillable = [
        'titulo',
        'descripcion',
        'fecha_inicio',
        'fecha_fin',
        'modalidad',
        'lugar',
        'direccion',
        'enlace',
        'codigo_comision',
        'flag_gratuito',
        'costo',
        'organizador_tipo',
        'persona_id',
        'agrupacion_id',
        'estado',
        'observacion',
        'revisado_por',
        'revisado_en',
        'flag_destacado',
        'creado_por',
    ];

    protected $appends = ['imagen_url', 'imagen_nombre_original'];

    protected $with = ['adjunto'];

    protected $hidden = ['adjunto'];

    protected function casts(): array
    {
        return [
            'fecha_inicio' => 'datetime:' . self::FORMATO_FECHA,
            'fecha_fin' => 'datetime:' . self::FORMATO_FECHA,
            'revisado_en' => 'datetime',
            'flag_gratuito' => 'boolean',
            'flag_destacado' => 'boolean',
            'costo' => 'decimal:2',
        ];
    }

    // ÚNICO criterio de "aparece en la agenda pública": cancelados, pendientes
    // y observados no se muestran
    public function scopePublicado(Builder $query): void
    {
        $query->where('estado', 'aprobado');
    }

    // eventos que se SUPERPONEN con [desde, hasta]: un festival que empezó el
    // mes pasado y sigue este mes también tiene que aparecer
    public function scopeEnRango(Builder $query, string $desde, string $hasta): void
    {
        $query->where('fecha_inicio', '<=', "{$hasta} 23:59:59")
            ->where(fn ($q) => $q
                ->where('fecha_fin', '>=', "{$desde} 00:00:00")
                ->orWhere(fn ($q) => $q->whereNull('fecha_fin')->where('fecha_inicio', '>=', "{$desde} 00:00:00")));
    }

    // de hoy en adelante (incluye los que están en curso: un festival que sigue)
    public function scopeProximos(Builder $query): void
    {
        $hoy = now(self::ZONA)->toDateString() . ' 00:00:00';

        $query->where(fn ($q) => $q
            ->where('fecha_fin', '>=', $hoy)
            ->orWhere(fn ($q) => $q->whereNull('fecha_fin')->where('fecha_inicio', '>=', $hoy)));
    }

    // el link público es /agenda/{id}-{slug}: se busca por id y el slug es solo
    // para que se lea (si cambia el título, los links ya compartidos siguen andando)
    public function getSlugAttribute(): string
    {
        return Str::slug($this->titulo) ?: 'evento';
    }

    public function getRutaPublicaAttribute(): string
    {
        return "{$this->id}-{$this->slug}";
    }

    public function comision()
    {
        return $this->belongsTo(Comision::class, 'codigo_comision', 'codigo');
    }

    public function persona()
    {
        return $this->belongsTo(Persona::class);
    }

    public function agrupacion()
    {
        return $this->belongsTo(Agrupacion::class);
    }

    public function creador()
    {
        return $this->belongsTo(User::class, 'creado_por');
    }

    // quién figura como organizador en el portal
    public function organizador(): array
    {
        return match ($this->organizador_tipo) {
            'persona' => ['tipo' => 'persona', 'nombre' => $this->persona?->nombre_completo, 'slug' => $this->persona?->slug],
            'agrupacion' => ['tipo' => 'agrupacion', 'nombre' => $this->agrupacion?->nombre, 'slug' => $this->agrupacion?->slug],
            default => ['tipo' => 'municipalidad', 'nombre' => null, 'slug' => null],
        };
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
