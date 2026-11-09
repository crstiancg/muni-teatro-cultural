<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\ImageManager;

class Archivo extends Model
{
    // Prioridad: que la imagen se vea igual que la original. 2400 px alcanza para
    // el visor a pantalla completa y JPEG 90 es indistinguible a la vista.
    private const MAX_LADO = 2400;

    private const CALIDAD_JPEG = 90;

    // una foto por debajo de esto no se recomprime (cada pasada JPEG pierde algo)
    private const MAX_BYTES_SIN_TOCAR = 1_500_000;

    // la compresión fuerte va solo en la miniatura de los listados
    private const MAX_LADO_MINIATURA = 600;

    private const CALIDAD_MINIATURA = 82;

    protected $appends = ['url', 'miniatura_url'];

    protected $fillable = [
        'coleccion',
        'disco',
        'path',
        'path_miniatura',
        'nombre_original',
        'mime_type',
        'tamano',
    ];

    // un registro de archivos sin su archivo físico (o al revés) no sirve,
    // así que al borrar el registro se borra también del disco
    protected static function booted(): void
    {
        static::deleting(function (Archivo $archivo) {
            if (! static::esExterna($archivo->path)) {
                Storage::disk($archivo->disco)->delete(array_filter([$archivo->path, $archivo->path_miniatura]));
            }
        });
    }

    // Único punto por donde entra un archivo subido. Las imágenes solo se tocan
    // si pesan o miden de más: se achican a 2400 px (JPEG 90, o PNG sin pérdida
    // para no arruinar transparencias) y se corrige la rotación de las fotos de
    // celular (EXIF). Siempre se genera una miniatura para los listados.
    // El resto (PDF de certificados) se guarda tal cual.
    public static function datosDesdeSubida(UploadedFile $archivo, string $carpeta): array
    {
        $datos = [
            'disco' => 'public',
            'nombre_original' => $archivo->getClientOriginalName(),
        ];

        if (! in_array($archivo->getMimeType(), ['image/jpeg', 'image/png', 'image/webp'])) {
            return [
                ...$datos,
                'path' => $archivo->store($carpeta, 'public'),
                'mime_type' => $archivo->getMimeType(),
                'tamano' => $archivo->getSize(),
            ];
        }

        $imagenes = ImageManager::gd(autoOrientation: true);
        $base = $carpeta . '/' . Str::random(40);
        $imagen = $imagenes->read($archivo->getRealPath());
        $esPng = $archivo->getMimeType() === 'image/png';

        $cabe = max($imagen->width(), $imagen->height()) <= self::MAX_LADO;
        $orientada = ($imagen->exif('IFD0.Orientation') ?? 1) === 1;

        if ($cabe && $orientada && $archivo->getSize() <= self::MAX_BYTES_SIN_TOCAR) {
            // ya está bien: se guarda el original, byte por byte
            $extension = $esPng ? 'png' : ($archivo->getMimeType() === 'image/webp' ? 'webp' : 'jpg');
            $path = "{$base}.{$extension}";
            Storage::disk('public')->putFileAs($carpeta, $archivo, basename($path));
            $mime = $archivo->getMimeType();
            $tamano = $archivo->getSize();
        } else {
            $imagen->scaleDown(self::MAX_LADO, self::MAX_LADO);
            $codificada = $esPng ? $imagen->toPng() : $imagen->toJpeg(self::CALIDAD_JPEG);
            $path = $base . ($esPng ? '.png' : '.jpg');
            Storage::disk('public')->put($path, (string) $codificada);
            $mime = $esPng ? 'image/png' : 'image/jpeg';
            $tamano = strlen((string) $codificada);
        }

        $miniatura = $imagenes->read($archivo->getRealPath())
            ->scaleDown(self::MAX_LADO_MINIATURA, self::MAX_LADO_MINIATURA)
            ->toJpeg(self::CALIDAD_MINIATURA);
        Storage::disk('public')->put("{$base}-min.jpg", (string) $miniatura);

        return [
            ...$datos,
            'path' => $path,
            'path_miniatura' => "{$base}-min.jpg",
            'mime_type' => $mime,
            'tamano' => $tamano,
        ];
    }

    public function archivable()
    {
        return $this->morphTo();
    }

    public function getUrlAttribute(): ?string
    {
        return $this->resolverUrl($this->path);
    }

    // si no hay miniatura (PDF, URL externa, archivo viejo) se usa el original
    public function getMiniaturaUrlAttribute(): ?string
    {
        return $this->resolverUrl($this->path_miniatura) ?? $this->url;
    }

    private function resolverUrl(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        // los seeders de prueba guardan URLs externas (picsum) en vez de un archivo
        if (static::esExterna($path)) {
            return $path;
        }

        // mismo criterio que el resto de los modelos: asset() toma el host del
        // request, en vez de APP_URL como hace Storage::url() en el disco public
        if ($this->disco === 'public') {
            return asset('storage/' . $path);
        }

        return Storage::disk($this->disco)->url($path);
    }

    private static function esExterna(?string $path): bool
    {
        return Str::startsWith($path ?? '', ['http://', 'https://']);
    }
}
