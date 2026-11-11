<?php

namespace Tests\Feature;

use App\Models\Agrupacion;
use App\Models\Evento;
use App\Models\Persona;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

// Los contadores del menú lateral: cuántas cosas esperan revisión en cada
// sección. Cada usuario ve SOLO los de lo que puede revisar.
class PanelContadoresTest extends TestCase
{
    use RefreshDatabase;

    private function usuario(array $permisos = []): User
    {
        $user = User::factory()->create();
        foreach ($permisos as $permiso) {
            Permission::findOrCreate($permiso, 'api');
            $user->givePermissionTo($permiso);
        }
        Passport::actingAs($user, [], 'api');

        return $user;
    }

    private function evento(string $estado): Evento
    {
        return Evento::create([
            'titulo' => 'Evento',
            'fecha_inicio' => '2026-11-20 20:00',
            'modalidad' => 'presencial',
            'lugar' => 'Teatro',
            'organizador_tipo' => 'persona',
            'estado' => $estado,
        ]);
    }

    public function test_cuenta_los_eventos_y_agrupaciones_pendientes_que_puede_revisar(): void
    {
        $this->usuario(['admin-eventos-aprobar', 'admin-agrupaciones-aprobar']);
        $this->evento('pendiente');
        $this->evento('pendiente');
        $this->evento('aprobado');
        $this->evento('observado');
        Agrupacion::create(['nombre' => 'Uno', 'estado' => 'pendiente']);
        Agrupacion::create(['nombre' => 'Dos', 'estado' => 'aprobado']);

        $this->getJson('/api/panel/contadores')
            ->assertOk()
            ->assertExactJson(['eventos' => 2, 'agrupaciones' => 1]);
    }

    public function test_cuenta_los_perfiles_de_artistas_en_revision(): void
    {
        $this->usuario(['admin-personas-aprobar']);
        foreach (['pendiente', 'pendiente', 'aprobado', 'borrador'] as $i => $estado) {
            Persona::create([
                'dni' => '4200000' . $i,
                'nombre' => 'Ana',
                'apellido_paterno' => 'Quispe',
                'apellido_materno' => 'Mamani',
                'nombre_completo' => "Ana Quispe Mamani {$i}",
                'user_id' => User::factory()->create()->id,
                'estado' => $estado,
            ]);
        }

        $this->getJson('/api/panel/contadores')
            ->assertOk()
            ->assertExactJson(['personas' => 2]);
    }

    public function test_sin_permisos_de_revision_no_recibe_contadores(): void
    {
        $this->usuario();
        $this->evento('pendiente');

        $this->getJson('/api/panel/contadores')
            ->assertOk()
            ->assertExactJson([]);
    }

    public function test_requiere_sesion(): void
    {
        $this->getJson('/api/panel/contadores')->assertUnauthorized();
    }
}
