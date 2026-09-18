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
        Schema::create('formacion_academicas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('persona_id')->constrained('personas')->cascadeOnDelete();
            // categoría amplia del nivel educativo
            $table->enum('tipo', [
                'primaria', 'secundaria', 'tecnica_basica', 'tecnica_superior', 'universitaria', 'maestria', 'doctorado',
            ]);
            // grado/estado alcanzado dentro de ese tipo (egresado, bachiller, titulado, etc.)
            $table->enum('nivel_alcanzado', ['egresado', 'tecnico', 'bachiller', 'titulado', 'maestria', 'doctorado'])->nullable();
            $table->string('centro_estudios');
            $table->string('profesion')->nullable();
            $table->string('folio')->nullable();
            $table->date('fecha_expedicion')->nullable();
            // se guarda el path dentro del disco "public" (storage/app/public/...)
            $table->string('archivo_path')->nullable();
            $table->string('archivo_nombre_original')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('formacion_academicas');
    }
};
