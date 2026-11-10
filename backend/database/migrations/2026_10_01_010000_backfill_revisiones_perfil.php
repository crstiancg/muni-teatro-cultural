<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// Las revisiones hechas antes de existir revisiones_perfil solo quedaron en
// personas (estado + observacion + revisado_por + revisado_en): se pasan al
// historial para que no falte la última observación o aprobación.
return new class extends Migration
{
    public function up(): void
    {
        DB::table('personas')
            ->whereIn('estado', ['aprobado', 'observado'])
            ->whereNotNull('revisado_en')
            ->whereNotExists(fn ($q) => $q->from('revisiones_perfil')
                ->whereColumn('revisiones_perfil.persona_id', 'personas.id'))
            ->orderBy('id')
            ->each(function ($persona) {
                DB::table('revisiones_perfil')->insert([
                    'persona_id' => $persona->id,
                    'accion' => $persona->estado,
                    'observacion' => $persona->estado === 'observado' ? $persona->observacion : null,
                    'user_id' => $persona->revisado_por,
                    'created_at' => $persona->revisado_en,
                    'updated_at' => $persona->revisado_en,
                ]);
            });
    }

    public function down(): void
    {
        // no se distinguen las filas migradas de las reales: no se revierte
    }
};
