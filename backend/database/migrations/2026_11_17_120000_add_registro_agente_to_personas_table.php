<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

// Registro de Agentes Culturales: al aprobarse el perfil por primera vez la persona
// recibe un número correlativo (se muestra como CPC0001) que no cambia nunca, y un
// token aleatorio para el QR de verificación pública (el número es enumerable, el
// token no: así nadie recorre el padrón probando CPC0001, CPC0002...).
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('personas', function (Blueprint $table) {
            $table->unsignedInteger('numero_registro')->nullable()->unique()->after('estado');
            $table->string('qr_token', 16)->nullable()->unique()->after('numero_registro');
            // fecha de inscripción: la primera aprobación, no la última
            $table->timestamp('registrado_en')->nullable()->after('qr_token');
        });

        $this->numerarAprobadosExistentes();
    }

    public function down(): void
    {
        Schema::table('personas', function (Blueprint $table) {
            $table->dropUnique(['numero_registro']);
            $table->dropUnique(['qr_token']);
            $table->dropColumn(['numero_registro', 'qr_token', 'registrado_en']);
        });
    }

    // Los que ya fueron aprobados alguna vez entran al registro en el orden en que
    // se aprobaron: el primero aprobado es CPC0001. Cuenta también a los observados
    // después de aprobados (estuvieron inscritos; su ficha dirá "No vigente").
    private function numerarAprobadosExistentes(): void
    {
        $primeraAprobacion = DB::table('revisiones_perfil')
            ->where('accion', 'aprobado')
            ->groupBy('persona_id')
            ->select('persona_id', DB::raw('MIN(created_at) as aprobado_en'));

        $personas = DB::table('personas')
            ->leftJoinSub($primeraAprobacion, 'pa', 'pa.persona_id', '=', 'personas.id')
            ->where(fn ($q) => $q->whereNotNull('pa.aprobado_en')->orWhere('personas.estado', 'aprobado'))
            ->select('personas.id', 'pa.aprobado_en', 'personas.revisado_en', 'personas.created_at')
            ->get()
            ->map(fn ($p) => [
                'id' => $p->id,
                'registrado_en' => $p->aprobado_en ?? $p->revisado_en ?? $p->created_at,
            ])
            ->sortBy([['registrado_en', 'asc'], ['id', 'asc']])
            ->values();

        $tokens = [];
        foreach ($personas as $indice => $persona) {
            do {
                $token = Str::random(10);
            } while (isset($tokens[$token]));
            $tokens[$token] = true;

            DB::table('personas')->where('id', $persona['id'])->update([
                'numero_registro' => $indice + 1,
                'qr_token' => $token,
                'registrado_en' => $persona['registrado_en'],
            ]);
        }
    }
};
