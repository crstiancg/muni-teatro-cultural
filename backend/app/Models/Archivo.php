<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Archivo extends Model
{
    protected $appends = ['url'];

    protected $fillable = [
        'coleccion',
        'disco',
        'path',
        'nombre_original',
        'mime_type',
        'tamano',
    ];

    // un registro de archivos sin su archivo físico (o al revés) no sirve,
    // así que al borrar el registro se borra también del disco
    protected static function booted(): void
    {
        static::deleting(function (Archivo $archivo) {
            if (! Str::startsWith($archivo->path, ['http://', 'https://'])) {
                Storage::disk($archivo->disco)->delete($archivo->path);
            }
        });
    }

    public function archivable()
    {
        return $this->morphTo();
    }

    // los seeders de prueba guardan URLs externas (picsum) en vez de un archivo
    // subido, asi que las devolvemos tal cual en vez de pedirle la URL al disco
    public function getUrlAttribute(): ?string
    {
        if (! $this->path) {
            return null;
        }

        if (Str::startsWith($this->path, ['http://', 'https://'])) {
            return $this->path;
        }

        // mismo criterio que el resto de los modelos: asset() toma el host del
        // request, en vez de APP_URL como hace Storage::url() en el disco public
        if ($this->disco === 'public') {
            return asset('storage/' . $this->path);
        }

        return Storage::disk($this->disco)->url($this->path);
    }
}
