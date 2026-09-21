<?php

namespace Database\Seeders;

use App\Models\Actividad;
use App\Models\Comision;
use App\Models\Persona;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

// Crea 100 personas de prueba con sus actividades. A diferencia de
// ArtistasDemoSeeder, no descarga ningun archivo: en imagen_path guarda la URL
// de picsum y Actividad::resolverImagenUrl() la devuelve tal cual, asi que corre
// sin internet (las imagenes recien se piden cuando el navegador las muestra).
class PersonaSeeder extends Seeder
{
    private const CANTIDAD = 100;

    public function run(): void
    {
        $familias = Comision::where('tipo', 'familia')->pluck('codigo')->all();

        if (empty($familias)) {
            $this->command?->warn('No hay comisiones tipo "familia" — corré ComisionSeeder primero.');

            return;
        }

        $ubigeos = DB::table('ubigeos')->where('tipo', 'distrito')->pluck('codigo')->all();

        // los DNI y correos son unicos en la tabla, asi que los vamos apartando
        // en vez de confiar en que el azar no repita
        $dnisUsados = [];

        for ($i = 1; $i <= self::CANTIDAD; $i++) {
            do {
                $dni = (string) random_int(10000000, 99999999);
            } while (isset($dnisUsados[$dni]));
            $dnisUsados[$dni] = true;

            $genero = fake()->randomElement(['masculino', 'femenino']);
            $nombre = $genero === 'masculino' ? fake()->firstNameMale() : fake()->firstNameFemale();
            $apellidoPaterno = fake()->lastName();
            $apellidoMaterno = fake()->lastName();
            $nombreCompleto = trim("$nombre $apellidoPaterno $apellidoMaterno");
            $correo = Str::slug($nombreCompleto) . $i . '@ejemplo.com';

            $user = User::create([
                'name' => $nombreCompleto,
                'email' => $correo,
                'password' => bcrypt($dni),
            ]);

            $persona = Persona::create([
                'dni' => $dni,
                'nombre' => $nombre,
                'apellido_paterno' => $apellidoPaterno,
                'apellido_materno' => $apellidoMaterno,
                'nombre_completo' => $nombreCompleto,
                'genero' => $genero,
                'fecha_nacimiento' => fake()->dateTimeBetween('-70 years', '-18 years')->format('Y-m-d'),
                'direccion' => fake()->streetAddress(),
                'estado_civil' => fake()->randomElement(['soltero', 'casado', 'divorciado', 'viudo']),
                'celular' => '9' . fake()->numerify('########'),
                'celular_emergencia' => '9' . fake()->numerify('########'),
                'correo' => $correo,
                'ubigeo_cod_nacimiento' => $ubigeos ? fake()->randomElement($ubigeos) : null,
                'ubigeo_cod_residencia' => $ubigeos ? fake()->randomElement($ubigeos) : null,
                'codigo_comision' => fake()->randomElement($familias),
                'user_id' => $user->id,
            ]);

            foreach (range(1, random_int(1, 4)) as $j) {
                // el seed fija la imagen: la misma actividad devuelve siempre la
                // misma foto en vez de una distinta por recarga
                $seed = $persona->id . '-' . $j;

                Actividad::create([
                    'persona_id' => $persona->id,
                    'descripcion' => fake()->sentence(10),
                    'imagen_path' => "https://picsum.photos/seed/{$seed}/800/600",
                    'imagen_nombre_original' => "actividad-{$seed}.jpg",
                    'flag_activo' => true,
                    'flag_publico' => true,
                ]);
            }
        }

        $this->command?->info(self::CANTIDAD . ' personas de prueba creadas (imagenes por URL).');
    }
}
