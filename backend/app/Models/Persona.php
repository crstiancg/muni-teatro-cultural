<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

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
        'user_id'
    ];

    // el slug se arma acá y no en el controller para que valga por cualquier vía
    // de creación (admin, seeder, factory). Solo en "creating": si después
    // corrigen el nombre, la URL ya compartida sigue funcionando.
    protected static function booted(): void
    {
        static::creating(function (Persona $persona) {
            $persona->slug ??= static::generarSlug($persona->nombre_completo);
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
}
