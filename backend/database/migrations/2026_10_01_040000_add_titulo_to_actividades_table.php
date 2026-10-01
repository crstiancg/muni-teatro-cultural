<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('actividades', function (Blueprint $table) {
            // nullable solo por las actividades que ya existen: el form lo exige
            $table->string('titulo', 150)->nullable()->after('persona_id');
            // la descripción pasa a ser opcional (HTML del editor); el título identifica el evento
            $table->text('descripcion')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('actividades', function (Blueprint $table) {
            $table->dropColumn('titulo');
            $table->text('descripcion')->nullable(false)->change();
        });
    }
};
