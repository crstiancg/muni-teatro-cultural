<?php

namespace Tests\Feature;

use App\Models\Evento;
use App\Support\Seo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// Identidad SEO: que Google reciba UNA entidad coherente (organización + sitio)
// y que ninguna URL pública apunte a un dominio de ejemplo.
class SeoIdentidadTest extends TestCase
{
    use RefreshDatabase;

    private function jsonLd(string $html): array
    {
        preg_match_all('/<script type="application\/ld\+json">(.*?)<\/script>/s', $html, $m);

        return collect($m[1])
            ->map(fn ($bloque) => json_decode($bloque, true, flags: JSON_THROW_ON_ERROR))
            ->flatMap(fn ($dato) => $dato['@graph'] ?? [$dato])
            ->all();
    }

    public function test_la_portada_publica_la_organizacion_y_el_sitio_con_buscador(): void
    {
        $html = $this->get('/')->assertOk()->getContent();
        $nodos = collect($this->jsonLd($html))->keyBy('@type');

        $org = $nodos['GovernmentOrganization'];
        $this->assertSame('Registro de Agentes Culturales de Puno', $org['name']);
        $this->assertSame('Municipalidad Provincial de Puno', $org['parentOrganization']['name']);
        $this->assertSame('Puno', $org['address']['addressLocality']);
        $this->assertStringEndsWith('/icons/logo-512.png', $org['logo']['url']);

        $sitio = $nodos['WebSite'];
        $this->assertSame(['@id' => $org['@id']], $sitio['publisher']);
        $this->assertStringContainsString('/consejeros?buscar={search_term_string}', $sitio['potentialAction']['target']['urlTemplate']);
    }

    public function test_cada_pagina_tiene_una_sola_descripcion_idioma_e_imagen_para_compartir(): void
    {
        $html = $this->get('/agenda')->assertOk()->getContent();

        // el build minificado trae name=description sin comillas: no debe quedar duplicada
        $this->assertSame(1, preg_match_all('/<meta\s+name=["\']?description/i', $html));
        $this->assertStringContainsString('<html lang="es-PE">', $html);
        $this->assertStringContainsString('/images/og-racc.jpg', $html);
        $this->assertStringContainsString('<meta property="og:image:width" content="1200">', $html);
    }

    public function test_el_evento_municipal_lo_organiza_la_entidad_del_sitio_y_es_gratuito(): void
    {
        $evento = Evento::create([
            'titulo' => 'Concurso de sikuris',
            'fecha_inicio' => '2026-11-15 16:00',
            'modalidad' => 'presencial',
            'lugar' => 'Parque Pino',
            'organizador_tipo' => 'municipalidad',
            'estado' => 'aprobado',
            'flag_gratuito' => true,
        ]);

        $html = $this->get("/agenda/{$evento->ruta_publica}")->assertOk()->getContent();
        $nodo = collect($this->jsonLd($html))->firstWhere('@type', 'Event');

        $this->assertSame(['@id' => Seo::idOrganizacion()], $nodo['organizer']);
        $this->assertSame(0, $nodo['offers']['price']);
        $this->assertSame('PEN', $nodo['offers']['priceCurrency']);
    }

    public function test_el_sitemap_incluye_la_agenda_y_los_eventos_publicados(): void
    {
        $publicado = Evento::create([
            'titulo' => 'Noche de estudiantinas', 'fecha_inicio' => '2026-11-18 19:30',
            'modalidad' => 'presencial', 'lugar' => 'Teatro', 'organizador_tipo' => 'municipalidad', 'estado' => 'aprobado',
        ]);
        Evento::create([
            'titulo' => 'Propuesta pendiente', 'fecha_inicio' => '2026-11-20 19:30',
            'modalidad' => 'presencial', 'lugar' => 'Teatro', 'organizador_tipo' => 'municipalidad', 'estado' => 'pendiente',
        ]);

        $xml = $this->get('/sitemap.xml')->assertOk()->getContent();

        $this->assertStringContainsString('/agenda</loc>', $xml);
        $this->assertStringContainsString("/agenda/{$publicado->ruta_publica}</loc>", $xml);
        $this->assertStringNotContainsString('propuesta-pendiente', $xml);
    }

    public function test_llms_txt_describe_la_entidad(): void
    {
        $this->get('/llms.txt')
            ->assertOk()
            ->assertSee('# Registro de Agentes Culturales de Puno (RACC Puno)', false)
            ->assertSee('Municipalidad Provincial de Puno');
    }

    public function test_en_produccion_un_dominio_de_ejemplo_se_reemplaza_por_el_real(): void
    {
        // pasó en el primer despliegue: FRONTEND_URL=https://tudominio
        $this->app['env'] = 'production';
        config(['app.frontend_url' => 'https://tudominio']);

        $this->get('http://racc.munipuno.gob.pe/robots.txt')
            ->assertOk()
            ->assertSee('Sitemap: http://racc.munipuno.gob.pe/sitemap.xml', false)
            ->assertDontSee('tudominio');
    }
}
