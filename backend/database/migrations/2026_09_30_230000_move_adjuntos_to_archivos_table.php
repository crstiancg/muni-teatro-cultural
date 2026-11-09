<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Los adjuntos de formación, capacitaciones y actividades pasan a la tabla
// polimórfica "archivos" (ver App\Models\Concerns\TieneAdjunto).
// En desarrollo no se copian los datos: los adjuntos existentes se pierden.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('formacion_academicas', function (Blueprint $table) {
            $table->dropColumn(['archivo_path', 'archivo_nombre_original']);
        });

        Schema::table('capacitaciones', function (Blueprint $table) {
            $table->dropColumn(['archivo_path', 'archivo_nombre_original']);
        });

        Schema::table('actividades', function (Blueprint $table) {
            $table->dropColumn(['imagen_path', 'imagen_nombre_original']);
        });
    }

    public function down(): void
    {
        Schema::table('formacion_academicas', function (Blueprint $table) {
            $table->string('archivo_path')->nullable();
            $table->string('archivo_nombre_original')->nullable();
        });

        Schema::table('capacitaciones', function (Blueprint $table) {
            $table->string('archivo_path')->nullable();
            $table->string('archivo_nombre_original')->nullable();
        });

        Schema::table('actividades', function (Blueprint $table) {
            $table->string('imagen_path')->nullable();
            $table->string('imagen_nombre_original')->nullable();
        });
    }
};
