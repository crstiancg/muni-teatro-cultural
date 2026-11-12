<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// Los nombres sembrados por ComisionSeeder iban sin tildes ("Banda de musicos",
// "Confeccion de trajes tipicos") y así salían en el portal. Solo se corrigen
// las filas que siguen EXACTAMENTE con el texto original: si un admin ya las
// renombró desde el panel, no se tocan.
return new class extends Migration
{
    public const NOMBRES = [
        'Comision especializada de musica' => 'Comisión especializada de música',
        'Banda de musicos' => 'Banda de músicos',
        'Orquesta tipica' => 'Orquesta típica',
        'Comision especializada de danza' => 'Comisión especializada de danza',
        'Danza autoctona' => 'Danza autóctona',
        'Comision especializada de artes escenicas - teatro' => 'Comisión especializada de artes escénicas - teatro',
        'Titeres' => 'Títeres',
        'Comision especializada de literatura y expresiones orales' => 'Comisión especializada de literatura y expresiones orales',
        'Poesia' => 'Poesía',
        'Comision especializada de federacion regional de artistas bordadores, mascareros y trajes tipicos' => 'Comisión especializada de la federación regional de artistas bordadores, mascareros y trajes típicos',
        'Confeccion de trajes tipicos' => 'Confección de trajes típicos',
        'Comision especializada de audiovisuales' => 'Comisión especializada de audiovisuales',
        'Fotografia' => 'Fotografía',
        'Cine y animacion' => 'Cine y animación',
        'Comision especializada de artes plasticas' => 'Comisión especializada de artes plásticas',
        'Artesania' => 'Artesanía',
        'Comision especializada de promocion, difusion y gestion cultural' => 'Comisión especializada de promoción, difusión y gestión cultural',
        'Prensa y difusion' => 'Prensa y difusión',
        'Gestion de eventos' => 'Gestión de eventos',
        'Promocion turistica cultural' => 'Promoción turística cultural',
    ];

    public function up(): void
    {
        foreach (self::NOMBRES as $sinTilde => $conTilde) {
            DB::table('comisions')->where('nombre', $sinTilde)->update(['nombre' => $conTilde]);
        }
    }

    public function down(): void
    {
        foreach (self::NOMBRES as $sinTilde => $conTilde) {
            DB::table('comisions')->where('nombre', $conTilde)->update(['nombre' => $sinTilde]);
        }
    }
};
