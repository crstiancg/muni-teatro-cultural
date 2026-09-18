<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Actividad extends Model
{
    protected $table = 'actividades';

    protected $appends = ['imagen_url'];

    protected $fillable = [
        'persona_id',
        'descripcion',
        'imagen_path',
        'imagen_nombre_original',
        'flag_activo',
        'flag_publico',
    ];

    protected function casts(): array
    {
        return [
            'flag_activo' => 'boolean',
            'flag_publico' => 'boolean',
        ];
    }

    public function persona()
    {
        return $this->belongsTo(Persona::class);
    }

    public function getImagenUrlAttribute()
    {
        return $this->imagen_path ? asset('storage/' . $this->imagen_path) : null;
    }
}
