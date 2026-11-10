<?php

namespace Tests\Feature;

use App\Models\Comision;
use App\Models\Evento;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// La agenda pública muestra SOLO eventos aprobados. Pendientes, observados y
// cancelados no aparecen (decisión: un cancelado desaparece del calendario).
class EventoPublicoTest extends TestCase
{
    use RefreshDatabase;

    private function evento(array $datos = []): Evento
    {
        return Evento::create([
            'titulo' => 'Evento de prueba',
            'fecha_inicio' => '2026-11-14 20:00',
            'modalidad' => 'presencial',
            'lugar' => 'Teatro Municipal',
            'organizador_tipo' => 'municipalidad',
            'estado' => 'aprobado',
            ...$datos,
        ]);
    }

    private function agenda(array $query = []): array
    {
        return $this->getJson('/api/publico/eventos?' . http_build_query([
            'desde' => '2026-11-01',
            'hasta' => '2026-11-30',
            ...$query,
        ]))->assertOk()->json('data');
    }

    public function test_muestra_los_eventos_aprobados_del_rango(): void
    {
        $this->evento(['titulo' => 'Concierto de noviembre']);
        $this->evento(['titulo' => 'Fuera de rango', 'fecha_inicio' => '2026-12-05 19:00']);

        $this->assertSame(['Concierto de noviembre'], array_column($this->agenda(), 'titulo'));
    }

    public function test_no_muestra_cancelados_pendientes_ni_observados(): void
    {
        $this->evento(['titulo' => 'Aprobado']);
        $this->evento(['titulo' => 'Cancelado', 'estado' => 'cancelado']);
        $this->evento(['titulo' => 'Pendiente', 'estado' => 'pendiente']);
        $this->evento(['titulo' => 'Observado', 'estado' => 'observado']);

        $this->assertSame(['Aprobado'], array_column($this->agenda(), 'titulo'));
    }

    public function test_incluye_un_evento_de_varios_dias_que_empezo_antes_del_rango(): void
    {
        $this->evento([
            'titulo' => 'Festival',
            'fecha_inicio' => '2026-10-30 10:00',
            'fecha_fin' => '2026-11-02 22:00',
        ]);

        $this->assertSame(['Festival'], array_column($this->agenda(), 'titulo'));
    }

    public function test_ordena_por_fecha_de_inicio(): void
    {
        $this->evento(['titulo' => 'Segundo', 'fecha_inicio' => '2026-11-20 18:00']);
        $this->evento(['titulo' => 'Primero', 'fecha_inicio' => '2026-11-03 18:00']);

        $this->assertSame(['Primero', 'Segundo'], array_column($this->agenda(), 'titulo'));
    }

    public function test_filtra_por_disciplina(): void
    {
        Comision::create(['codigo' => '0101', 'tipo' => 'familia', 'cod_grupo' => '01', 'cod_familia' => '01', 'nombre' => 'Danza']);
        Comision::create(['codigo' => '0201', 'tipo' => 'familia', 'cod_grupo' => '02', 'cod_familia' => '01', 'nombre' => 'Música']);
        $this->evento(['titulo' => 'De danza', 'codigo_comision' => '0101']);
        $this->evento(['titulo' => 'De música', 'codigo_comision' => '0201']);

        $this->assertSame(['De música'], array_column($this->agenda(['grupo' => '02']), 'titulo'));
    }

    public function test_devuelve_la_hora_tal_cual_se_cargo(): void
    {
        $this->evento(['fecha_inicio' => '2026-11-14 20:00']);

        $this->assertSame('2026-11-14 20:00', $this->agenda()[0]['fecha_inicio']);
    }

    public function test_no_expone_datos_internos(): void
    {
        $this->evento(['observacion' => 'nota interna']);

        $evento = $this->agenda()[0];

        foreach (['estado', 'observacion', 'creado_por', 'revisado_por'] as $campo) {
            $this->assertArrayNotHasKey($campo, $evento);
        }
    }

    // la SPA responde 404 a las rutas que SpaController no conoce: la agenda
    // tiene que responder 200 para que los buscadores la indexen
    public function test_la_pagina_de_la_agenda_responde_200(): void
    {
        $this->get('/agenda')->assertOk();
    }

    // --- página propia de cada evento: /agenda/{id}-{slug} ---

    public function test_la_agenda_devuelve_el_slug_para_armar_el_link(): void
    {
        $this->evento(['titulo' => 'Noche de Rock']);

        $this->assertSame('noche-de-rock', $this->agenda()[0]['slug']);
    }

    public function test_muestra_el_detalle_de_un_evento_aprobado(): void
    {
        $evento = $this->evento(['titulo' => 'Noche de Rock']);

        $this->getJson("/api/publico/eventos/{$evento->id}")
            ->assertOk()
            ->assertJsonPath('data.titulo', 'Noche de Rock')
            ->assertJsonPath('data.slug', 'noche-de-rock')
            ->assertJsonMissingPath('data.estado')
            ->assertJsonMissingPath('data.observacion');
    }

