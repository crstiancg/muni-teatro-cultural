<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AgrupacionIntegrante extends Model
{
    protected $table = 'agrupacion_integrantes';

    protected $fillable = [
        'agrupacion_id',
        'dni',
        'nombre',
        'apellido_paterno',
        'apellido_materno',
        'rol',
        'persona_id',
        'es_representante',
    ];

    protected $appends = ['nombre_completo'];

    protected function casts(): array
    {
        return ['es_representante' => 'boolean'];
    }

    // si el DNI es de un artista registrado, se vincula solo al guardar
    protected static function booted(): void
    {
        static::saving(function (AgrupacionIntegrante $integrante) {
            if ($integrante->isDirty('dni') || ! $integrante->persona_id) {
                $integrante->persona_id = Persona::where('dni', $integrante->dni)->value('id');
            }
        });
    }

    public function agrupacion()
    {
        return $this->belongsTo(Agrupacion::class);
    }

    public function persona()
    {
        return $this->belongsTo(Persona::class);
    }

    public function getNombreCompletoAttribute(): string
    {
        return trim("{$this->nombre} {$this->apellido_paterno} {$this->apellido_materno}");
    }
}
