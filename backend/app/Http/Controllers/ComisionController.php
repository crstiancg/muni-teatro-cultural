<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreComisionRequest;
use App\Http\Requests\StoreFamiliaRequest;
use App\Models\Comision;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ComisionController extends Controller
{
    public function index(Request $request)
    {
        return $this->generateViewSetList(
            $request,
            Comision::query(),
            ['tipo', 'cod_grupo'],
            ['codigo', 'nombre'],
            ['id', 'codigo', 'nombre']
        );
    }

    public function show(string $codigo)
    {
        return response()->json(Comision::where('codigo', $codigo)->firstOrFail());
    }

    public function storeGrupo(StoreComisionRequest $request)
    {
        $grupo = DB::transaction(function () use ($request) {
            // bloqueamos las filas de grupo existentes para que dos creaciones
            // simultáneas no calculen el mismo "siguiente" correlativo
            $max = Comision::where('tipo', 'grupo')->lockForUpdate()->max('cod_grupo') ?? '00';
            $codGrupo = str_pad((int) $max + 1, 2, '0', STR_PAD_LEFT);

            return Comision::create([
                'codigo' => $codGrupo . '00',
                'tipo' => 'grupo',
                'cod_grupo' => $codGrupo,
                'cod_familia' => '00',
                'nombre' => data_get($request, 'comision.nombre'),
            ]);
        });

        return response()->json($grupo, 201);
    }

    public function storeFamilia(StoreFamiliaRequest $request)
    {
        $codGrupo = data_get($request, 'comision.cod_grupo');

        $familia = DB::transaction(function () use ($request, $codGrupo) {
            $max = Comision::where('tipo', 'familia')
                ->where('cod_grupo', $codGrupo)
                ->lockForUpdate()
                ->max('cod_familia') ?? '00';
            $codFamilia = str_pad((int) $max + 1, 2, '0', STR_PAD_LEFT);

            return Comision::create([
                'codigo' => $codGrupo . $codFamilia,
                'tipo' => 'familia',
                'cod_grupo' => $codGrupo,
                'cod_familia' => $codFamilia,
                'nombre' => data_get($request, 'comision.nombre'),
            ]);
        });

        return response()->json($familia, 201);
    }

    public function update(StoreComisionRequest $request, Comision $comision)
    {
        // renombrar no cambia el código, así que no hay correlativo que tocar
        $comision->update(['nombre' => data_get($request, 'comision.nombre')]);
        return response()->json($comision);
    }

    public function destroy(Comision $comision)
    {
        if ($comision->tipo === 'grupo' && Comision::where('tipo', 'familia')->where('cod_grupo', $comision->cod_grupo)->exists()) {
            return response()->json(['message' => 'No se puede eliminar un grupo con familias asignadas.'], 422);
        }

        return response()->json($comision->delete());
    }
}
