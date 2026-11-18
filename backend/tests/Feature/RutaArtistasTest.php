<?php

namespace Tests\Feature;

use App\Models\Comision;
use App\Models\Persona;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// El directorio pasó de /consejeros a /artistas (lo que dice el menú). Los links
// viejos ya circulan (perfiles compartidos, Google, fichas impresas): redirigen
// con 301, que además le pasa a Google el posicionamiento de la URL vieja.
class RutaArtistasTest extends TestCase
{
    use RefreshDatabase;

    private Persona $persona;

    protected function setUp(): void
    {
        parent::setUp();
        Comision::create(['codigo' => '0100', 'tipo' => 'grupo', 'cod_grupo' => '01', 'cod_familia' => '00', 'nombre' => 'Comisión especializada de música']);
        Comision::create(['codigo' => '0101', 'tipo' => 'familia', 'cod_grupo' => '01', 'cod_familia' => '01', 'nombre' => 'Conjunto de sikuris']);
        $this->persona = Persona::create([
            'dni' => '41000001', 'nombre' => 'María', 'apellido_paterno' => 'Quispe', 'apellido_materno' => 'Mamani',
            'nombre_completo' => 'María Quispe Mamani', 'codigo_comision' => '0101', 'estado' => 'aprobado',
            'user_id' => User::factory()->create()->id,
        ]);
    }

    public function test_el_directorio_y_el_perfil_viven_en_artistas(): void
    {
        $this->get('/artistas')->assertOk()->assertSee('/artistas"', false);
        $this->get("/artistas/{$this->persona->slug}")->assertOk()->assertSee("/artistas/{$this->persona->slug}\"", false);
    }

    public function test_los_links_viejos_de_consejeros_redirigen_con_301(): void
    {
        $slug = $this->persona->slug;

        $this->get('/consejeros')->assertStatus(301)->assertRedirect('/artistas');
        $this->get("/consejeros/{$slug}")->assertStatus(301)->assertRedirect("/artistas/{$slug}");
        $this->get("/consejeros/{$slug}/galeria")->assertStatus(301)->assertRedirect("/artistas/{$slug}/galeria");
        // la búsqueda (cuadro de búsqueda de Google, links compartidos) conserva sus parámetros
        $this->get('/consejeros?buscar=sikuris&grupo=01')->assertStatus(301)->assertRedirect('/artistas?buscar=sikuris&grupo=01');
    }

    public function test_compartir_y_el_sitemap_apuntan_a_artistas(): void
    {
        $slug = $this->persona->slug;

        // una persona que abre el link compartido llega al perfil nuevo
        $this->get("/compartir/{$slug}", ['User-Agent' => 'Mozilla/5.0 (Linux; Android 14) Chrome/130'])
            ->assertRedirectContains("/artistas/{$slug}");
        // el bot de WhatsApp recibe la tarjeta con la canónica nueva
        $this->get("/compartir/{$slug}", ['User-Agent' => 'WhatsApp/2.24.1 A'])
            ->assertOk()
            ->assertSee("/artistas/{$slug}\">", false)
            ->assertDontSee('/consejeros', false);

        $sitemap = $this->get('/sitemap.xml')->getContent();
        $this->assertStringContainsString("/artistas/{$slug}</loc>", $sitemap);
        $this->assertStringNotContainsString('/consejeros', $sitemap);
    }
}
