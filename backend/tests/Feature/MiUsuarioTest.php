<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Passport\Passport;
use Tests\TestCase;

// PUT /api/mi-usuario cambia solo el nombre y el correo. La contraseña tiene su
// propio endpoint (mi-password) con sus validaciones: no se puede colar por acá.
class MiUsuarioTest extends TestCase
{
    use RefreshDatabase;

    public function test_cambia_nombre_y_correo(): void
    {
        $user = User::factory()->create();
        Passport::actingAs($user, [], 'api');

        $this->putJson('/api/mi-usuario', ['usuario' => ['name' => 'Ana Quispe', 'email' => 'ana@correo.pe']])->assertOk();

        $this->assertSame('ana@correo.pe', $user->fresh()->email);
    }

    public function test_no_permite_cambiar_la_contrasena_salteando_mi_password(): void
    {
        $user = User::factory()->create(['password' => 'clave-original']);
        Passport::actingAs($user, [], 'api');

        $this->putJson('/api/mi-usuario', ['usuario' => [
            'name' => $user->name,
            'email' => $user->email,
            'password' => 'clave-colada',
        ]])->assertOk();

        $this->assertTrue(Hash::check('clave-original', $user->fresh()->password));
    }

    public function test_no_permite_escribir_campos_que_no_valida(): void
    {
        $user = User::factory()->create();
        Passport::actingAs($user, [], 'api');

        $this->putJson('/api/mi-usuario', ['usuario' => [
            'name' => $user->name,
            'email' => $user->email,
            'preferencias' => ['acento' => '<script>'],
        ]])->assertOk();

        $this->assertNull($user->fresh()->preferencias);
    }
}
