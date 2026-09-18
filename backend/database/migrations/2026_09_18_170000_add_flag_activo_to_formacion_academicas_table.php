<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('formacion_academicas', function (Blueprint $table) {
            // permite "anular" un registro (ocultarlo) sin borrarlo ni perder el archivo adjunto
            $table->boolean('flag_activo')->default(true)->after('archivo_nombre_original');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('formacion_academicas', function (Blueprint $table) {
            $table->dropColumn('flag_activo');
        });
    }
};
