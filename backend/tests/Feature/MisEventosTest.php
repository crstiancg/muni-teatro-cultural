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

// Fase 2: cada artista propone SUS próximos eventos (o los de la agrupación que
// representa). Todo entra pendiente, el admin lo aprueba u observa, y el artista
// ve en "Mis eventos" en qué estado está cada uno.
class MisEventosTest extends TestCase
{
    use RefreshDatabase;

    private int $dni = 40000000;

    private function persona(array $datos = []): Persona
    {
        $user = User::factory()->create();
        $dni = (string) $this->dni++;

        return Persona::create([
            'dni' => $dni,
            'nombre' => 'Ana',
            'apellido_paterno' => 'Quispe',
            'apellido_materno' => 'Mamani',
            'nombre_completo' => "Ana Quispe Mamani {$dni}",
            'user_id' => $user->id,
            ...$datos,
        ]);
    }

    private function comoPersona(?Persona $persona = null): Persona
    {
        $persona ??= $this->persona();
        Passport::actingAs($persona->user, [], 'api');

        return $persona;
    }

    private function adminRevisor(): User
    {
        $admin = User::factory()->create();
        Permission::findOrCreate('admin-eventos-aprobar', 'api');
        $admin->givePermissionTo('admin-eventos-aprobar');

        return $admin;
    }

    private function agrupacion(Persona $representante, string $estado = 'aprobado'): Agrupacion
    {
        $agrupacion = Agrupacion::create(['nombre' => 'Sikuris del Lago ' . $this->dni, 'estado' => $estado]);
        $agrupacion->integrantes()->create([
            'dni' => $representante->dni,
            'nombre' => $representante->nombre,
            'apellido_paterno' => $representante->apellido_paterno,
            'persona_id' => $representante->id,
            'es_representante' => true,
        ]);

        return $agrupacion;
    }

    private function datos(array $cambios = []): array
    {
        return ['evento' => [
            'titulo' => 'Mi recital',
            'fecha_inicio' => '2026-11-20 19:00',
            'modalidad' => 'presencial',
            'lugar' => 'Casa de la Cultura',
            'flag_gratuito' => '1',
            ...$cambios,
        ]];
    }

    private function eventoDe(Persona $persona, string $estado, array $datos = []): Evento
    {
        return Evento::create([
            'titulo' => 'Evento propio',
            'fecha_inicio' => '2026-11-20 19:00',
            'modalidad' => 'presencial',
            'lugar' => 'Casa de la Cultura',
            'organizador_tipo' => 'persona',
            'persona_id' => $persona->id,
            'estado' => $estado,
            ...$datos,
        ]);
    }

    // ---------- proponer ----------

    public function test_la_persona_propone_un_evento_y_queda_pendiente(): void
    {
        Notification::fake();
        $admin = $this->adminRevisor();
        $persona = $this->comoPersona();

        $this->postJson('/api/mis-eventos', $this->datos())->assertCreated();

        $evento = Evento::sole();
        $this->assertSame('pendiente', $evento->estado);
        $this->assertSame('persona', $evento->organizador_tipo);
        $this->assertSame($persona->id, $evento->persona_id);
        $this->assertSame($persona->user_id, $evento->creado_por);
        $this->assertSame('enviado', $evento->revisiones()->sole()->accion);
        Notification::assertSentTo($admin, AvisoEvento::class);
    }

