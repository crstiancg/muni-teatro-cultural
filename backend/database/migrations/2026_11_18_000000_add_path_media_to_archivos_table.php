<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Versión "media" en WebP (1200 px) de cada imagen subida: las tarjetas grandes
// de la portada se ven a ~550 px y en pantallas 2x necesitan ~1100 px. La
// miniatura (600 px) se vería borrosa y el original (hasta 2400 px) pesa de más.
// Las existentes las genera "php artisan imagenes:miniaturas".
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('archivos', function (Blueprint $table) {
            $table->string('path_media')->nullable()->after('path_miniatura');
        });
    }

    public function down(): void
    {
        Schema::table('archivos', function (Blueprint $table) {
            $table->dropColumn('path_media');
        });
    }
};
