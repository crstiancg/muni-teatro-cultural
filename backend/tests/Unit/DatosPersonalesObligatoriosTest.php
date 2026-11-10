<?php

namespace Tests\Unit;

use App\Http\Requests\StoreMiInformacionRequest;
use App\Http\Requests\StorePersonaRequest;
use App\Models\Persona;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

// El admin puede registrar a una persona con lo mínimo (sin comisión, etc.),
// pero cuando la persona guarda SUS datos tiene que completarlos todos.
class DatosPersonalesObligatoriosTest extends TestCase
{
    private function erroresMiInformacion(array $persona): array
    {
        $request = new StoreMiInformacionRequest();
        $request->setUserResolver(fn () => new User());

        return Validator::make(['persona' => $persona], $request->rules())->errors()->toArray();
    }

    public function test_la_persona_no_puede_guardar_sus_datos_incompletos(): void
    {
        $errores = $this->erroresMiInformacion([]);

        foreach (Persona::DATOS_OBLIGATORIOS as $campo) {
            $this->assertArrayHasKey("persona.$campo", $errores, "persona.$campo debería ser obligatorio");
        }
    }

    public function test_la_comision_alternativa_sigue_siendo_opcional_para_la_persona(): void
    {
        $this->assertArrayNotHasKey('persona.codigo_comision_alternativo', $this->erroresMiInformacion([]));
    }

    public function test_el_admin_puede_registrar_una_persona_sin_comision(): void
    {
        $errores = Validator::make(['persona' => []], (new StorePersonaRequest())->rules())->errors()->toArray();

        $this->assertArrayNotHasKey('persona.codigo_comision', $errores);
        $this->assertArrayNotHasKey('persona.genero', $errores);
    }

    // persona con todos los obligatorios llenos y que declaró no tener discapacidad
    private function personaCompleta(array $cambios = []): Persona
    {
        return new Persona([
            ...array_fill_keys(Persona::DATOS_OBLIGATORIOS, 'x'),
            'flag_discapacidad' => false,
            ...$cambios,
        ]);
    }

    public function test_datos_completos_cuando_tiene_todos_los_obligatorios(): void
    {
        $this->assertTrue($this->personaCompleta()->datosPersonalesCompletos());
    }

    public function test_datos_incompletos_si_falta_la_comision(): void
    {
        $this->assertFalse($this->personaCompleta(['codigo_comision' => null])->datosPersonalesCompletos());
    }

    // --- discapacidad: dato sensible, la persona tiene que responder Sí o No ---

    public function test_la_persona_tiene_que_responder_si_tiene_discapacidad(): void
    {
        $this->assertArrayHasKey('persona.flag_discapacidad', $this->erroresMiInformacion([]));
    }

    public function test_si_tiene_discapacidad_tiene_que_indicar_el_tipo(): void
    {
        $errores = $this->erroresMiInformacion(['flag_discapacidad' => true]);

        $this->assertArrayHasKey('persona.tipo_discapacidad', $errores);
    }

    public function test_si_no_tiene_discapacidad_no_se_pide_el_tipo(): void
    {
        $errores = $this->erroresMiInformacion(['flag_discapacidad' => false]);

        $this->assertArrayNotHasKey('persona.flag_discapacidad', $errores);
        $this->assertArrayNotHasKey('persona.tipo_discapacidad', $errores);
    }

    public function test_rechaza_un_tipo_de_discapacidad_desconocido(): void
    {
        $errores = $this->erroresMiInformacion(['flag_discapacidad' => true, 'tipo_discapacidad' => 'otra']);

        $this->assertArrayHasKey('persona.tipo_discapacidad', $errores);
    }

    public function test_el_admin_puede_registrar_sin_responder_discapacidad(): void
    {
        $errores = Validator::make(['persona' => []], (new StorePersonaRequest())->rules())->errors()->toArray();

        $this->assertArrayNotHasKey('persona.flag_discapacidad', $errores);
        $this->assertArrayNotHasKey('persona.tipo_discapacidad', $errores);
    }

    public function test_datos_incompletos_si_no_respondio_discapacidad(): void
    {
        $this->assertFalse($this->personaCompleta(['flag_discapacidad' => null])->datosPersonalesCompletos());
    }

    public function test_datos_incompletos_si_tiene_discapacidad_sin_tipo(): void
    {
        $this->assertFalse($this->personaCompleta(['flag_discapacidad' => true])->datosPersonalesCompletos());
    }

    public function test_datos_completos_si_tiene_discapacidad_con_tipo(): void
    {
        $persona = $this->personaCompleta(['flag_discapacidad' => true, 'tipo_discapacidad' => 'visual']);

        $this->assertTrue($persona->datosPersonalesCompletos());
    }

    public function test_si_no_tiene_discapacidad_no_conserva_un_tipo_viejo(): void
    {
        $persona = $this->personaCompleta(['flag_discapacidad' => false, 'tipo_discapacidad' => 'visual']);

        $persona->normalizarDiscapacidad();

        $this->assertNull($persona->tipo_discapacidad);
    }
}
