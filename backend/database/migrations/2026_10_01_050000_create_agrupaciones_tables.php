<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Agrupaciones concretas ("Sikuris Juventud Obrera"), distintas de la comisión,
// que es la categoría ("Conjunto de sikuris"). La crea un artista registrado,
// que queda como representante y carga a los integrantes: la mayoría son
// personas comunes, sin cuenta ni perfil en el registro.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agrupaciones', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 150);
            $table->string('slug')->unique();
            // una sola comisión: la categoría donde se la ubica en el portal
            $table->char('codigo_comision', 4)->nullable();
            // HTML sanitizado (App\Support\Html::limpio)
            $table->text('descripcion')->nullable();
            $table->json('redes_sociales')->nullable();
            $table->enum('estado', ['borrador', 'pendiente', 'aprobado', 'observado'])->default('borrador');
            $table->text('observacion')->nullable();
            $table->foreignId('revisado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('revisado_en')->nullable();
            $table->timestamps();
        });

        // integrantes: registro simple dentro de la agrupación (no son artistas
        // del registro). El DNI nunca sale al portal: solo panel y admin.
        Schema::create('agrupacion_integrantes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agrupacion_id')->constrained('agrupaciones')->cascadeOnDelete();
            $table->char('dni', 8);
            $table->string('nombre');
            $table->string('apellido_paterno');
            $table->string('apellido_materno')->nullable();
            // texto libre corto: director, músico, danzante, vocalista...
            $table->string('rol', 60)->nullable();
            // si el DNI es de un artista registrado se vincula: en su perfil sale "Integra: ..."
            $table->foreignId('persona_id')->nullable()->constrained('personas')->nullOnDelete();
            $table->boolean('es_representante')->default(false);
            $table->timestamps();

            $table->unique(['agrupacion_id', 'dni']);
            $table->index('dni');
        });

        // historial de revisión de la agrupación (mismo formato que revisiones_perfil)
        Schema::create('revisiones_agrupacion', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agrupacion_id')->constrained('agrupaciones')->cascadeOnDelete();
            $table->enum('accion', ['enviado', 'aprobado', 'observado']);
            $table->text('observacion')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('revisiones_agrupacion');
        Schema::dropIfExists('agrupacion_integrantes');
        Schema::dropIfExists('agrupaciones');
    }
};
