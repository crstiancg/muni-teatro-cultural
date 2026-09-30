<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Historial del flujo de revisión del perfil público: personas.observacion
// solo guarda la última, acá queda cada envío, aprobación y observación.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('revisiones_perfil', function (Blueprint $table) {
            $table->id();
            $table->foreignId('persona_id')->constrained('personas')->cascadeOnDelete();
            $table->enum('accion', ['enviado', 'aprobado', 'observado']);
            $table->text('observacion')->nullable();
            // quién hizo la acción: el artista al enviar, el admin al resolver
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('revisiones_perfil');
    }
};
