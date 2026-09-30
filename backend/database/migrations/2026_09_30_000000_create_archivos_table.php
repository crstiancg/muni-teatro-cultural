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
        Schema::create('archivos', function (Blueprint $table) {
            $table->id();
            // archivable_type + archivable_id: dueño del archivo (persona, capacitacion,
            // formacion_academica, actividad). Crea el índice compuesto solo.
            $table->morphs('archivable');
            // distingue para qué sirve el archivo dentro del mismo dueño
            // (ej: persona -> "foto"; capacitacion -> "certificado")
            $table->string('coleccion')->default('default');
            $table->string('disco')->default('public');
            // path dentro del disco (storage/app/public/...) o URL externa (seeders)
            $table->string('path');
            $table->string('nombre_original')->nullable();
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('tamano')->nullable();
            $table->timestamps();

            $table->index(['archivable_type', 'archivable_id', 'coleccion']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('archivos');
    }
};
