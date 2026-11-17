<?php

namespace Tests\Feature;

use App\Models\Persona;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use Laravel\Passport\Passport;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

// Sin lang/es, Laravel muestra la clave cruda ("validation.required") en cada
// formulario. Pasó en producción con APP_LOCALE=es: los mensajes tienen que
// salir en español y con el nombre del campo, no "formacion.centro_estudios".
class TraduccionesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app()->setLocale('es');
    }

    public function test_esta_traducida_cada_regla_que_trae_laravel(): void
    {
        $ingles = require base_path('vendor/laravel/framework/src/Illuminate/Translation/lang/en/validation.php');
        $espanol = require lang_path('es/validation.php');

        $claves = fn (array $mensajes) => array_keys(Arr::dot(Arr::except($mensajes, ['custom', 'attributes'])));

        $this->assertSame([], array_values(array_diff($claves($ingles), $claves($espanol))), 'Reglas sin traducir en lang/es/validation.php');
    }

    public function test_el_mensaje_sale_en_espanol_con_el_nombre_legible_del_campo(): void
    {
        $errores = Validator::make(
            ['formacion' => ['profesion' => str_repeat('a', 300)]],
            ['formacion.centro_estudios' => 'required', 'formacion.profesion' => 'max:255'],
        )->errors();

        $this->assertSame('El campo centro de estudios es obligatorio.', $errores->first('formacion.centro_estudios'));
        $this->assertSame('El campo profesión no debe tener más de 255 caracteres.', $errores->first('formacion.profesion'));
    }

    public function test_la_api_responde_los_errores_de_validacion_en_espanol(): void
    {
        $admin = User::factory()->create();
        Permission::findOrCreate('admin-personas-aprobar', 'api');
        $admin->givePermissionTo('admin-personas-aprobar');
        Passport::actingAs($admin, [], 'api');
        $persona = Persona::create([
            'dni' => '41000001', 'nombre' => 'Ana', 'apellido_paterno' => 'Quispe', 'apellido_materno' => 'Mamani',
            'nombre_completo' => 'Ana Quispe Mamani', 'codigo_comision' => '0101', 'estado' => 'pendiente',
            'user_id' => User::factory()->create()->id,
        ]);

        $this->putJson("/api/personas/{$persona->id}/observar", [])
            ->assertUnprocessable()
            ->assertJsonPath('errors.observacion.0', 'El campo observación es obligatorio.');
    }
}
