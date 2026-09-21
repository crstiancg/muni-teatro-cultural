<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

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
        return static::resolverImagenUrl($this->imagen_path);
    }

    // los seeders de prueba guardan URLs externas (picsum) en vez de un archivo
    // subido, asi que las devolvemos tal cual en vez de prefijarlas con storage/
    public static function resolverImagenUrl(?string $imagenPath): ?string
    {
        if (! $imagenPath) {
            return null;
        }

        if (Str::startsWith($imagenPath, ['http://', 'https://'])) {
            return $imagenPath;
        }

        return asset('storage/' . $imagenPath);
    }
}
