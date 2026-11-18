<?php

namespace App\Support;

use App\Models\Archivo;
use App\Models\Comision;
use App\Models\Persona;
use App\Models\Ubigeo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\ImageManager;

// Arma lo que dice la ficha de registro (PDF) y lo que muestra la verificación
// pública del QR. Un solo lugar para que el papel y la web nunca se contradigan.
final class FichaRegistro
{
    private const NIVELES = [
        'egresado' => 'Egresado', 'tecnico' => 'Técnico', 'bachiller' => 'Bachiller',
        'titulado' => 'Titulado', 'maestria' => 'Maestría', 'doctorado' => 'Doctorado',
    ];

    private const TIPOS_FORMACION = [
        'tecnica_basica' => 'Técnica básica', 'tecnica_superior' => 'Técnica superior',
        'universitaria' => 'Universitaria',
    ];

    private const TIPOS_CAPACITACION = [
        'especializacion' => 'Especialización', 'curso' => 'Curso', 'taller' => 'Taller',
        'seminario' => 'Seminario', 'conferencias' => 'Conferencia', 'reconocimiento' => 'Reconocimiento',
        'capacitacion' => 'Capacitación', 'otros' => 'Otro',
    ];

    // la ficha es un resumen de una hoja A4: el resto va como "y N más en su portafolio"
    private const MAX_ITEMS = 3;

    public const TIPO_AGENTE = 'Persona natural / artista individual';

    public static function estado(Persona $persona): string
    {
        return match (true) {
            $persona->registroVigente() => 'Vigente',
            $persona->estado === 'pendiente' => 'En revisión',
            default => 'No vigente',
        };
    }

    // "Comision especializada de musica" (así viene de la base) -> "Música"
    public static function nombreComision(?Comision $comision): ?string
    {
        if (! $comision) {
            return null;
        }

        return config("registro.comisiones.{$comision->cod_grupo}") ?? Str::ucfirst(Str::lower($comision->nombre));
    }

    // Lo mínimo para confirmar que la ficha es auténtica. Sin DNI, contacto ni
    // motivo de observación: lo ve cualquiera que escanee el QR.
    public static function verificacion(Persona $persona): array
    {
        $vigente = $persona->registroVigente();
        $persona->loadMissing('comision', 'foto');

        return [
            'codigo' => $persona->codigo_registro,
            'nombre_completo' => $persona->nombre_completo,
            'tipo_agente' => self::TIPO_AGENTE,
            'comision' => self::nombreComision($persona->comisionPrincipal()),
            'especialidad' => $persona->comision?->nombre,
            'vigente' => $vigente,
            'estado' => self::estado($persona),
            'registrado_en' => $persona->registrado_en?->toDateString(),
            // la foto y el perfil ya son públicos solo mientras está aprobado
            'foto_url' => $vigente ? $persona->foto?->miniatura_url : null,
            'perfil_slug' => $vigente ? $persona->slug : null,
        ];
    }