    public function test_un_evento_propuesto_no_sale_en_la_agenda_hasta_que_se_aprueba(): void
    {
        $this->comoPersona();
        $this->postJson('/api/mis-eventos', $this->datos())->assertCreated();

        $this->getJson('/api/publico/eventos?desde=2026-11-01&hasta=2026-11-30')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_el_artista_no_puede_destacar_su_propio_evento(): void
    {
        $this->comoPersona();

        $this->postJson('/api/mis-eventos', $this->datos(['flag_destacado' => '1']))->assertCreated();

        $this->assertFalse(Evento::sole()->flag_destacado);
    }

    public function test_sin_ficha_de_persona_no_puede_proponer(): void
    {
        Passport::actingAs(User::factory()->create(), [], 'api');

        $this->postJson('/api/mis-eventos', $this->datos())->assertForbidden();
    }

    public function test_el_representante_propone_en_nombre_de_su_agrupacion(): void
    {
        $persona = $this->comoPersona();
        $agrupacion = $this->agrupacion($persona);

        $this->postJson('/api/mis-eventos', $this->datos(['agrupacion_id' => $agrupacion->id]))->assertCreated();

        $evento = Evento::sole();
        $this->assertSame('agrupacion', $evento->organizador_tipo);
        $this->assertSame($agrupacion->id, $evento->agrupacion_id);
        $this->assertSame('pendiente', $evento->estado);
    }

    public function test_no_puede_proponer_por_una_agrupacion_que_no_representa(): void
    {
        $agrupacion = $this->agrupacion($this->persona());
        $this->comoPersona();

        $this->postJson('/api/mis-eventos', $this->datos(['agrupacion_id' => $agrupacion->id]))->assertForbidden();
        $this->assertSame(0, Evento::count());
    }

    public function test_la_agrupacion_tiene_que_estar_aprobada(): void
    {
        $persona = $this->comoPersona();
        $agrupacion = $this->agrupacion($persona, 'pendiente');

        $this->postJson('/api/mis-eventos', $this->datos(['agrupacion_id' => $agrupacion->id]))->assertUnprocessable();
    }

    // ---------- ver el estado ----------

    public function test_lista_solo_mis_eventos_y_los_de_mis_agrupaciones_con_su_estado(): void
    {
        $persona = $this->comoPersona();
        $agrupacion = $this->agrupacion($persona);
        $this->eventoDe($persona, 'observado', ['titulo' => 'Mío observado', 'observacion' => 'Falta el afiche']);
        Evento::create([
            'titulo' => 'De mi agrupación',
            'fecha_inicio' => '2026-11-21 19:00',
            'modalidad' => 'virtual',
            'enlace' => 'https://example.com',
            'organizador_tipo' => 'agrupacion',
            'agrupacion_id' => $agrupacion->id,
            'estado' => 'aprobado',
        ]);
        $this->eventoDe($this->persona(), 'pendiente', ['titulo' => 'De otro artista']);

        $respuesta = $this->getJson('/api/mis-eventos')->assertOk();

        $titulos = collect($respuesta->json('eventos'))->pluck('titulo')->sort()->values()->all();
        $this->assertSame(['De mi agrupación', 'Mío observado'], $titulos);
        $observado = collect($respuesta->json('eventos'))->firstWhere('titulo', 'Mío observado');
        $this->assertSame('observado', $observado['estado']);
        $this->assertSame('Falta el afiche', $observado['observacion']);
        // las agrupaciones por las que puede proponer (para el selector "Organiza")
        $this->assertSame([$agrupacion->id], collect($respuesta->json('agrupaciones'))->pluck('id')->all());
    }

    public function test_no_puede_ver_ni_editar_el_evento_de_otro(): void
    {
        $ajeno = $this->eventoDe($this->persona(), 'pendiente');
        $this->comoPersona();

        $this->getJson("/api/mis-eventos/{$ajeno->id}")->assertForbidden();
        $this->postJson("/api/mis-eventos/{$ajeno->id}", $this->datos())->assertForbidden();
    }

    // ---------- editar vuelve a revisión ----------

    public function test_editar_un_evento_aprobado_lo_vuelve_a_revision(): void
    {
        Notification::fake();
        $admin = $this->adminRevisor();
        $persona = $this->comoPersona();
        $evento = $this->eventoDe($persona, 'aprobado');

        $this->postJson("/api/mis-eventos/{$evento->id}", $this->datos(['titulo' => 'Cambió la hora']))->assertOk();

        $evento->refresh();
        $this->assertSame('pendiente', $evento->estado);
        $this->assertSame('Cambió la hora', $evento->titulo);
        Notification::assertSentTo($admin, AvisoEvento::class);
    }

    public function test_corregir_un_evento_observado_lo_reenvia_y_limpia_la_observacion(): void
    {
        $persona = $this->comoPersona();
        $evento = $this->eventoDe($persona, 'observado', ['observacion' => 'Falta el lugar exacto']);

        $this->postJson("/api/mis-eventos/{$evento->id}", $this->datos())->assertOk();

        $evento->refresh();
        $this->assertSame('pendiente', $evento->estado);
        $this->assertNull($evento->observacion);
    }

    public function test_un_evento_cancelado_ya_no_se_edita(): void
    {
        $persona = $this->comoPersona();
        $evento = $this->eventoDe($persona, 'cancelado');

        $this->postJson("/api/mis-eventos/{$evento->id}", $this->datos())->assertUnprocessable();
    }

    // ---------- cancelar y eliminar ----------

    public function test_el_artista_cancela_su_evento(): void
    {
        $persona = $this->comoPersona();
        $evento = $this->eventoDe($persona, 'aprobado');

        $this->putJson("/api/mis-eventos/{$evento->id}/cancelar")->assertOk();

        $this->assertSame('cancelado', $evento->refresh()->estado);
    }

    public function test_puede_eliminar_un_evento_que_aun_no_se_publico(): void
    {
        $persona = $this->comoPersona();
        $evento = $this->eventoDe($persona, 'pendiente');

        $this->deleteJson("/api/mis-eventos/{$evento->id}")->assertOk();

        $this->assertModelMissing($evento);
    }

    public function test_un_evento_publicado_no_se_elimina_se_cancela(): void
    {
        $persona = $this->comoPersona();
        $evento = $this->eventoDe($persona, 'aprobado');

        $this->deleteJson("/api/mis-eventos/{$evento->id}")->assertUnprocessable();

        $this->assertModelExists($evento);
    }
}
