<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Agenda cultural: UNA tabla para los tres organizadores (municipalidad,
        // persona, agrupación) para que el calendario público sea una sola consulta.
        // FKs explícitas y no polimórficas: el conjunto de organizadores es cerrado.
        Schema::create('eventos', function (Blueprint $table) {
            $table->id();
            $table->string('titulo', 150);
            $table->text('descripcion')->nullable();

            // "hora de pared" de la ciudad, sin zona: lo que se carga es lo que se muestra
            $table->dateTime('fecha_inicio');
            $table->dateTime('fecha_fin')->nullable();

            $table->enum('modalidad', ['presencial', 'virtual'])->default('presencial');
            $table->string('lugar', 150)->nullable();
            $table->string('direccion')->nullable();
            $table->string('enlace')->nullable();

            // disciplina: apunta a comisions.codigo (tipo='familia'), como personas
            $table->char('codigo_comision', 4)->nullable();

            $table->boolean('flag_gratuito')->default(true);
            $table->decimal('costo', 8, 2)->nullable();

            $table->enum('organizador_tipo', ['municipalidad', 'persona', 'agrupacion'])->default('municipalidad');
            $table->foreignId('persona_id')->nullable()->constrained('personas')->cascadeOnDelete();
            $table->foreignId('agrupacion_id')->nullable()->constrained('agrupaciones')->cascadeOnDelete();

            // pendiente/observado se usan en la fase 2 (eventos propuestos por artistas)
            $table->enum('estado', ['pendiente', 'aprobado', 'observado', 'cancelado'])->default('pendiente');
            $table->text('observacion')->nullable();
            $table->foreignId('revisado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('revisado_en')->nullable();

            $table->boolean('flag_destacado')->default(false);
            $table->foreignId('creado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            // la agenda pública siempre filtra por estado y rango de fechas
            $table->index(['estado', 'fecha_inicio']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('eventos');
    }
};
