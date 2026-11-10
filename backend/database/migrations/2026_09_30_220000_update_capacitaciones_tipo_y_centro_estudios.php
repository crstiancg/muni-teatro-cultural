<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Una migración ya corrida no se edita: en las bases existentes no se
// vuelve a ejecutar. Los cambios de esquema van siempre en una nueva.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('capacitaciones', function (Blueprint $table) {
            $table->enum('tipo', [
                'diplomado', 'programa', 'especializacion', 'capacitacion', 'reconocimiento',
                'curso', 'taller', 'seminario', 'conferencias', 'otros',
            ])->change();
            $table->string('centro_estudios')->nullable()->change();
        });
    }

    public function down(): void
    {
        // falla si ya hay filas con los tipos nuevos o sin centro_estudios:
        // hay que corregirlas antes de revertir
        Schema::table('capacitaciones', function (Blueprint $table) {
            $table->enum('tipo', [
                'diplomado', 'programa', 'especializacion', 'curso', 'taller', 'seminario', 'conferencias', 'otros',
            ])->change();
            $table->string('centro_estudios')->nullable(false)->change();
        });
    }
};
