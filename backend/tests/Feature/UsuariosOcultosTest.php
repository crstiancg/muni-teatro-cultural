<?php

namespace Tests\Feature;

use App\Models\Persona;
use App\Models\User;
use App\Support\FichaRegistro;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

// El superadministrador (config/acceso.php, USUARIOS_OCULTOS) no existe para
// nadie más: ni en la lista de usuarios, ni por URL, ni su NOMBRE en el historial
// de revisiones o en la ficha PDF que imprime el artista.
class UsuariosOcultosTest extends TestCase
{
    use RefreshDatabase;

    private User $oculto;

    protected function setUp(): void
    {
        parent::setUp();
        $this->oculto = User::factory()->create(['name' => 'Superadmin Secreto']);
        config(['acceso.usuarios_ocultos' => [$this->oculto->id]]);
    }

    private function admin(): User
    {
        $admin = User::factory()->create(['name' => 'Admin Normal']);
        foreach (['index', 'editar', 'eliminar'] as $accion) {
            Permission::findOrCreate("admin-usuarios-{$accion}", 'api');
            $admin->givePermissionTo("admin-usuarios-{$accion}");
        }

        return $admin;
    }

    // perfil aprobado por el usuario oculto
    private function aprobadoPorElOculto(): Persona
    {
        $persona = Persona::create([
            'dni' => '41000001', 'nombre' => 'Ana', 'apellido_paterno' => 'Quispe', 'apellido_materno' => 'Mamani',
            'nombre_completo' => 'Ana Quispe Mamani', 'codigo_comision' => '0101', 'estado' => 'aprobado',
            'user_id' => User::factory()->create()->id,
        ]);
        $persona->revisiones()->create(['accion' => 'aprobado', 'user_id' => $this->oculto->id]);
        $persona->asignarRegistro();

        return $persona;
    }

    public function test_no_aparece_en_la_lista_de_usuarios_de_otro_admin(): void
    {
        Passport::actingAs($this->admin(), [], 'api');

        $nombres = collect($this->getJson('/api/usuarios?per_page=100')->assertOk()->json('data'))->pluck('name');

        $this->assertNotContains('Superadmin Secreto', $nombres);
        $this->assertContains('Admin Normal', $nombres);
    }

    public function test_por_url_no_existe_para_otro_admin_ni_se_puede_editar_ni_borrar(): void
    {
        Passport::actingAs($this->admin(), [], 'api');

        $this->getJson("/api/usuarios/{$this->oculto->id}")->assertNotFound();
        $this->putJson("/api/usuarios/{$this->oculto->id}", ['usuario' => ['name' => 'x']])->assertNotFound();
        $this->deleteJson("/api/usuarios/{$this->oculto->id}")->assertNotFound();
        $this->assertNotNull($this->oculto->fresh());
    }

    public function test_el_oculto_si_se_ve_a_si_mismo(): void
    {
        Permission::findOrCreate('admin-usuarios-index', 'api');
        $this->oculto->givePermissionTo('admin-usuarios-index');
        Passport::actingAs($this->oculto, [], 'api');

        $this->getJson("/api/usuarios/{$this->oculto->id}")->assertOk()->assertJsonPath('user.name', 'Superadmin Secreto');
    }

    public function test_el_artista_no_ve_el_nombre_del_oculto_en_su_historial_de_revisiones(): void
    {
        $persona = $this->aprobadoPorElOculto();
        Passport::actingAs($persona->user, [], 'api');

        $historial = $this->getJson('/api/mi-informacion')->assertOk()->json('persona.revisiones');

        $this->assertSame('Gerencia de Cultura y Turismo', $historial[0]['usuario']['name']);
        $this->assertStringNotContainsString('Superadmin Secreto', json_encode($historial));
    }

    public function test_la_ficha_pdf_no_imprime_el_nombre_del_oculto(): void
    {
        $persona = $this->aprobadoPorElOculto();
        Passport::actingAs($persona->user, [], 'api');

        $this->assertSame('Gerencia de Cultura y Turismo', FichaRegistro::datos($persona->fresh())['validado_por']);
    }
}
