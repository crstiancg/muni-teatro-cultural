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
        Schema::create('capacitaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('persona_id')->constrained('personas')->cascadeOnDelete();
            $table->enum('tipo', [
                'diplomado', 'programa', 'especializacion', 'curso', 'taller', 'seminario', 'conferencias', 'otros',
            ]);
            $table->string('nombre_evento');
            $table->string('centro_estudios');
            $table->unsignedInteger('horas')->nullable();
            $table->string('folio')->nullable();
            $table->date('fecha')->nullable();
            // se guarda el path dentro del disco "public" (storage/app/public/...)
            $table->string('archivo_path')->nullable();
            $table->string('archivo_nombre_original')->nullable();
            // permite "anular" un registro (ocultarlo) sin borrarlo ni perder el archivo adjunto
            $table->boolean('flag_activo')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('capacitaciones');
    }
};