    public function test_el_detalle_de_un_evento_no_publicado_no_existe(): void
    {
        $cancelado = $this->evento(['estado' => 'cancelado']);
        $pendiente = $this->evento(['estado' => 'pendiente']);

        $this->getJson("/api/publico/eventos/{$cancelado->id}")->assertNotFound();
        $this->getJson("/api/publico/eventos/{$pendiente->id}")->assertNotFound();
    }

    public function test_el_detalle_sugiere_otros_eventos_proximos(): void
    {
        $this->travelTo(now()->setDate(2026, 11, 10));
        $actual = $this->evento(['titulo' => 'Actual', 'fecha_inicio' => '2026-11-14 20:00']);
        $this->evento(['titulo' => 'Próximo', 'fecha_inicio' => '2026-11-20 19:00']);
        $this->evento(['titulo' => 'Ya pasó', 'fecha_inicio' => '2026-11-02 19:00']);
        $this->evento(['titulo' => 'Cancelado', 'fecha_inicio' => '2026-11-21 19:00', 'estado' => 'cancelado']);

        $otros = $this->getJson("/api/publico/eventos/{$actual->id}")->assertOk()->json('otros');

        $this->assertSame(['Próximo'], array_column($otros, 'titulo'));
    }

    public function test_los_otros_eventos_priorizan_la_misma_disciplina(): void
    {
        $this->travelTo(now()->setDate(2026, 11, 10));
        Comision::create(['codigo' => '0101', 'tipo' => 'familia', 'cod_grupo' => '01', 'cod_familia' => '01', 'nombre' => 'Danza']);
        Comision::create(['codigo' => '0201', 'tipo' => 'familia', 'cod_grupo' => '02', 'cod_familia' => '01', 'nombre' => 'Música']);
        $actual = $this->evento(['titulo' => 'Actual', 'codigo_comision' => '0201']);
        $this->evento(['titulo' => 'Danza antes', 'codigo_comision' => '0101', 'fecha_inicio' => '2026-11-15 18:00']);
        $this->evento(['titulo' => 'Música después', 'codigo_comision' => '0201', 'fecha_inicio' => '2026-11-25 18:00']);

        $otros = $this->getJson("/api/publico/eventos/{$actual->id}")->json('otros');

        $this->assertSame(['Música después', 'Danza antes'], array_column($otros, 'titulo'));
    }

    // el backend escribe el <head> (Open Graph): WhatsApp muestra afiche y título
    public function test_la_pagina_del_evento_responde_con_su_titulo_para_compartir(): void
    {
        $evento = $this->evento(['titulo' => 'Noche de Rock']);

        $this->get("/agenda/{$evento->id}-noche-de-rock")
            ->assertOk()
            ->assertSee('<meta property="og:title" content="Noche de Rock', false);
    }

    public function test_la_pagina_de_un_evento_cancelado_responde_404(): void
    {
        $evento = $this->evento(['estado' => 'cancelado']);

        $this->get("/agenda/{$evento->id}-lo-que-sea")->assertNotFound();
    }

    // --- portada: "próximos eventos" ---

    public function test_la_portada_muestra_los_proximos_eventos_en_orden(): void
    {
        $this->travelTo(now()->setDate(2026, 11, 10));
        $this->evento(['titulo' => 'Pasado', 'fecha_inicio' => '2026-11-05 19:00']);
        $this->evento(['titulo' => 'Mañana', 'fecha_inicio' => '2026-11-11 19:00']);
        $this->evento(['titulo' => 'Hoy', 'fecha_inicio' => '2026-11-10 08:00']);
        $this->evento(['titulo' => 'Cancelado', 'fecha_inicio' => '2026-11-12 19:00', 'estado' => 'cancelado']);
        $this->evento(['titulo' => 'Festival en curso', 'fecha_inicio' => '2026-11-08 10:00', 'fecha_fin' => '2026-11-13 22:00']);

        $titulos = array_column($this->getJson('/api/publico/eventos/proximos')->assertOk()->json('data'), 'titulo');

        $this->assertSame(['Festival en curso', 'Hoy', 'Mañana'], $titulos);
    }

    public function test_la_portada_muestra_como_maximo_cuatro_por_defecto(): void
    {
        $this->travelTo(now()->setDate(2026, 11, 10));
        foreach (range(11, 17) as $dia) {
            $this->evento(['titulo' => "Día {$dia}", 'fecha_inicio' => "2026-11-{$dia} 19:00"]);
        }

        $this->getJson('/api/publico/eventos/proximos')->assertOk()->assertJsonCount(4, 'data');
    }

    public function test_rechaza_rangos_demasiado_largos(): void
    {
        $this->getJson('/api/publico/eventos?desde=2026-01-01&hasta=2026-12-31')->assertUnprocessable();
    }
}
