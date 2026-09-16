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
        Schema::create('personas', function (Blueprint $table) {
            $table->id();
            $table->char('dni', 8)->unique();
            $table->string('nombre');
            $table->string('apellido_paterno');
            $table->string('apellido_materno');
            $table->string('nombre_completo');
            $table->enum('genero', ['masculino', 'femenino', 'sin especificar'])->nullable();
            $table->date('fecha_nacimiento')->nullable();
            $table->string('direccion')->nullable();
            $table->enum('estado_civil', ['soltero', 'casado', 'divorciado', 'viudo'])->nullable();
            $table->char('celular', 9)->nullable();
            $table->char('celular_emergencia', 9)->nullable();
            $table->string('correo')->nullable();
            $table->char('ubigeo_cod_nacimiento', 6)->nullable();
            $table->char('ubigeo_cod_residencia', 6)->nullable();
            $table->foreignId('user_id')->constrained();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('personas');
    }
};
