<?php

namespace Tests\Feature;

use App\Models\Comision;
use App\Models\Persona;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// Lo que se corrigió en el sistema (marca CPC, logo, nombres cortos de comisión,
// código de registro verificable) tiene que llegar igual a Google y a los
// buscadores con IA: una sola identidad en la web, en el PDF y en el SEO.
class SeoRegistroTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // nombres tal cual quedan en la base (oficiales y largos)
        Comision::create(['codigo' => '0100', 'tipo' => 'grupo', 'cod_grupo' => '01', 'cod_familia' => '00', 'nombre' => 'Comisión especializada de música']);
        Comision::create(['codigo' => '0101', 'tipo' => 'familia', 'cod_grupo' => '01', 'cod_familia' => '01', 'nombre' => 'Conjunto de sikuris']);
    }

    private function jsonLd(string $html): array
    {
        preg_match_all('/<script type="application\/ld\+json">(.*?)<\/script>/s', $html, $m);

        return collect($m[1])
            ->map(fn ($bloque) => json_decode($bloque, true, flags: JSON_THROW_ON_ERROR))
            ->flatMap(fn ($dato) => $dato['@graph'] ?? [$dato])
            ->all();
    }

    private function inscrita(): Persona
    {
        $persona = Persona::create([
            'dni' => '41000001',
            'nombre' => 'María',
            'apellido_paterno' => 'Quispe',
            'apellido_materno' => 'Mamani',
            'nombre_completo' => 'María Quispe Mamani',
            'codigo_comision' => '0101',
            'user_id' => User::factory()->create()->id,
            'estado' => 'aprobado',
        ]);
        $persona->asignarRegistro();

        return $persona->refresh();
    }

    public function test_el_perfil_publico_declara_su_codigo_de_registro_y_su_comision(): void
    {
        $persona = $this->inscrita();

        $html = $this->get("/consejeros/{$persona->slug}")->assertOk()->getContent();
        $artista = collect($this->jsonLd($html))->firstWhere('@type', 'Person');

        $this->assertSame([
            '@type' => 'PropertyValue',
            'propertyID' => 'CPC',
            'name' => 'Código de registro de agente cultural',
            'value' => 'CPC0001',
        ], $artista['identifier']);
        // especialidad y comisión principal por su nombre corto, no el oficial largo
        $this->assertSame(['Conjunto de sikuris', 'Música'], $artista['knowsAbout']);
    }

    // mismo contacto que el footer (fronted/src/config/institucion.js → atencion):
    // Google cruza dirección y teléfono para confirmar que es la misma entidad
    public function test_la_organizacion_publica_el_contacto_de_la_gerencia_de_cultura(): void
    {
        config(['seo.organizacion.redes' => []]);
        $html = $this->get('/')->assertOk()->getContent();
        $org = collect($this->jsonLd($html))->firstWhere('@type', 'GovernmentOrganization');

        $this->assertSame('Pasaje Ramis N.° 359', $org['address']['streetAddress']);
        $this->assertSame('+51 931 342 560', $org['telephone']);
    }

    public function test_sin_redes_configuradas_usa_el_facebook_oficial_de_la_gerencia(): void
    {
        $org = collect($this->jsonLd($this->get('/')->getContent()))->firstWhere('@type', 'GovernmentOrganization');

        $this->assertContains('https://www.facebook.com/share/1G4VLHidc4/', $org['sameAs']);
    }

    public function test_la_pagina_del_consejo_tiene_su_titulo_y_esta_en_el_sitemap_y_llms(): void
    {
        $html = $this->get('/consejo')->assertOk()->getContent();
        $this->assertStringContainsString('<title>Consejo Provincial de Cultura y Gerencia de Cultura y Turismo | CPC Puno</title>', $html);
        $this->assertStringContainsString('/consejo"', $html); // canónica

        $this->assertStringContainsString('/consejo</loc>', $this->get('/sitemap.xml')->getContent());
        $this->assertStringContainsString('/consejo)', $this->get('/llms.txt')->getContent());
    }

    public function test_la_imagen_para_compartir_es_la_de_la_marca_cpc(): void
    {
        $html = $this->get('/agenda')->assertOk()->getContent();

        $this->assertStringContainsString('/images/og-cpc.jpg', $html);
        $this->assertStringNotContainsString('racc', strtolower($html));
    }

    public function test_llms_txt_usa_los_nombres_cortos_y_explica_la_verificacion_del_registro(): void
    {
        $texto = $this->get('/llms.txt')->assertOk()->getContent();

        $this->assertStringContainsString('- Música (Comisión especializada de música)', $texto);
        $this->assertStringContainsString('CPC0001', $texto);
        $this->assertStringContainsString('código QR', $texto);
    }
}
