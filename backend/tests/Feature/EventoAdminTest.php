<?php

namespace Tests\Feature;

use App\Models\Evento;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

// Fase 1: el admin carga los eventos que organiza la municipalidad.
// Se publican directo (sin revisión) y cancelar los saca del calendario.
class EventoAdminTest extends TestCase
{
    use RefreshDatabase;

    private function admin(array $permisos = ['crear', 'editar', 'eliminar', 'index']): User
    {
        $user = User::factory()->create();
        foreach ($permisos as $accion) {
            Permission::findOrCreate("admin-eventos-{$accion}", 'api');
            $user->givePermissionTo(Permission::findByName("admin-eventos-{$accion}", 'api'));
        }
        Passport::actingAs($user, [], 'api');

        return $user;
    }

    private function datos(array $cambios = []): array
    {
        return ['evento' => [
            'titulo' => 'Noche de teatro',
            'fecha_inicio' => '2026-11-14 20:00',
            'modalidad' => 'presencial',
            'lugar' => 'Teatro Municipal',
            'flag_gratuito' => '1',
            ...$cambios,
        ]];
    }

    public function test_el_admin_crea_un_evento_de_la_municipalidad_ya_publicado(): void
    {
        $admin = $this->admin();

        $this->postJson('/api/eventos', $this->datos())->assertCreated();

        $evento = Evento::sole();
        $this->assertSame('municipalidad', $evento->organizador_tipo);
        $this->assertSame('aprobado', $evento->estado);
        $this->assertSame($admin->id, $evento->creado_por);
    }

    public function test_sin_permiso_no_puede_crear(): void
    {
        $this->admin(['index']);

        $this->postJson('/api/eventos', $this->datos())->assertForbidden();
    }

    public function test_un_evento_virtual_necesita_enlace(): void
    {
        $this->admin();

        $this->postJson('/api/eventos', $this->datos(['modalidad' => 'virtual', 'lugar' => null]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('evento.enlace');
    }

    public function test_un_evento_presencial_necesita_lugar(): void
    {
        $this->admin();

        $this->postJson('/api/eventos', $this->datos(['lugar' => null]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('evento.lugar');
    }

    public function test_la_fecha_de_fin_no_puede_ser_anterior_al_inicio(): void
    {
        $this->admin();

        $this->postJson('/api/eventos', $this->datos(['fecha_fin' => '2026-11-13 20:00']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('evento.fecha_fin');
    }

    public function test_si_no_es_gratuito_necesita_costo(): void
    {
        $this->admin();

        $this->postJson('/api/eventos', $this->datos(['flag_gratuito' => '0']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('evento.costo');
    }

    public function test_cancelar_lo_saca_de_la_agenda_publica(): void
    {
        $this->admin();
        $this->postJson('/api/eventos', $this->datos())->assertCreated();
        $evento = Evento::sole();

        $this->putJson("/api/eventos/{$evento->id}/cancelar")->assertOk();

        $this->assertSame('cancelado', $evento->fresh()->estado);
        $this->getJson('/api/publico/eventos?desde=2026-11-01&hasta=2026-11-30')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    // ---------- listado del panel ----------

    private function crear(string $titulo, string $inicio, array $datos = []): Evento
    {
        return Evento::create([
            'titulo' => $titulo,
            'fecha_inicio' => $inicio,
            'modalidad' => 'presencial',
            'lugar' => 'Teatro Municipal',
            'organizador_tipo' => 'municipalidad',
            'estado' => 'aprobado',
            ...$datos,
        ]);
    }

    private function titulos(string $query): array
    {
        return collect($this->getJson("/api/eventos?{$query}")->assertOk()->json('data'))->pluck('titulo')->all();
    }

    public function test_proximos_lista_de_hoy_en_adelante_del_mas_cercano_al_mas_lejano(): void
    {
        $this->travelTo('2026-11-10 12:00');
        $this->admin();
        $this->crear('Ya pasó', '2026-11-02 20:00');
        $this->crear('El mes que viene', '2026-12-05 20:00');
        $this->crear('Esta semana', '2026-11-14 20:00');
        $this->crear('Festival en curso', '2026-11-08 10:00', ['fecha_fin' => '2026-11-12 22:00']);

        $this->assertSame(['Festival en curso', 'Esta semana', 'El mes que viene'], $this->titulos('cuando=proximos'));
    }

    public function test_anteriores_lista_lo_que_ya_paso_del_mas_reciente_al_mas_viejo(): void
    {
        $this->travelTo('2026-11-10 12:00');
        $this->admin();
        $this->crear('Octubre', '2026-10-20 20:00');
        $this->crear('Próximo', '2026-11-20 20:00');
        $this->crear('Noviembre', '2026-11-02 20:00');

        $this->assertSame(['Noviembre', 'Octubre'], $this->titulos('cuando=anteriores'));
    }

    public function test_filtra_lo_que_organizan_los_artistas(): void
    {
        $this->admin();
        $this->crear('De la municipalidad', '2026-11-20 20:00');
        $this->crear('De un artista', '2026-11-21 20:00', ['organizador_tipo' => 'persona']);
        $this->crear('De una agrupación', '2026-11-22 20:00', ['organizador_tipo' => 'agrupacion']);

        $this->assertSame(['De un artista', 'De una agrupación'], $this->titulos('organizador=artistas&order_by=fecha_inicio'));
        $this->assertSame(['De la municipalidad'], $this->titulos('organizador=municipalidad'));
    }

    public function test_el_detalle_trae_el_historial_de_revision(): void
    {
        $admin = $this->admin();
        $evento = $this->crear('Propuesto', '2026-11-20 20:00', ['organizador_tipo' => 'persona', 'estado' => 'pendiente']);
        $evento->revisiones()->create(['accion' => 'enviado', 'user_id' => $admin->id]);

        $this->getJson("/api/eventos/{$evento->id}")
            ->assertOk()
            ->assertJsonPath('revisiones.0.accion', 'enviado')
            ->assertJsonPath('revisiones.0.usuario.id', $admin->id);
    }
}
