<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

// Resoluciones y ordenanzas del Consejo: el portal las aloja en otros dominios,
// que no permiten leerlas desde el navegador (CORS) y en Android no se ven en un
// iframe. El backend las trae de una lista blanca, las guarda y las sirve desde
// el dominio del sitio para el visor del front.
class DocumentosPublicosTest extends TestCase
{
    use RefreshDatabase;

    private const PDF = "%PDF-1.4\nresolucion de prueba";

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    public function test_lista_los_documentos_oficiales_sin_exponer_nada_mas(): void
    {
        $this->getJson('/api/publico/documentos')
            ->assertOk()
            ->assertJsonCount(3)
            ->assertJsonPath('0.slug', 'ra-277-2024')
            ->assertJsonPath('0.titulo', 'Resolución de Alcaldía N.° 277-2024-MPP/A')
            ->assertJsonStructure([['slug', 'titulo', 'fecha', 'descripcion', 'url_original']]);
    }

    public function test_sirve_el_pdf_desde_el_dominio_del_sitio_y_no_lo_pide_dos_veces(): void
    {
        Http::fake(['*' => Http::response(self::PDF, 200, ['Content-Type' => 'application/pdf'])]);

        $respuesta = $this->get('/api/publico/documentos/ra-0571-2025')->assertOk();
        $this->assertSame('application/pdf', $respuesta->headers->get('Content-Type'));
        $this->assertStringStartsWith('inline', $respuesta->headers->get('Content-Disposition'));
        $this->assertSame(self::PDF, $respuesta->getContent());

        $this->get('/api/publico/documentos/ra-0571-2025')->assertOk();
        Http::assertSentCount(1);
    }

    public function test_con_descargar_baja_el_archivo_en_vez_de_mostrarlo(): void
    {
        Http::fake(['*' => Http::response(self::PDF)]);

        $respuesta = $this->get('/api/publico/documentos/ra-277-2024?descargar=1')->assertOk();

        $this->assertStringStartsWith('attachment', $respuesta->headers->get('Content-Disposition'));
    }

    public function test_un_documento_fuera_de_la_lista_blanca_no_se_descarga_de_ningun_lado(): void
    {
        Http::fake();

        $this->get('/api/publico/documentos/cualquier-cosa')->assertNotFound();

        Http::assertNothingSent();
    }

    public function test_si_el_portal_devuelve_una_pagina_de_error_no_la_guarda_como_pdf(): void
    {
        Http::fake(['*' => Http::response('<html>Página no encontrada</html>', 200)]);

        $this->getJson('/api/publico/documentos/om-094-2026')->assertStatus(502);

        Storage::disk('local')->assertMissing('documentos/om-094-2026.pdf');
    }

    public function test_si_el_portal_se_cae_sigue_sirviendo_la_copia_guardada(): void
    {
        Http::fake(['*' => Http::sequence()
            ->push(self::PDF)
            ->push('error', 500)
            ->push('error', 500)
            ->push('error', 500)]);

        $this->get('/api/publico/documentos/ra-277-2024')->assertOk();
        $this->travel(30)->days();

        $this->get('/api/publico/documentos/ra-277-2024')->assertOk()->assertContent(self::PDF);
    }
}
