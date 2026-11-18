<?php

namespace Tests\Feature;

use App\Models\Archivo;
use App\Models\Persona;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

// Cada imagen subida tiene dos versiones WebP para la web: miniatura (600 px,
// listados) y media (1200 px, tarjetas grandes de la portada en pantallas 2x).
// El original queda intacto (ficha PDF, descargas, vista previa en redes).
// Medido con fotos reales: la media pesa ~67 % menos que el original en JPEG.
class MiniaturasWebpTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    private function esWebp(string $bytes): bool
    {
        return str_starts_with($bytes, 'RIFF') && substr($bytes, 8, 4) === 'WEBP';
    }

    private function ladoMayor(string $bytes): int
    {
        [$ancho, $alto] = getimagesizefromstring($bytes);

        return max($ancho, $alto);
    }

    // foto vieja: solo el original (como las subidas antes de las versiones WebP)
    private function fotoVieja(string $path = 'personas/fotos/vieja.jpg'): Archivo
    {
        Storage::disk('public')->put($path, (string) UploadedFile::fake()->image('vieja.jpg', 2400, 1600)->get());

        return Archivo::forceCreate([
            'archivable_type' => Persona::class, 'archivable_id' => 1, 'coleccion' => 'foto',
            'disco' => 'public', 'path' => $path, 'path_miniatura' => null, 'path_media' => null,
            'nombre_original' => 'vieja.jpg', 'mime_type' => 'image/jpeg', 'tamano' => 1000,
        ]);
    }

    public function test_una_foto_nueva_guarda_sus_dos_versiones_webp_y_el_original_intacto(): void
    {
        $datos = Archivo::datosDesdeSubida(UploadedFile::fake()->image('foto.jpg', 3000, 2000), 'personas/fotos');

        $miniatura = Storage::disk('public')->get($datos['path_miniatura']);
        $this->assertStringEndsWith('-min.webp', $datos['path_miniatura']);
        $this->assertTrue($this->esWebp($miniatura), 'la miniatura no es un WebP real');
        $this->assertLessThanOrEqual(600, $this->ladoMayor($miniatura));

        $media = Storage::disk('public')->get($datos['path_media']);
        $this->assertStringEndsWith('-med.webp', $datos['path_media']);
        $this->assertTrue($this->esWebp($media), 'la versión media no es un WebP real');
        $this->assertSame(1200, $this->ladoMayor($media));

        // el original sigue en JPEG: lo usan la ficha PDF, las descargas y las redes
        $this->assertStringEndsWith('.jpg', $datos['path']);
    }

    public function test_media_url_cae_al_original_si_la_foto_todavia_no_tiene_version_media(): void
    {
        $archivo = $this->fotoVieja();

        $this->assertSame($archivo->url, $archivo->media_url);
    }

    public function test_el_comando_completa_las_versiones_de_las_fotos_viejas_y_no_repite_trabajo(): void
    {
        $archivo = $this->fotoVieja();

        $this->artisan('imagenes:miniaturas')->expectsOutputToContain('1 imágenes optimizadas')->assertSuccessful();

        $archivo->refresh();
        $this->assertSame('personas/fotos/vieja-min.webp', $archivo->path_miniatura);
        $this->assertSame('personas/fotos/vieja-med.webp', $archivo->path_media);
        $this->assertTrue($this->esWebp(Storage::disk('public')->get($archivo->path_media)));
        Storage::disk('public')->assertExists('personas/fotos/vieja.jpg'); // el original no se toca

        $this->artisan('imagenes:miniaturas')->expectsOutputToContain('0 imágenes optimizadas')->assertSuccessful();
    }

    public function test_el_comando_pasa_a_webp_las_miniaturas_jpeg_y_borra_la_vieja(): void
    {
        $archivo = $this->fotoVieja();
        Storage::disk('public')->put('personas/fotos/vieja-min.jpg', 'jpeg viejo');
        $archivo->update(['path_miniatura' => 'personas/fotos/vieja-min.jpg']);

        $this->artisan('imagenes:miniaturas')->assertSuccessful();

        $this->assertSame('personas/fotos/vieja-min.webp', $archivo->refresh()->path_miniatura);
        Storage::disk('public')->assertMissing('personas/fotos/vieja-min.jpg');
    }

    public function test_al_borrar_la_imagen_se_borran_tambien_sus_versiones(): void
    {
        $archivo = $this->fotoVieja();
        $this->artisan('imagenes:miniaturas');

        $archivo->refresh()->delete();

        Storage::disk('public')->assertMissing(['personas/fotos/vieja.jpg', 'personas/fotos/vieja-min.webp', 'personas/fotos/vieja-med.webp']);
    }

    public function test_el_comando_no_toca_pdfs_ni_se_rompe_con_archivos_perdidos(): void
    {
        $pdf = Archivo::forceCreate([
            'archivable_type' => Persona::class, 'archivable_id' => 1, 'coleccion' => 'certificado',
            'disco' => 'public', 'path' => 'certificados/c.pdf',
            'nombre_original' => 'c.pdf', 'mime_type' => 'application/pdf', 'tamano' => 10,
        ]);
        Storage::disk('public')->put('certificados/c.pdf', '%PDF-1.4');
        $perdida = Archivo::forceCreate([
            'archivable_type' => Persona::class, 'archivable_id' => 1, 'coleccion' => 'foto',
            'disco' => 'public', 'path' => 'personas/fotos/no-existe.jpg',
            'nombre_original' => 'x.jpg', 'mime_type' => 'image/jpeg', 'tamano' => 10,
        ]);

        $this->artisan('imagenes:miniaturas')->assertSuccessful();

        $this->assertNull($pdf->refresh()->path_media);
        $this->assertNull($perdida->refresh()->path_media);
    }

    public function test_la_api_de_eventos_expone_la_version_media_para_el_afiche(): void
    {
        $evento = \App\Models\Evento::create([
            'titulo' => 'Recital', 'fecha_inicio' => '2026-11-14 20:00', 'modalidad' => 'presencial',
            'lugar' => 'Teatro Municipal', 'organizador_tipo' => 'municipalidad', 'estado' => 'aprobado',
        ]);
        $evento->archivos()->create(['coleccion' => \App\Models\Evento::COLECCION_ADJUNTO,
            ...Archivo::datosDesdeSubida(UploadedFile::fake()->image('afiche.jpg', 1800, 2400), 'eventos')]);

        $this->getJson("/api/publico/eventos/{$evento->id}")
            ->assertOk()
            ->assertJsonPath('data.imagen_media_url', fn ($url) => str_ends_with((string) $url, '-med.webp'));
    }
}
