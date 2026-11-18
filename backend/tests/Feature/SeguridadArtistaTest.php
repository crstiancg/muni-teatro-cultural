<?php

namespace Tests\Feature;

use App\Models\Agrupacion;
use App\Models\Evento;
use App\Models\Persona;
use App\Models\User;
use App\Notifications\AvisoPerfil;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;

// Un artista (cuenta sin rol ni permisos) solo puede tocar LO SUYO:
// 1) ninguna ruta del panel de administración le responde (403);
// 2) no puede ver ni modificar lo de OTRO artista cambiando el id en la URL
//    (IDOR): la API responde 404/403 y el dato queda intacto.
class SeguridadArtistaTest extends TestCase
{
    use RefreshDatabase;

    private Persona $yo;

    private Persona $otro;

    protected function setUp(): void
    {
        parent::setUp();
        $this->yo = $this->artista('41000001');
        $this->otro = $this->artista('41000002');
        Passport::actingAs($this->yo->user, [], 'api');
    }

    private function artista(string $dni): Persona
    {
        return Persona::create([
            'dni' => $dni, 'nombre' => 'Ana', 'apellido_paterno' => 'Quispe', 'apellido_materno' => 'Mamani',
            'nombre_completo' => "Ana Quispe {$dni}", 'codigo_comision' => '0101', 'estado' => 'aprobado',
            'user_id' => User::factory()->create()->id,
        ]);
    }

    public function test_ninguna_ruta_del_panel_de_administracion_le_responde(): void
    {
        $otro = $this->otro->id;

        foreach ([
            ['get', '/api/usuarios'], ['get', '/api/roles'], ['get', '/api/permisos'],
            ['get', '/api/personas'], ['get', "/api/personas/{$otro}"],
            ['put', "/api/personas/{$otro}/aprobar"], ['put', "/api/personas/{$otro}/observar"],
            ['get', "/api/personas/{$otro}/ficha-registro"], ['delete', "/api/personas/{$otro}"],
            ['get', '/api/dashboard/admin'], ['get', '/api/eventos'], ['get', '/api/agrupaciones'],
            ['post', '/api/profesiones'], ['post', '/api/usuarios'],
        ] as [$metodo, $url]) {
            $this->json($metodo, $url)->assertForbidden();
        }
    }

    public function test_no_puede_tocar_la_formacion_capacitacion_ni_actividad_de_otro(): void
    {
        $formacion = $this->otro->formacionesAcademicas()->create(['tipo' => 'universitaria', 'centro_estudios' => 'UNA Puno', 'flag_activo' => true]);
        $capacitacion = $this->otro->capacitaciones()->create(['tipo' => 'taller', 'nombre_evento' => 'Taller', 'flag_activo' => true]);
        $actividad = $this->otro->actividades()->create(['titulo' => 'Recital', 'flag_activo' => true, 'flag_publico' => true]);

        $this->deleteJson("/api/mi-informacion/curriculum-vitaes/{$formacion->id}")->assertNotFound();
        $this->deleteJson("/api/mi-informacion/capacitaciones/{$capacitacion->id}")->assertNotFound();
        $this->deleteJson("/api/mi-informacion/actividades/{$actividad->id}")->assertNotFound();
        $this->deleteJson("/api/mi-informacion/actividades/{$actividad->id}/permanente")->assertNotFound();

        $this->assertTrue($formacion->fresh()->flag_activo);
        $this->assertTrue($capacitacion->fresh()->flag_activo);
        $this->assertNotNull($actividad->fresh());
    }

    public function test_no_puede_ver_ni_cancelar_el_evento_de_otro(): void
    {
        $evento = Evento::create([
            'titulo' => 'Evento ajeno', 'fecha_inicio' => '2026-11-20 19:00', 'modalidad' => 'presencial',
            'lugar' => 'Casa de la Cultura', 'organizador_tipo' => 'persona', 'persona_id' => $this->otro->id,
            'estado' => 'pendiente',
        ]);

        $this->assertContains($this->getJson("/api/mis-eventos/{$evento->id}")->status(), [403, 404]);
        $this->assertContains($this->putJson("/api/mis-eventos/{$evento->id}/cancelar")->status(), [403, 404]);
        $this->assertContains($this->deleteJson("/api/mis-eventos/{$evento->id}")->status(), [403, 404]);

        $this->assertSame('pendiente', $evento->fresh()->estado);
    }

    public function test_no_puede_gestionar_una_agrupacion_donde_no_es_representante(): void
    {
        $agrupacion = Agrupacion::create(['nombre' => 'Sikuris ajenos', 'estado' => 'borrador']);
        $agrupacion->integrantes()->create([
            'dni' => $this->otro->dni, 'nombre' => 'Ana', 'apellido_paterno' => 'Quispe',
            'persona_id' => $this->otro->id, 'es_representante' => true,
        ]);

        $this->assertContains($this->putJson("/api/mis-agrupaciones/{$agrupacion->id}", ['nombre' => 'Robada'])->status(), [403, 404]);
        $this->assertContains($this->deleteJson("/api/mis-agrupaciones/{$agrupacion->id}")->status(), [403, 404]);

        $this->assertSame('Sikuris ajenos', $agrupacion->fresh()->nombre);
    }

    public function test_no_puede_marcar_las_notificaciones_de_otro(): void
    {
        $this->otro->user->notify(new AvisoPerfil('aprobado', 'Tu perfil fue aprobado.', $this->otro));
        $id = $this->otro->user->notifications()->first()->id;

        $this->putJson("/api/notificaciones/{$id}/leer")->assertNotFound();

        $this->assertNull($this->otro->user->notifications()->first()->read_at);
    }
}
