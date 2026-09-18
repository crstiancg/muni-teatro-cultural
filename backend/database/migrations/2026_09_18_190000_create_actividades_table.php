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
        Schema::create('actividades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('persona_id')->constrained('personas')->cascadeOnDelete();
            $table->text('descripcion');
            // acá solo se acepta imagen (no PDF), a diferencia de formacion_academicas/capacitaciones
            $table->string('imagen_path')->nullable();
            $table->string('imagen_nombre_original')->nullable();
            // permite "anular" un registro (ocultarlo) sin borrarlo ni perder la imagen
            $table->boolean('flag_activo')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('actividades');
    }
};
