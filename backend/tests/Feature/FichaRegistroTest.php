<?php

namespace Tests\Feature;

use App\Models\Comision;
use App\Models\Persona;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Laravel\Passport\Passport;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

// Al aprobarse el perfil por primera vez, la persona entra al Registro de Agentes
// Culturales con un código correlativo (CPC0001, CPC0002...) que no cambia nunca,
// y puede descargar su ficha en PDF con un QR de verificación pública.
class FichaRegistroTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();

        // nombre tal cual viene de la base: la ficha y la verificación muestran "Música"
        Comision::create(['codigo' => '0100', 'tipo' => 'grupo', 'cod_grupo' => '01', 'cod_familia' => '00', 'nombre' => 'Comision especializada de musica']);
        Comision::create(['codigo' => '0101', 'tipo' => 'familia', 'cod_grupo' => '01', 'cod_familia' => '01', 'nombre' => 'Charango']);
    }

    private function revisor(array $permisos = ['admin-personas-aprobar']): User
    {
        $admin = User::factory()->create();
        foreach ($permisos as $permiso) {
            Permission::findOrCreate($permiso, 'api');
            $admin->givePermissionTo($permiso);
        }
        Passport::actingAs($admin, [], 'api');

        return $admin;
    }

    private function persona(string $dni = '41000000', string $estado = 'pendiente'): Persona
    {
        return Persona::create([
            'dni' => $dni,
            'nombre' => 'María',
            'apellido_paterno' => 'Quispe',
            'apellido_materno' => 'Mamani',
            'nombre_completo' => "María Quispe Mamani {$dni}",
            'codigo_comision' => '0101',
            'user_id' => User::factory()->create()->id,
            'estado' => $estado,
        ]);
    }

    private function aprobar(Persona $persona): Persona
    {
        $this->putJson("/api/personas/{$persona->id}/aprobar")->assertOk();

        return $persona->refresh();
    }

    public function test_la_primera_aprobacion_asigna_el_codigo_cpc0001(): void
    {
        $this->revisor();
        $persona = $this->aprobar($this->persona());

        $this->assertSame(1, $persona->numero_registro);
        $this->assertSame('CPC0001', $persona->codigo_registro);
        $this->assertMatchesRegularExpression('/^[A-Za-z0-9]{10}$/', $persona->qr_token);
        $this->assertNotNull($persona->registrado_en);
    }

    public function test_los_codigos_son_correlativos_en_orden_de_aprobacion(): void
    {
        $this->revisor();
        $segunda = $this->persona('41000002');
        $primera = $this->persona('41000001');

        $this->aprobar($primera);
        $this->aprobar($segunda);

        $this->assertSame('CPC0001', $primera->refresh()->codigo_registro);
        $this->assertSame('CPC0002', $segunda->refresh()->codigo_registro);
    }

    public function test_volver_a_aprobar_conserva_el_codigo_y_la_fecha_de_inscripcion(): void
    {
        $this->revisor();
        $persona = $this->aprobar($this->persona());
        $token = $persona->qr_token;
        $inscripcion = $persona->registrado_en;

        $this->putJson("/api/personas/{$persona->id}/observar", ['observacion' => 'Falta foto'])->assertOk();
        $persona->update(['estado' => 'pendiente']);
        $this->travel(2)->days();
        $persona = $this->aprobar($persona);

        $this->assertSame('CPC0001', $persona->codigo_registro);
        $this->assertSame($token, $persona->qr_token);
        $this->assertTrue($inscripcion->equalTo($persona->registrado_en));
        $this->assertSame('CPC0002', $this->aprobar($this->persona('41000009'))->codigo_registro);
    }

    public function test_el_token_del_qr_nunca_viaja_en_la_api(): void
    {
        $this->revisor();
        $persona = $this->persona();

        $this->putJson("/api/personas/{$persona->id}/aprobar")
            ->assertOk()
            ->assertJsonPath('codigo_registro', 'CPC0001')
            ->assertJsonMissingPath('qr_token');
    }

    public function test_el_artista_descarga_su_ficha_en_pdf(): void
    {
        $this->revisor();
        $persona = $this->aprobar($this->persona());
        Passport::actingAs($persona->user, [], 'api');

        $respuesta = $this->get('/api/mi-informacion/ficha-registro')->assertOk();

        $this->assertSame('application/pdf', $respuesta->headers->get('Content-Type'));
        $this->assertStringContainsString('CPC0001', $respuesta->headers->get('Content-Disposition'));
        $this->assertStringStartsWith('%PDF', $respuesta->getContent());
    }

    public function test_sin_aprobacion_no_hay_ficha(): void
    {
        $persona = $this->persona();
        Passport::actingAs($persona->user, [], 'api');

        $this->getJson('/api/mi-informacion/ficha-registro')->assertNotFound();
    }

    public function test_el_admin_descarga_la_ficha_de_una_persona(): void
    {
        $this->revisor(['admin-personas-aprobar', 'admin-personas-index']);
        $persona = $this->aprobar($this->persona());

        $respuesta = $this->get("/api/personas/{$persona->id}/ficha-registro")->assertOk();

        $this->assertSame('application/pdf', $respuesta->headers->get('Content-Type'));
    }

    public function test_sin_permiso_no_se_ve_la_ficha_ajena(): void
    {
        $this->revisor();
        $persona = $this->aprobar($this->persona());
        Passport::actingAs(User::factory()->create(), [], 'api');

        $this->getJson("/api/personas/{$persona->id}/ficha-registro")->assertForbidden();
    }

    public function test_la_verificacion_publica_muestra_el_registro_vigente_sin_datos_sensibles(): void
    {
        $this->revisor();
        $persona = $this->aprobar($this->persona());

        $this->getJson("/api/publico/verificar/{$persona->qr_token}")
            ->assertOk()
            ->assertJsonPath('codigo', 'CPC0001')
            ->assertJsonPath('nombre_completo', $persona->nombre_completo)
            ->assertJsonPath('comision', 'Música')
            ->assertJsonPath('especialidad', 'Charango')
            ->assertJsonPath('vigente', true)
            ->assertJsonMissingPath('dni')
            ->assertJsonMissingPath('correo')
            ->assertJsonMissingPath('celular');
    }

    public function test_un_registro_observado_figura_como_no_vigente(): void
    {
        $this->revisor();
        $persona = $this->aprobar($this->persona());
        $this->putJson("/api/personas/{$persona->id}/observar", ['observacion' => 'Datos falsos'])->assertOk();

        $this->getJson("/api/publico/verificar/{$persona->qr_token}")
            ->assertOk()
            ->assertJsonPath('vigente', false)
            ->assertJsonMissingPath('observacion');
    }

    public function test_un_token_inexistente_no_revela_nada(): void
    {
        $this->getJson('/api/publico/verificar/NOEXISTE12')->assertNotFound();
    }

    public function test_la_url_del_qr_es_absoluta_y_apunta_al_front(): void
    {
        config(['app.frontend_url' => 'https://cultura.example.test/']);
        $persona = new Persona;
        $persona->qr_token = 'ABC123defg';

        $this->assertSame('https://cultura.example.test/v/ABC123defg', $persona->verificacionUrl());
    }
}
