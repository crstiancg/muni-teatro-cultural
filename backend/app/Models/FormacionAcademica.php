<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FormacionAcademica extends Model
{
    protected $appends = ['archivo_url'];

    protected $fillable = [
        'persona_id',
        'tipo',
        'nivel_alcanzado',
        'centro_estudios',
        'profesion',
        'folio',
        'fecha_expedicion',
        'archivo_path',
        'archivo_nombre_original',
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

    public function archivos()
    {
        return $this->morphMany(Archivo::class, 'archivable');
    }

    public function getArchivoUrlAttribute()
    {
        return $this->archivo_path ? asset('storage/' . $this->archivo_path) : null;
    }
}
