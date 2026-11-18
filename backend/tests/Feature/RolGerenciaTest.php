<?php

namespace Tests\Feature;

use App\Models\Persona;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

// Rol para los admins de la Gerencia que no somos nosotros (el superadmin es el
// usuario oculto con rol Administrador). Gestionan el día a día, pero:
// - no tocan usuarios, roles ni permisos: con eso podrían darse el rol
//   Administrador a sí mismos (escalada de privilegios);
// - no borran nada: las eliminaciones quedan para el Administrador.
class RolGerenciaTest extends TestCase
{
    use RefreshDatabase;

    private const ROL = 'Administrador de la Gerencia';

    private function gestor(): User
    {
        $this->artisan('permisos:sincronizar')->assertSuccessful();
        $usuario = User::factory()->create();
        $usuario->assignRole(self::ROL);

        return $usuario;
    }

    public function test_el_rol_existe_sin_permisos_de_usuarios_roles_permisos_ni_de_borrar(): void
    {
        $this->artisan('permisos:sincronizar')->assertSuccessful();
        $permisos = Role::findByName(self::ROL, 'api')->permissions->pluck('name');

        $this->assertContains('admin-personas-aprobar', $permisos);
        $this->assertContains('admin-eventos-aprobar', $permisos);
        $this->assertContains('admin-agrupaciones-aprobar', $permisos);
        foreach ($permisos as $permiso) {
            $this->assertDoesNotMatchRegularExpression('/^admin-(usuarios|roles|permisos)-/', $permiso);
            $this->assertStringEndsNotWith('-eliminar', $permiso);
        }
    }

    public function test_gestiona_personas_pero_no_entra_a_usuarios_ni_roles_ni_borra(): void
    {
        Passport::actingAs($this->gestor(), [], 'api');
        $persona = Persona::create([
            'dni' => '41000001', 'nombre' => 'Ana', 'apellido_paterno' => 'Quispe', 'apellido_materno' => 'Mamani',
            'nombre_completo' => 'Ana Quispe Mamani', 'codigo_comision' => '0101', 'estado' => 'pendiente',
            'user_id' => User::factory()->create()->id,
        ]);

        $this->getJson('/api/personas')->assertOk();
        $this->putJson("/api/personas/{$persona->id}/aprobar")->assertOk();

        $this->getJson('/api/usuarios')->assertForbidden();
        $this->getJson('/api/roles')->assertForbidden();
        $this->getJson('/api/permisos')->assertForbidden();
        $this->deleteJson("/api/personas/{$persona->id}")->assertForbidden();
    }

    public function test_lo_que_se_le_quita_desde_el_panel_no_vuelve_con_el_siguiente_deploy(): void
    {
        $this->artisan('permisos:sincronizar');
        Role::findByName(self::ROL, 'api')->revokePermissionTo('admin-eventos-aprobar');

        $this->artisan('permisos:sincronizar'); // como en cada migrate de Dokploy

        $this->assertFalse(Role::findByName(self::ROL, 'api')->hasPermissionTo('admin-eventos-aprobar'));
    }
}
