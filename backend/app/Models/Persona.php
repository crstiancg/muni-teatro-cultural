<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Notifications\AvisoPerfil;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use App\Support\Html;

class Persona extends Model
{
    /** @use HasFactory<\Database\Factories\PersonaFactory> */
    use HasFactory;

    protected $fillable = [
        'dni',
        'nombre',
        'apellido_paterno',
        'apellido_materno',
        'nombre_completo',
        'slug',
        'genero',
        'fecha_nacimiento',
        'direccion',
        'estado_civil',
        'celular',
        'celular_emergencia',
        'correo',
        'ubigeo_cod_nacimiento',
        'ubigeo_cod_residencia',
        'codigo_comision',
        'codigo_comision_alternativo',
        'user_id',
        'biografia',
        'redes_sociales',
        'estado',
        'observacion',
        'revisado_por',
        'revisado_en',
    ];

    public const REDES = ['facebook', 'instagram', 'tiktok', 'youtube', 'web'];

    protected function casts(): array
    {
        return [
            'redes_sociales' => 'array',
            'revisado_en' => 'datetime',
        ];
    }

    // ÚNICO criterio de "aparece en el portal": todo PersonaPublicaController
    // pasa por acá para que ningún listado se salte la aprobación
    public function scopePublicado(Builder $query): void
    {
        $query->where('personas.estado', 'aprobado')->whereNotNull('personas.codigo_comision');
    }

    public static function biografiaLimpia(?string $html): ?string
    {
        return Html::limpio($html);
    }

    // ÚNICA definición de "perfil completo": la usan el checklist del dashboard
    // del artista y la validación de enviarRevision. Obligatorio = bloquea el envío.
    public function requisitosPerfil(): array
    {
        $this->loadMissing('foto');

        return [
            ['clave' => 'foto', 'label' => 'Foto de perfil', 'obligatorio' => true,
                'cumple' => (bool) $this->foto],
            ['clave' => 'comision', 'label' => 'Comisión a la que perteneces', 'obligatorio' => true,
                'cumple' => (bool) $this->codigo_comision],
            ['clave' => 'biografia', 'label' => 'Sobre tu trabajo', 'obligatorio' => true,
                'cumple' => filled($this->biografia)],
            ['clave' => 'actividad', 'label' => 'Al menos una actividad pública', 'obligatorio' => true,
                'cumple' => $this->actividades()->where('flag_activo', true)->where('flag_publico', true)->exists()],
            ['clave' => 'redes', 'label' => 'Al menos una red social', 'obligatorio' => false,
                'cumple' => filled($this->redes_sociales)],
            ['clave' => 'trayectoria', 'label' => 'Formación o capacitación', 'obligatorio' => false,
                'cumple' => $this->formacionesAcademicas()->where('flag_activo', true)->exists()
                    || $this->capacitaciones()->where('flag_activo', true)->exists()],
        ];
    }

    // Suma una visita al día de hoy. Una misma persona (IP + navegador) cuenta
    // una vez cada 30 min: recargar la página no infla el número.
    public function registrarVisita(string $visitante): void
    {
        if (! Cache::add("visita:{$this->id}:" . sha1($visitante), true, now()->addMinutes(30))) {
            return;
        }

        DB::table('visitas_perfil')->upsert(
            [['persona_id' => $this->id, 'fecha' => today()->toDateString(), 'visitas' => 1, 'created_at' => now(), 'updated_at' => now()]],
            ['persona_id', 'fecha'],
            ['visitas' => DB::raw('visitas + 1'), 'updated_at' => now()]
        );
    }

    // para el dashboard del artista: esta semana vs la anterior y los últimos 14 días
    public function resumenVisitas(): array
    {
        $porDia = DB::table('visitas_perfil')
            ->where('persona_id', $this->id)
            ->where('fecha', '>=', today()->subDays(13)->toDateString())
            ->pluck('visitas', 'fecha');

        // se completan los días sin visitas con 0: el gráfico no puede saltearse días
        $dias = collect(range(13, 0))->map(function ($atras) use ($porDia) {
            $fecha = today()->subDays($atras)->toDateString();

            return ['fecha' => $fecha, 'visitas' => (int) ($porDia[$fecha] ?? 0)];
        });

        return [
            'semana' => $dias->slice(7)->sum('visitas'),
            'semana_anterior' => $dias->slice(0, 7)->sum('visitas'),
            'total' => (int) DB::table('visitas_perfil')->where('persona_id', $this->id)->sum('visitas'),
            'dias' => $dias->values(),
        ];
    }

