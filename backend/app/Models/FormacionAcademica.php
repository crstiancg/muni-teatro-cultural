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

    public function persona()
    {
        return $this->belongsTo(Persona::class);
    }

    public function getArchivoUrlAttribute()
    {
        return $this->archivo_path ? asset('storage/' . $this->archivo_path) : null;
    }
}
