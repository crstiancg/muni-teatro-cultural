<?php

namespace Tests\Feature;

use App\Models\Persona;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

// "Mi espacio" del panel (perfil y CV, agrupaciones, eventos) se controla con
// PERMISOS del rol Artista, igual que el panel de administración: nada de
// decidir por el nombre del rol. El backend los exige: quitarle un permiso a
// alguien desde el panel le corta el acceso de verdad, no solo el menú.
class ArtistaPermisosTest extends TestCase
{
    use RefreshDatabase;

    private const PERMISOS = ['artista-perfil', 'artista-agrupaciones', 'artista-eventos'];

    private function persona(string $dni = '41000001'): Persona
    {
        return Persona::create([
            'dni' => $dni, 'nombre' => 'Ana', 'apellido_paterno' => 'Quispe', 'apellido_materno' => 'Mamani',
            'nombre_completo' => "Ana Quispe {$dni}", 'codigo_comision' => '0101',
            'user_id' => User::factory()->create()->id,
        ]);
    }

    public function test_el_rol_artista_tiene_sus_permisos_y_el_administrador_no(): void
    {
        $this->artisan('permisos:sincronizar')->assertSuccessful();

        $artista = Role::findByName('Artista', 'api');
        foreach (self::PERMISOS as $permiso) {
            $this->assertTrue($artista->hasPermissionTo($permiso), "Artista sin {$permiso}");
            // si el Administrador los tuviera, volvería a ver "Mi espacio"
            $this->assertFalse(Role::findByName('Administrador', 'api')->hasPermissionTo($permiso), "Administrador con {$permiso}");
            $this->assertFalse(Role::findByName('Administrador de la Gerencia', 'api')->hasPermissionTo($permiso));
        }
    }

    public function test_una_ficha_de_persona_nueva_le_da_el_rol_artista_a_su_cuenta(): void
    {
        $persona = $this->persona();

        $this->assertTrue($persona->user->hasRole('Artista'));
        $this->assertContains('artista-eventos', $this->actingAs($persona->user, 'api')->getJson('/api/user')->json('permisos'));
    }

    public function test_los_artistas_que_ya_existian_reciben_el_rol_al_crearse(): void
    {
        Role::where('name', 'Artista')->delete();          // como en una base anterior a este cambio
        $persona = $this->persona();                        // sin rol: todavía no existía
        $this->assertFalse($persona->user->fresh()->hasRole('Artista'));

        $this->artisan('permisos:sincronizar')->assertSuccessful(); // primer deploy con el cambio

        $this->assertTrue($persona->user->fresh()->hasRole('Artista'));
    }

    public function test_el_backend_exige_el_permiso_no_solo_el_menu(): void
    {
        $persona = $this->persona();
        Passport::actingAs($persona->user, [], 'api');
        $this->getJson('/api/mis-eventos')->assertOk();

        Role::findByName('Artista', 'api')->revokePermissionTo('artista-eventos');
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
        Passport::actingAs($persona->user->fresh(), [], 'api');

        $this->getJson('/api/mis-eventos')->assertForbidden();
        $this->getJson('/api/mis-agrupaciones')->assertOk(); // los otros permisos siguen
    }

    public function test_un_admin_sin_ficha_ni_permisos_de_artista_no_entra_a_mi_espacio(): void
    {
        $this->artisan('permisos:sincronizar');
        $gestor = User::factory()->create();
        $gestor->assignRole('Administrador de la Gerencia');
        Passport::actingAs($gestor, [], 'api');

        $this->getJson('/api/mis-eventos')->assertForbidden();
        $this->getJson('/api/mis-agrupaciones')->assertForbidden();
        $this->postJson('/api/mi-informacion/actividades', [])->assertForbidden();
        // sus datos de cuenta sí (los usa desde el avatar)
        $this->getJson('/api/mi-informacion')->assertOk();
    }
}
