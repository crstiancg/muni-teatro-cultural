<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('archivos', function (Blueprint $table) {
            // versión chica para listados del portal (solo imágenes)
            $table->string('path_miniatura')->nullable()->after('path');
        });
    }

    public function down(): void
    {
        Schema::table('archivos', function (Blueprint $table) {
            $table->dropColumn('path_miniatura');
        });
    }
};
