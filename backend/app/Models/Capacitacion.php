<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Capacitacion extends Model
{
    protected $table = 'capacitaciones';

    protected $appends = ['archivo_url'];

    protected $fillable = [
        'persona_id',
        'tipo',
        'nombre_evento',
        'centro_estudios',
        'horas',
        'folio',
        'fecha',
        'archivo_path',
        'archivo_nombre_original',
        'flag_activo',
    ];

    protected function casts(): array
    {
        return [
            'fecha' => 'date',
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

    public function archivos()
    {
        return $this->morphMany(Archivo::class, 'archivable');
    }

    public function getArchivoUrlAttribute()
    {
        return $this->archivo_path ? asset('storage/' . $this->archivo_path) : null;
    }
}
