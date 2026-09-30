<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('personas', function (Blueprint $table) {
            // HTML ya sanitizado (HTMLPurifier) en el backend antes de guardar
            $table->text('biografia')->nullable()->after('correo');
            // { "facebook": "https://...", "instagram": "https://..." }
            $table->json('redes_sociales')->nullable()->after('biografia');
            // borrador -> pendiente -> aprobado | observado -> pendiente ...
            // solo "aprobado" aparece en el portal público
            $table->enum('estado', ['borrador', 'pendiente', 'aprobado', 'observado'])
                ->default('borrador')->after('redes_sociales');
            // feedback del admin al observar la solicitud
            $table->text('observacion')->nullable()->after('estado');
            $table->foreignId('revisado_por')->nullable()->after('observacion')
                ->constrained('users')->nullOnDelete();
            $table->timestamp('revisado_en')->nullable()->after('revisado_por');
        });

        // hasta hoy "publicado" era tener comisión: sin esto el portal queda vacío
        DB::table('personas')->whereNotNull('codigo_comision')->update(['estado' => 'aprobado']);
    }

    public function down(): void
    {
        Schema::table('personas', function (Blueprint $table) {
            $table->dropConstrainedForeignId('revisado_por');
            $table->dropColumn(['biografia', 'redes_sociales', 'estado', 'observacion', 'revisado_en']);
        });
    }
};
