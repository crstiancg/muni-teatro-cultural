<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

// Los permisos se declaran en config/permisos.php y se crean solos: con
// "php artisan migrate" (en cada deploy) o con "php artisan permisos:sincronizar".
class SincronizarPermisosTest extends TestCase
{
    use RefreshDatabase;

    private function esperados(): array
    {
        $nombres = [];
        foreach (array_keys(config('permisos.modulos')) as $modulo) {
            foreach (array_keys(config('permisos.acciones')) as $accion) {
                $nombres[] = "admin-{$modulo}-{$accion}";
            }
        }

        return [...$nombres, ...array_keys(config('permisos.extra'))];
    }

    public function test_crea_todos_los_permisos_declarados_y_se_los_da_al_administrador(): void
    {
        Permission::query()->delete();

        $this->artisan('permisos:sincronizar')->assertSuccessful();

        $admin = Role::findByName('Administrador', 'api');
        foreach ($this->esperados() as $nombre) {
            $this->assertTrue($admin->hasPermissionTo($nombre, 'api'), "falta {$nombre}");
        }
    }

    public function test_incluye_el_modulo_de_eventos(): void
    {
        $this->assertContains('admin-eventos-index', $this->esperados());
    }

    public function test_correrlo_dos_veces_no_duplica_nada(): void
    {
        $this->artisan('permisos:sincronizar')->assertSuccessful();
        $total = Permission::count();

        $this->artisan('permisos:sincronizar')->assertSuccessful();

        $this->assertSame($total, Permission::count());
    }

    public function test_no_borra_permisos_que_no_estan_declarados(): void
    {
        Permission::create(['name' => 'permiso-creado-a-mano', 'guard_name' => 'api']);

        $this->artisan('permisos:sincronizar')->assertSuccessful();

        $this->assertTrue(Permission::where('name', 'permiso-creado-a-mano')->exists());
    }

    public function test_migrar_sincroniza_los_permisos_sin_correr_nada_mas(): void
    {
        // RefreshDatabase ya corrió las migraciones: los permisos tienen que estar
        $this->assertTrue(Permission::where('name', 'admin-eventos-index')->where('guard_name', 'api')->exists());
    }

    public function test_el_seeder_no_le_pisa_la_contrasena_al_admin_existente(): void
    {
        User::factory()->create(['email' => 'password@gmail.com', 'password' => Hash::make('mi-clave-segura')]);

        $this->seed(PermissionSeeder::class);

        $this->assertTrue(Hash::check('mi-clave-segura', User::where('email', 'password@gmail.com')->value('password')));
    }
}
