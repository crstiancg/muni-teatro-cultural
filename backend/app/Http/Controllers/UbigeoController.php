<?php

namespace App\Http\Controllers;

use App\Models\Ubigeo;
use Illuminate\Http\Request;

class UbigeoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        return $this->generateViewSetList(
            $request,
            Ubigeo::query(),
            ['tipo', 'cod_dep', 'cod_prov'],
            ['codigo', 'nombre'],
            ['id', 'codigo', 'nombre']
        );
    }

    /**
     * Display the specified resource by its código (no es la PK).
     */
    public function show(string $codigo)
    {
        return response()->json(Ubigeo::where('codigo', $codigo)->firstOrFail());
    }
}
