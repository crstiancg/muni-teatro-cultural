<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('personas', function (Blueprint $table) {
            // Dato sensible (Ley 29733): lo ven solo la persona y los admins, nunca
            // el portal público. null = todavía no respondió (distinto de "no tiene").
            $table->boolean('flag_discapacidad')->nullable()->after('estado_civil');
            $table->string('tipo_discapacidad', 20)->nullable()->after('flag_discapacidad');
        });
    }

    public function down(): void
    {
        Schema::table('personas', function (Blueprint $table) {
            $table->dropColumn(['flag_discapacidad', 'tipo_discapacidad']);
        });
    }
};
