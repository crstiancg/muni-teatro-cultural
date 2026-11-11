<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;

// Cada usuario elige el tema del panel entre opciones curadas (acento, neutro,
// modo) y se guarda en su cuenta: lo ve igual en cualquier dispositivo.
class PreferenciasPanelTest extends TestCase
{
    use RefreshDatabase;

    private function usuario(?array $preferencias = null): User
    {
        $user = User::factory()->create(['preferencias' => $preferencias]);
        Passport::actingAs($user, [], 'api');

        return $user;
    }

    public function test_sin_preferencias_guardadas_recibe_el_tema_por_defecto(): void
    {
        $this->usuario();

        $this->getJson('/api/user')
            ->assertOk()
            ->assertJsonPath('preferencias', ['acento' => 'rojo', 'neutro' => 'calido', 'modo' => 'claro']);
    }

    public function test_guarda_el_tema_elegido_y_lo_devuelve_al_cargar_el_usuario(): void
    {
        $user = $this->usuario();

        $this->putJson('/api/mi-usuario/preferencias', ['acento' => 'violeta', 'neutro' => 'frio', 'modo' => 'oscuro'])
            ->assertOk()
            ->assertJsonPath('acento', 'violeta');

        $this->assertSame(['acento' => 'violeta', 'neutro' => 'frio', 'modo' => 'oscuro'], $user->fresh()->preferenciasPanel());
        $this->getJson('/api/user')->assertJsonPath('preferencias.neutro', 'frio');
    }

    public function test_cambiar_una_sola_opcion_conserva_las_demas(): void
    {
        $user = $this->usuario(['acento' => 'azul', 'neutro' => 'frio', 'modo' => 'oscuro']);

        $this->putJson('/api/mi-usuario/preferencias', ['modo' => 'claro'])->assertOk();

        $this->assertSame(['acento' => 'azul', 'neutro' => 'frio', 'modo' => 'claro'], $user->fresh()->preferenciasPanel());
    }

    public function test_solo_acepta_los_temas_curados(): void
    {
        $this->usuario();

        // esmeralda y ámbar no son acentos: son los colores de "Publicado" y "Observado"
        $this->putJson('/api/mi-usuario/preferencias', ['acento' => 'esmeralda'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('acento');
        $this->putJson('/api/mi-usuario/preferencias', ['neutro' => '#ff00ff'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('neutro');
    }

    public function test_un_valor_guardado_que_ya_no_existe_vuelve_al_defecto(): void
    {
        // p. ej. un acento que se quitó de la lista después de que alguien lo eligió
        $user = $this->usuario(['acento' => 'naranja', 'neutro' => 'frio']);

        $this->assertSame(['acento' => 'rojo', 'neutro' => 'frio', 'modo' => 'claro'], $user->preferenciasPanel());
    }

    public function test_requiere_sesion(): void
    {
        $this->putJson('/api/mi-usuario/preferencias', ['modo' => 'oscuro'])->assertUnauthorized();
    }
}