    public function notificarAdmins(string $tipo, string $mensaje): void
    {
        // a quien PUEDE aprobar (por rol o directo), no a un rol fijo
        Notification::send(
            User::permission('admin-personas-aprobar')->get(),
            new AvisoPerfil($tipo, $mensaje, $this)
        );
    }

    // el slug se arma acá y no en el controller para que valga por cualquier vía
    // de creación (admin, seeder, factory). Solo en "creating": si después
    // corrigen el nombre, la URL ya compartida sigue funcionando.
    protected static function booted(): void
    {
        static::creating(function (Persona $persona) {
            $persona->slug ??= static::generarSlug($persona->nombre_completo);
        });

        // si ya figuraba como integrante de alguna agrupación (cargado por su DNI),
        // al registrarse queda vinculado y en su perfil aparece "Integra: ..."
        static::created(function (Persona $persona) {
            AgrupacionIntegrante::where('dni', $persona->dni)
                ->whereNull('persona_id')
                ->update(['persona_id' => $persona->id]);
        });
    }

    public static function generarSlug(?string $nombreCompleto): string
    {
        $base = Str::slug($nombreCompleto ?? '') ?: 'artista';
        $slug = $base;
        $intento = 2;

        // solo los homónimos pagan el sufijo; el resto queda con la URL limpia
        while (static::where('slug', $slug)->exists()) {
            $slug = "{$base}-{$intento}";
            $intento++;
        }

        return $slug;
    }

    //relacion eloquent uno a uno

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function ubigeoNacimiento()
    {
        return $this->belongsTo(Ubigeo::class, 'ubigeo_cod_nacimiento', 'codigo');
    }

    public function ubigeoResidencia()
    {
        return $this->belongsTo(Ubigeo::class, 'ubigeo_cod_residencia', 'codigo');
    }

    public function comision()
    {
        return $this->belongsTo(Comision::class, 'codigo_comision', 'codigo');
    }

    public function comisionAlternativo()
    {
        return $this->belongsTo(Comision::class, 'codigo_comision_alternativo', 'codigo');
    }

    public function formacionesAcademicas()
    {
        return $this->hasMany(FormacionAcademica::class);
    }

    public function capacitaciones()
    {
        return $this->hasMany(Capacitacion::class);
    }

    public function actividades()
    {
        return $this->hasMany(Actividad::class);
    }

    public function archivos()
    {
        return $this->morphMany(Archivo::class, 'archivable');
    }

    // agrupaciones donde figura como integrante (vinculado por su DNI)
    public function agrupaciones()
    {
        return $this->belongsToMany(Agrupacion::class, 'agrupacion_integrantes')
            ->withPivot(['id', 'rol', 'es_representante'])
            ->withTimestamps();
    }

    // historial del flujo de revisión, lo más reciente primero
    public function revisiones()
    {
        return $this->hasMany(RevisionPerfil::class)->latest()->latest('id');
    }

    public function foto()
    {
        return $this->morphOne(Archivo::class, 'archivable')->where('coleccion', 'foto');
    }

    // vive acá y no en el controller porque la usan el admin y "Mi Perfil".
    // Una persona tiene una sola foto: la anterior se borra (registro + disco).
    public function reemplazarFoto(UploadedFile $imagen): Archivo
    {
        $this->eliminarFoto();

        return $this->archivos()->create([
            'coleccion' => 'foto',
            ...Archivo::datosDesdeSubida($imagen, 'personas/fotos'),
        ]);
    }

    // uno por uno (y no delete() masivo) para que corra el evento deleting de
    // Archivo y se borre también el archivo del disco
    public function eliminarFoto(): void
    {
        $this->archivos()->where('coleccion', 'foto')->get()->each->delete();
    }
}
