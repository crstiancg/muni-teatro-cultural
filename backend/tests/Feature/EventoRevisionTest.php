<?php

namespace Tests\Feature;

use App\Models\Agrupacion;
use App\Models\Evento;
use App\Models\Persona;
use App\Models\User;
use App\Notifications\AvisoEvento;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Laravel\Passport\Passport;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

// El admin revisa lo que proponen los artistas: aprobar lo publica en la agenda,
// observar lo devuelve con un motivo. En ambos casos le llega aviso al organizador.
class EventoRevisionTest extends TestCase
{
    use RefreshDatabase;

    private function revisor(): User
    {
        $admin = User::factory()->create();
        Permission::findOrCreate('admin-eventos-aprobar', 'api');
        $admin->givePermissionTo('admin-eventos-aprobar');
        Passport::actingAs($admin, [], 'api');

        return $admin;
    }

    private function persona(string $dni = '41000000', array $datos = []): Persona
    {
        return Persona::create([
            'dni' => $dni,
            'nombre' => 'Luis',
            'apellido_paterno' => 'Mamani',
            'apellido_materno' => 'Condori',
            'nombre_completo' => "Luis Mamani Condori {$dni}",
            'user_id' => User::factory()->create()->id,
            ...$datos,
        ]);
    }

    private function propuesto(Persona $persona, string $estado = 'pendiente'): Evento
    {
        return Evento::create([
            'titulo' => 'Recital de charango',
            'fecha_inicio' => '2026-11-22 19:00',
            'modalidad' => 'presencial',
            'lugar' => 'Casa de la Cultura',
            'organizador_tipo' => 'persona',
            'persona_id' => $persona->id,
            'estado' => $estado,
        ]);
    }

    public function test_aprobar_lo_publica_y_avisa_al_artista(): void
    {
        Notification::fake();
        $admin = $this->revisor();
        $persona = $this->persona();
        $evento = $this->propuesto($persona);

        $this->putJson("/api/eventos/{$evento->id}/aprobar")->assertOk();

        $evento->refresh();
        $this->assertSame('aprobado', $evento->estado);
        $this->assertSame($admin->id, $evento->revisado_por);
        $this->assertNotNull($evento->revisado_en);
        $this->assertSame('aprobado', $evento->revisiones()->first()->accion);
        Notification::assertSentTo($persona->user, AvisoEvento::class);
    }

    public function test_solo_se_aprueba_lo_que_esta_en_revision(): void
    {
        $this->revisor();
        $evento = $this->propuesto($this->persona(), 'cancelado');

        $this->putJson("/api/eventos/{$evento->id}/aprobar")->assertUnprocessable();
    }

    public function test_observar_exige_un_motivo(): void
    {
        $this->revisor();
        $evento = $this->propuesto($this->persona());

        $this->putJson("/api/eventos/{$evento->id}/observar", [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('observacion');
    }

    public function test_observar_devuelve_el_evento_con_el_motivo_y_avisa(): void
    {
        Notification::fake();
        $this->revisor();
        $persona = $this->persona();
        $evento = $this->propuesto($persona);

        $this->putJson("/api/eventos/{$evento->id}/observar", ['observacion' => 'Indica el lugar exacto'])->assertOk();

        $evento->refresh();
        $this->assertSame('observado', $evento->estado);
        $this->assertSame('Indica el lugar exacto', $evento->observacion);
        Notification::assertSentTo($persona->user, AvisoEvento::class);
    }

    public function test_sin_permiso_no_puede_revisar(): void
    {
        Passport::actingAs(User::factory()->create(), [], 'api');
        $evento = $this->propuesto($this->persona());

        $this->putJson("/api/eventos/{$evento->id}/aprobar")->assertForbidden();
    }

    public function test_el_aviso_de_una_agrupacion_le_llega_a_su_representante(): void
    {
        Notification::fake();
        $this->revisor();
        $representante = $this->persona();
        $agrupacion = Agrupacion::create(['nombre' => 'Sikuris', 'estado' => 'aprobado']);
        $agrupacion->integrantes()->create([
            'dni' => $representante->dni,
            'nombre' => 'Luis',
            'apellido_paterno' => 'Mamani',
            'persona_id' => $representante->id,
            'es_representante' => true,
        ]);
        $evento = Evento::create([
            'titulo' => 'Pasacalle',
            'fecha_inicio' => '2026-11-22 10:00',
            'modalidad' => 'presencial',
            'lugar' => 'Plaza de Armas',
            'organizador_tipo' => 'agrupacion',
            'agrupacion_id' => $agrupacion->id,
            'estado' => 'pendiente',
        ]);

        $this->putJson("/api/eventos/{$evento->id}/aprobar")->assertOk();

        Notification::assertSentTo($representante->user, AvisoEvento::class);
    }

    public function test_el_portal_no_enlaza_a_un_perfil_que_no_esta_publicado(): void
    {
        $evento = $this->propuesto($this->persona(), 'aprobado');

        $this->getJson("/api/publico/eventos/{$evento->id}")
            ->assertOk()
            ->assertJsonPath('data.organizador.tipo', 'persona')
            ->assertJsonPath('data.organizador.slug', null);
    }
}
