<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Baja de usuarios: la cuenta deja de poder entrar (sus sesiones se cierran en
// todos los dispositivos) pero no se borra: queda su historial (revisiones,
// aprobaciones) y se puede reactivar. Se guarda cuándo, por qué y quién.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('baja_en')->nullable()->after('password');
            $table->string('baja_motivo', 500)->nullable()->after('baja_en');
            $table->foreignId('baja_por')->nullable()->after('baja_motivo')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('baja_por');
            $table->dropColumn(['baja_en', 'baja_motivo']);
        });
    }
};
