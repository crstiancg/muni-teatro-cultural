<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Mismo esqueleto que "ubigeos": una sola tabla con dos niveles
        // jerárquicos (grupo/familia) distinguidos por "tipo". El código final
        // de una familia es cod_grupo+cod_familia concatenado, y esa fila
        // familia ES la comisión (su nombre es el nombre de la comisión).
        Schema::create('comisions', function (Blueprint $table) {
            $table->id();
            $table->char('codigo', 4)->unique();
            $table->enum('tipo', ['grupo', 'familia']);
            $table->char('cod_grupo', 2);
            $table->char('cod_familia', 2);
            $table->string('nombre');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('comisions');
    }
};
