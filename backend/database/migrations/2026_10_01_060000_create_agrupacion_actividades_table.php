<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Actividades realizadas por la agrupación como tal (presentaciones, concursos).
// Misma forma que "actividades" de los artistas: el front reutiliza su CRUD.
// La imagen va a la tabla polimórfica "archivos" (TieneAdjunto).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agrupacion_actividades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agrupacion_id')->constrained('agrupaciones')->cascadeOnDelete();
            $table->string('titulo', 150);
            // HTML sanitizado (App\Support\Html::limpio)
            $table->text('descripcion')->nullable();
            $table->boolean('flag_activo')->default(true);
            $table->boolean('flag_publico')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agrupacion_actividades');
    }
};
