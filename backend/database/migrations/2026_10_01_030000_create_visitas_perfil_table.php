<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Una fila por persona por día (no una por visita): la tabla crece poco aunque
// el perfil tenga mucho tráfico.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visitas_perfil', function (Blueprint $table) {
            $table->id();
            $table->foreignId('persona_id')->constrained('personas')->cascadeOnDelete();
            $table->date('fecha');
            $table->unsignedInteger('visitas')->default(0);
            $table->timestamps();

            $table->unique(['persona_id', 'fecha']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visitas_perfil');
    }
};
