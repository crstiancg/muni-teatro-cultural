<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Passport\Passport;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

// Dar de baja a un usuario lo saca de TODOS los dispositivos al instante. Los
// tokens de Passport duran un año: marcarlo "de baja" sin revocarlos lo dejaría
// adentro hasta que venzan. Tres capas: se revocan los tokens (acceso y
// refresco), el login lo rechaza y cada request vuelve a verificarlo.
class BajaUsuarioTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $usuario;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create();
        Permission::findOrCreate('admin-usuarios-baja', 'api');
        $this->admin->givePermissionTo('admin-usuarios-baja');
        $this->usuario = User::factory()->create(['email' => 'ana@cpc.test', 'password' => bcrypt('clave-segura')]);
    }

    // como si hubiera iniciado sesión en un dispositivo: token de acceso + de refresco
    private function sesion(User $usuario): string
    {
        $id = Str::random(80);
        DB::table('oauth_access_tokens')->insert([
            'id' => $id, 'user_id' => $usuario->id, 'client_id' => Str::uuid(), 'scopes' => '[]',
            'revoked' => false, 'created_at' => now(), 'updated_at' => now(), 'expires_at' => now()->addYear(),
        ]);
        DB::table('oauth_refresh_tokens')->insert([
            'id' => Str::random(80), 'access_token_id' => $id, 'revoked' => false, 'expires_at' => now()->addYear(),
        ]);

        return $id;
    }

    private function darDeBaja(User $usuario, array $datos = ['motivo' => 'Dejó de trabajar en la Gerencia'])
    {
        Passport::actingAs($this->admin, [], 'api');

        return $this->putJson("/api/usuarios/{$usuario->id}/baja", $datos);
    }

    public function test_la_baja_cierra_la_sesion_en_todos_los_dispositivos(): void
    {
        $celular = $this->sesion($this->usuario);
        $oficina = $this->sesion($this->usuario);
        $otroUsuario = $this->sesion($this->admin);

        $this->darDeBaja($this->usuario)->assertOk();

        $this->assertSame(0, DB::table('oauth_access_tokens')->where('user_id', $this->usuario->id)->where('revoked', false)->count());
        $this->assertSame(0, DB::table('oauth_refresh_tokens')->whereIn('access_token_id', [$celular, $oficina])->where('revoked', false)->count());
        // las sesiones de los demás no se tocan
        $this->assertFalse((bool) DB::table('oauth_access_tokens')->where('id', $otroUsuario)->value('revoked'));
    }

    public function test_queda_registrado_cuando_por_que_y_quien(): void
    {
        $this->darDeBaja($this->usuario)->assertOk()->assertJsonPath('de_baja', true);

        $this->usuario->refresh();
        $this->assertNotNull($this->usuario->baja_en);
        $this->assertSame('Dejó de trabajar en la Gerencia', $this->usuario->baja_motivo);
        $this->assertSame($this->admin->id, $this->usuario->baja_por);
    }

    public function test_dado_de_baja_no_puede_iniciar_sesion_y_se_le_dice_por_que(): void
    {
        $this->darDeBaja($this->usuario);

        $this->postJson('/api/login', ['email' => 'ana@cpc.test', 'password' => 'clave-segura'])
            ->assertForbidden()
            ->assertJsonPath('cuenta_de_baja', true);
    }

    public function test_con_la_clave_incorrecta_no_revela_que_la_cuenta_esta_de_baja(): void
    {
        $this->darDeBaja($this->usuario);

        $respuesta = $this->postJson('/api/login', ['email' => 'ana@cpc.test', 'password' => 'otra-cosa']);

        $this->assertNotSame(403, $respuesta->status());
        $this->assertNull($respuesta->json('cuenta_de_baja'));
    }

    public function test_si_le_quedara_un_token_vivo_igual_lo_rechaza_en_cada_peticion(): void
    {
        $this->usuario->forceFill(['baja_en' => now()])->save();
        Passport::actingAs($this->usuario, [], 'api');

        $this->getJson('/api/user')->assertUnauthorized()->assertJsonPath('cuenta_de_baja', true);
    }

    public function test_reactivar_le_devuelve_el_acceso(): void
    {
        $this->darDeBaja($this->usuario);

        $this->putJson("/api/usuarios/{$this->usuario->id}/reactivar")->assertOk()->assertJsonPath('de_baja', false);

        $this->assertNull($this->usuario->fresh()->baja_en);
        Passport::actingAs($this->usuario->fresh(), [], 'api');
        $this->getJson('/api/user')->assertOk();
    }

    public function test_no_puede_darse_de_baja_a_si_mismo_y_el_motivo_es_obligatorio(): void
    {
        $this->darDeBaja($this->admin)->assertUnprocessable();
        $this->darDeBaja($this->usuario, [])->assertUnprocessable()->assertJsonValidationErrors('motivo');
        $this->assertNull($this->admin->fresh()->baja_en);
    }

    public function test_sin_permiso_no_puede_dar_de_baja(): void
    {
        Passport::actingAs(User::factory()->create(), [], 'api');

        $this->putJson("/api/usuarios/{$this->usuario->id}/baja", ['motivo' => 'x'])->assertForbidden();
        $this->assertNull($this->usuario->fresh()->baja_en);
    }

    public function test_al_usuario_oculto_nadie_lo_da_de_baja(): void
    {
        $oculto = User::factory()->create();
        config(['acceso.usuarios_ocultos' => [$oculto->id]]);

        $this->darDeBaja($oculto)->assertNotFound();
        $this->assertNull($oculto->fresh()->baja_en);
    }
}