    public static function datos(Persona $persona): array
    {
        $persona->loadMissing([
            'comision', 'comisionAlternativo', 'foto', 'ubigeoResidencia', 'agrupaciones:id,nombre',
            'formacionesAcademicas' => fn ($q) => $q->where('flag_activo', true)->orderByDesc('fecha_expedicion'),
            'capacitaciones' => fn ($q) => $q->where('flag_activo', true)->orderByDesc('fecha'),
            'actividades' => fn ($q) => $q->where('flag_activo', true)->where('flag_publico', true)->latest()->latest('id'),
        ]);

        $vigente = $persona->registroVigente();
        $ultimaAprobacion = $persona->revisiones()->with('usuario:id,name')->where('accion', 'aprobado')->first();

        return [
            'config' => config('registro'),
            'logo' => self::logo(),
            'codigo' => $persona->codigo_registro,
            'vigente' => $vigente,
            'estado' => self::estado($persona),
            'persona' => $persona,
            'foto' => self::fotoIncrustada($persona->foto),
            'distrito' => self::lugar($persona->ubigeoResidencia),
            'tipo_agente' => self::TIPO_AGENTE,
            'comision' => self::nombreComision($persona->comisionPrincipal()),
            'especialidad' => $persona->comision?->nombre,
            'comision_alternativa' => $persona->comisionAlternativo?->nombre,
            'agrupaciones' => $persona->agrupaciones->pluck('nombre'),

            // filas de tabla, no texto pegado: cada dato en su columna
            'formaciones' => $persona->formacionesAcademicas->take(self::MAX_ITEMS)->map(fn ($f) => [
                'nivel' => collect([self::TIPOS_FORMACION[$f->tipo] ?? null, self::NIVELES[$f->nivel_alcanzado] ?? null])->filter()->implode(' · '),
                'carrera' => $f->profesion,
                'institucion' => $f->centro_estudios,
                'anio' => $f->fecha_expedicion?->format('Y'),
            ]),
            'total_formaciones' => $persona->formacionesAcademicas->count(),
            'capacitaciones' => $persona->capacitaciones->take(self::MAX_ITEMS)->map(fn ($c) => [
                'tipo' => self::TIPOS_CAPACITACION[$c->tipo] ?? Str::ucfirst($c->tipo),
                'nombre' => $c->nombre_evento,
                'institucion' => $c->centro_estudios,
                'horas' => $c->horas,
                'anio' => $c->fecha?->format('Y'),
            ]),
            'total_capacitaciones' => $persona->capacitaciones->count(),
            'actividades' => $persona->actividades->take(self::MAX_ITEMS)->map(fn ($a) => [
                'titulo' => $a->titulo ?: 'Actividad sin título',
                'anio' => $a->created_at?->format('Y'),
            ]),
            'total_actividades' => $persona->actividades->count(),
            'trayectoria_desde' => self::trayectoriaDesde($persona),

            'portafolio_url' => $vigente ? Seo::url("/artistas/{$persona->slug}") : null,
            'fecha_inscripcion' => $persona->registrado_en,
            'fecha_validacion' => $ultimaAprobacion?->created_at,
            'validado_por' => $ultimaAprobacion?->usuario?->name,
            'ultima_actualizacion' => $persona->updated_at,
            'emitido_en' => now(),
            'verificacion_url' => $persona->verificacionUrl(),
            'qr' => QrCode::svg($persona->verificacionUrl()),
        ];
    }

    // Logo del Consejo (copia achicada del de fronted/public/images: el backend
    // no puede depender de la carpeta del front, se despliegan por separado)
    private static function logo(): ?string
    {
        $path = resource_path('images/logo-cpcp.png');

        return is_file($path) ? 'data:image/png;base64,' . base64_encode(file_get_contents($path)) : null;
    }

    // año del certificado más antiguo: la trayectoria que el registro respalda con papeles
    private static function trayectoriaDesde(Persona $persona): ?string
    {
        return $persona->formacionesAcademicas->pluck('fecha_expedicion')
            ->merge($persona->capacitaciones->pluck('fecha'))
            ->filter()
            ->min()
            ?->format('Y');
    }

    // "Puno, Puno, Puno": distrito, provincia y departamento
    private static function lugar(?Ubigeo $distrito): ?string
    {
        if (! $distrito) {
            return null;
        }

        $superiores = Ubigeo::whereIn('codigo', [
            "{$distrito->cod_dep}{$distrito->cod_prov}00",
            "{$distrito->cod_dep}0000",
        ])->orderByDesc('codigo')->pluck('nombre');

        return collect([$distrito->nombre])->merge($superiores)->implode(', ');
    }

    // dompdf no baja imágenes por HTTP: va incrustada. Se recodifica a JPEG chico
    // porque las fotos viejas pueden ser WebP (dompdf no siempre lo soporta).
    private static function fotoIncrustada(?Archivo $foto): ?string
    {
        $path = $foto?->path_miniatura ?: $foto?->path;
        if (! $path || Str::startsWith($path, ['http://', 'https://'])) {
            return null;
        }

        $disco = Storage::disk($foto->disco);
        if (! $disco->exists($path)) {
            return null;
        }

        try {
            // recorte centrado 3:4, proporción de foto carnet: así no se deforma en el recuadro
            $jpeg = ImageManager::gd()->read($disco->get($path))->cover(300, 400)->toJpeg(85);
        } catch (\Throwable) {
            return null;
        }

        return 'data:image/jpeg;base64,' . base64_encode((string) $jpeg);
    }
}
