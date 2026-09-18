<?php

namespace Database\Seeders;

use App\Models\Actividad;
use App\Models\Comision;
use App\Models\Persona;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

// Seeder de datos de PRUEBA (no forma parte del flujo normal de instalación):
// crea ~100 personas ficticias con comisión asignada y algunas actividades
// con imágenes reales de picsum.photos, para poder ver la galería pública
// con contenido real en vez de vacía. Correr a mano con:
//   php artisan db:seed --class=ArtistasDemoSeeder
class ArtistasDemoSeeder extends Seeder
{
    public function run(): void
    {
        $familias = Comision::where('tipo', 'familia')->pluck('codigo')->all();

        if (empty($familias)) {
            $this->command?->warn('No hay comisiones tipo "familia" — corré ComisionSeeder primero.');
            return;
        }

        // bajamos un pool chico de imágenes reales una sola vez y las vamos
        // reutilizando (copiando con nombre nuevo) para no hacer 100+ requests
        $pool = $this->descargarPool(20);

        if (empty($pool)) {
            $this->command?->warn('No se pudo descargar ninguna imagen de prueba (¿sin internet?). Sigo sin imágenes.');
        }

        for ($i = 1; $i <= 100; $i++) {
            $dni = (string) random_int(10000000, 99999999);
            $nombre = fake()->firstName();
            $apellidoPaterno = fake()->lastName();
            $apellidoMaterno = fake()->lastName();
            $nombreCompleto = trim("$nombre $apellidoPaterno $apellidoMaterno");

            $user = User::create([
                'name' => $nombreCompleto,
                'email' => Str::slug($nombreCompleto) . $i . '@ejemplo.com',
                'password' => bcrypt($dni),
            ]);

            $persona = Persona::create([
                'dni' => $dni,
                'nombre' => $nombre,
                'apellido_paterno' => $apellidoPaterno,
                'apellido_materno' => $apellidoMaterno,
                'nombre_completo' => $nombreCompleto,
                'correo' => $user->email,
                'genero' => fake()->randomElement(['masculino', 'femenino']),
                'codigo_comision' => fake()->randomElement($familias),
                'user_id' => $user->id,
            ]);

            if (empty($pool)) {
                continue;
            }

            $cantidadActividades = random_int(1, 4);
            for ($j = 0; $j < $cantidadActividades; $j++) {
                $origen = fake()->randomElement($pool);
                $nuevoPath = 'actividades/' . Str::random(40) . '.jpg';
                Storage::disk('public')->put($nuevoPath, Storage::disk('public')->get($origen));

                Actividad::create([
                    'persona_id' => $persona->id,
                    'descripcion' => fake()->sentence(10),
                    'imagen_path' => $nuevoPath,
                    'imagen_nombre_original' => 'actividad.jpg',
                    'flag_activo' => true,
                    'flag_publico' => fake()->boolean(80),
                ]);
            }
        }

        // el pool era solo un préstamo temporal para copiar de ahí
        foreach ($pool as $path) {
            Storage::disk('public')->delete($path);
        }

        $this->command?->info('Listo: 100 personas de prueba creadas con sus actividades.');
    }

    private function descargarPool(int $cantidad): array
    {
        $paths = [];

        for ($i = 1; $i <= $cantidad; $i++) {
            try {
                $respuesta = Http::timeout(10)->get("https://picsum.photos/seed/artista{$i}/600/600");
                if (! $respuesta->successful()) {
                    continue;
                }

                $path = 'actividades_pool_tmp/' . Str::random(20) . '.jpg';
                Storage::disk('public')->put($path, $respuesta->body());
                $paths[] = $path;
            } catch (\Throwable $e) {
                continue;
            }
        }

        return $paths;
    }
}
