<?php

namespace Database\Seeders;

use App\Models\Comision;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ComisionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // una "familia" ES la comisión; pero el cascade select del frontend
        // primero pide el "grupo" para recién ahí filtrar familias, así que
        // sembramos ambos niveles con la misma convención de códigos que usa
        // ComisionController (grupo = cod_grupo+'00', familia = cod_grupo+cod_familia)
        $grupos = [
            '01' => [
                'nombre' => 'Comisión especializada de música',
                'familias' => ['Banda de músicos', 'Estudiantina', 'Orquesta típica', 'Conjunto de sikuris'],
            ],
            '02' => [
                'nombre' => 'Comisión especializada de danza',
                'familias' => ['Danza autóctona', 'Danza de luces', 'Danza mestiza'],
            ],
            '03' => [
                'nombre' => 'Comisión especializada de artes escénicas - teatro',
                'familias' => ['Teatro', 'Títeres', 'Pasacalle teatral'],
            ],
            '04' => [
                'nombre' => 'Comisión especializada de literatura y expresiones orales',
                'familias' => ['Poesía', 'Narrativa oral', 'Cuento y leyenda'],
            ],
            '05' => [
                'nombre' => 'Comisión especializada de la federación regional de artistas bordadores, mascareros y trajes típicos',
                'familias' => ['Bordadores', 'Mascareros', 'Confección de trajes típicos'],
            ],
            '06' => [
                'nombre' => 'Comisión especializada de audiovisuales',
                'familias' => ['Fotografía', 'Video documental', 'Cine y animación'],
            ],
            '07' => [
                'nombre' => 'Comisión especializada de artes plásticas',
                'familias' => ['Pintura', 'Escultura', 'Artesanía'],
            ],
            '08' => [
                'nombre' => 'Comisión especializada de promoción, difusión y gestión cultural',
                'familias' => ['Prensa y difusión', 'Gestión de eventos', 'Promoción turística cultural'],
            ],
        ];

        foreach ($grupos as $codGrupo => $grupo) {
            Comision::create([
                'codigo' => $codGrupo . '00',
                'tipo' => 'grupo',
                'cod_grupo' => $codGrupo,
                'cod_familia' => '00',
                'nombre' => $grupo['nombre'],
            ]);

            foreach ($grupo['familias'] as $i => $nombreFamilia) {
                $codFamilia = str_pad($i + 1, 2, '0', STR_PAD_LEFT);

                Comision::create([
                    'codigo' => $codGrupo . $codFamilia,
                    'tipo' => 'familia',
                    'cod_grupo' => $codGrupo,
                    'cod_familia' => $codFamilia,
                    'nombre' => $nombreFamilia,
                ]);
            }
        }
    }
}
