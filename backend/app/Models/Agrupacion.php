<?php

namespace App\Models;

use App\Notifications\AvisoAgrupacion;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

class Agrupacion extends Model
{
    protected $table = 'agrupaciones';

    protected $fillable = [
        'nombre',
        'slug',
        'codigo_comision',
        'descripcion',
        'redes_sociales',
        'estado',
        'observacion',
        'revisado_por',
        'revisado_en',
    ];

    protected function casts(): array
    {
        return [
            'redes_sociales' => 'array',
            'revisado_en' => 'datetime',
        ];
    }

    // el slug se fija al crear y no cambia: los links compartidos siguen andando
    protected static function booted(): void
    {
        static::creating(function (Agrupacion $agrupacion) {
            if ($agrupacion->slug) {
                return;
            }
            $base = Str::slug($agrupacion->nombre) ?: 'agrupacion';
            $slug = $base;
            $intento = 2;
            while (static::where('slug', $slug)->exists()) {
                $slug = "{$base}-{$intento}";
                $intento++;
            }
            $agrupacion->slug = $slug;
        });

        // la tabla archivos es polimórfica (sin FK): logo, portada y las imágenes
        // de sus actividades se borran a mano (uno por uno, para limpiar el disco)
        static::deleting(function (Agrupacion $agrupacion) {
            $agrupacion->archivos()->get()->each->delete();
            $agrupacion->actividades()->get()->each->delete();
        });
    }

    // ---------- relaciones ----------

    public function integrantes()
    {
        return $this->hasMany(AgrupacionIntegrante::class)
            ->orderByDesc('es_representante')
            ->orderBy('apellido_paterno');
    }

    public function comision()
    {
        return $this->belongsTo(Comision::class, 'codigo_comision', 'codigo');
    }

    public function archivos()
    {
        return $this->morphMany(Archivo::class, 'archivable');
    }

    public function logo()
    {
        return $this->morphOne(Archivo::class, 'archivable')->where('coleccion', 'logo');
    }

    public function portada()
    {
        return $this->morphOne(Archivo::class, 'archivable')->where('coleccion', 'portada');
    }

    public function actividades()
    {
        return $this->hasMany(AgrupacionActividad::class)->latest();
    }

    public function revisiones()
    {
        return $this->hasMany(RevisionAgrupacion::class)->latest()->latest('id');
    }

    // ---------- reglas ----------

    // mismo criterio que Persona::publicado: solo lo aprobado y con comisión sale al portal
    public function scopePublicado(Builder $query): void
    {
        $query->where('agrupaciones.estado', 'aprobado')->whereNotNull('agrupaciones.codigo_comision');
    }

    public function esRepresentante(?Persona $persona): bool
    {
        return $persona && $this->integrantes()
            ->where('persona_id', $persona->id)
            ->where('es_representante', true)
            ->exists();
    }

    // ÚNICA definición de "lista para enviar": checklist del panel y validación del envío
    public function requisitos(): array
    {
        $this->loadMissing('logo');

        return [
            ['clave' => 'comision', 'label' => 'Comisión a la que pertenece', 'obligatorio' => true,
                'cumple' => (bool) $this->codigo_comision],
            ['clave' => 'descripcion', 'label' => 'Descripción de la agrupación', 'obligatorio' => true,
                'cumple' => filled($this->descripcion)],
            ['clave' => 'logo', 'label' => 'Logo o foto de la agrupación', 'obligatorio' => true,
                'cumple' => (bool) $this->logo],
            ['clave' => 'integrantes', 'label' => 'Al menos otro integrante además del representante', 'obligatorio' => true,
                'cumple' => $this->integrantes()->count() > 1],
            ['clave' => 'redes', 'label' => 'Al menos una red social', 'obligatorio' => false,
                'cumple' => filled($this->redes_sociales)],
        ];
    }

    public function reemplazarLogo(UploadedFile $imagen): Archivo
    {
        $this->eliminarLogo();

        return $this->archivos()->create([
            'coleccion' => 'logo',
            ...Archivo::datosDesdeSubida($imagen, 'agrupaciones/logos'),
        ]);
    }

    // uno por uno para que el evento deleting de Archivo borre el disco
    public function eliminarLogo(): void
    {
        $this->archivos()->where('coleccion', 'logo')->get()->each->delete();
    }

    // imagen horizontal de cabecera en la página pública
    public function reemplazarPortada(UploadedFile $imagen): Archivo
    {
        $this->eliminarPortada();

        return $this->archivos()->create([
            'coleccion' => 'portada',
            ...Archivo::datosDesdeSubida($imagen, 'agrupaciones/portadas'),
        ]);
    }

    public function eliminarPortada(): void
    {
        $this->archivos()->where('coleccion', 'portada')->get()->each->delete();
    }

    // Una agrupación PUBLICADA que cambia datos o integrantes vuelve a revisión:
    // sale del portal hasta que el admin la apruebe (el front avisa antes de guardar).
    // En borrador, observada o pendiente no hace nada.
    public function volverARevision(string $motivo, ?int $userId): void
    {
        if ($this->estado !== 'aprobado') {
            return;
        }

        $this->update(['estado' => 'pendiente', 'observacion' => null]);
        $this->revisiones()->create(['accion' => 'enviado', 'observacion' => $motivo, 'user_id' => $userId]);
        $this->notificarAdmins('agrupacion_solicitud', "La agrupación {$this->nombre} volvió a revisión: {$motivo}");
    }

    // pasa la representación a otro integrante, que tiene que ser artista registrado
    // (necesita cuenta para gestionarla) y no superar el límite de agrupaciones
    public function transferirRepresentante(AgrupacionIntegrante $nuevo): void
    {
        abort_unless($nuevo->agrupacion_id === $this->id, 404);
        abort_if($nuevo->es_representante, 422, 'Ya es el representante.');
        abort_unless($nuevo->persona_id, 422, 'Solo un artista registrado puede ser representante: necesita una cuenta para gestionar la agrupación.');

        $limite = config('agrupaciones.max_por_representante');
        $representadas = AgrupacionIntegrante::where('persona_id', $nuevo->persona_id)->where('es_representante', true)->count();
        abort_if($representadas >= $limite, 422, "Esa persona ya representa el máximo de {$limite} agrupaciones.");

        $this->integrantes()->where('es_representante', true)->update(['es_representante' => false]);
        $nuevo->update(['es_representante' => true]);

        $nuevo->persona?->user?->notify(new AvisoAgrupacion(
            'agrupacion_representante',
            "Ahora eres el representante de la agrupación {$this->nombre}.",
            $this
        ));
    }

    // ---------- avisos ----------

    public function notificarAdmins(string $tipo, string $mensaje): void
    {
        Notification::send(
            User::permission('admin-agrupaciones-aprobar')->get(),
            new AvisoAgrupacion($tipo, $mensaje, $this)
        );
    }

    public function notificarRepresentantes(string $tipo, string $mensaje): void
    {
        $usuarios = $this->integrantes()
            ->where('es_representante', true)
            ->whereNotNull('persona_id')
            ->with('persona.user')
            ->get()
            ->map(fn ($i) => $i->persona?->user)
            ->filter();

        Notification::send($usuarios, new AvisoAgrupacion($tipo, $mensaje, $this));
    }
}
