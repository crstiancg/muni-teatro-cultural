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
                'nombre' => 'Comision especializada de musica',
                'familias' => ['Banda de musicos', 'Estudiantina', 'Orquesta tipica', 'Conjunto de sikuris'],
            ],
            '02' => [
                'nombre' => 'Comision especializada de danza',
                'familias' => ['Danza autoctona', 'Danza de luces', 'Danza mestiza'],
            ],
            '03' => [
                'nombre' => 'Comision especializada de artes escenicas - teatro',
                'familias' => ['Teatro', 'Titeres', 'Pasacalle teatral'],
            ],
            '04' => [
                'nombre' => 'Comision especializada de literatura y expresiones orales',
                'familias' => ['Poesia', 'Narrativa oral', 'Cuento y leyenda'],
            ],
            '05' => [
                'nombre' => 'Comision especializada de federacion regional de artistas bordadores, mascareros y trajes tipicos',
                'familias' => ['Bordadores', 'Mascareros', 'Confeccion de trajes tipicos'],
            ],
            '06' => [
                'nombre' => 'Comision especializada de audiovisuales',
                'familias' => ['Fotografia', 'Video documental', 'Cine y animacion'],
            ],
            '07' => [
                'nombre' => 'Comision especializada de artes plasticas',
                'familias' => ['Pintura', 'Escultura', 'Artesania'],
            ],
            '08' => [
                'nombre' => 'Comision especializada de promocion, difusion y gestion cultural',
                'familias' => ['Prensa y difusion', 'Gestion de eventos', 'Promocion turistica cultural'],
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
