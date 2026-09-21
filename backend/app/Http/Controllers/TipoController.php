<?php

namespace App\Http\Controllers;

use App\Models\Tipo;
use Illuminate\Http\Request;

class TipoController extends Controller
{

    public function index(Request $request)
    {
        return $this->generateViewSetList(
            $request,
            Tipo::query(),
            [],
            ['nombre'],
            ['id']
        );
    }


    public function store(Request $request)
    {
        $tipo = Tipo::create($request->all());
        return response()->json($tipo, 201);
    }


    public function show(Tipo $tipo)
    {
        return response()->json($tipo);
    }

    public function update(Request $request, Tipo $tipo)
    {
        $tipo->update($request->all());
        return response()->json($tipo);
    }

    public function destroy(Tipo $tipo)
    {
        $tipo->delete();
        return response()->json(null, 204);
    }
}