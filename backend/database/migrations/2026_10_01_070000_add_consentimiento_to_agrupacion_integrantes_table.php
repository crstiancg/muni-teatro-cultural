<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Ley 29733: los integrantes son personas comunes que no aceptaron nada en el
// sistema. El representante declara contar con su consentimiento al cargarlas;
// acá queda cuándo, como evidencia ante un reclamo.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('agrupacion_integrantes', function (Blueprint $table) {
            $table->timestamp('consentimiento_en')->nullable()->after('es_representante');
        });
    }

    public function down(): void
    {
        Schema::table('agrupacion_integrantes', function (Blueprint $table) {
            $table->dropColumn('consentimiento_en');
        });
    }
};
