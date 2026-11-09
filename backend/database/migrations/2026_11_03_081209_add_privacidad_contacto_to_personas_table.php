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
        Schema::table('personas', function (Blueprint $table) {
            // el artista decide si su celular y su correo se muestran en el perfil
            // público; el admin siempre los ve
            $table->boolean('flag_celular_publico')->default(true)->after('celular_emergencia');
            $table->boolean('flag_correo_publico')->default(true)->after('correo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('personas', function (Blueprint $table) {
            $table->dropColumn(['flag_celular_publico', 'flag_correo_publico']);
        });
    }
};