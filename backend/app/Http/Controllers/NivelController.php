<?php

namespace App\Http\Controllers;

use App\Models\Nivel;
use Illuminate\Http\Request;

class NivelController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        return $this->generateViewSetList(
            $request,
            Nivel::query(),
            [],
            ['nombre'],
            ['id']
        );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $nivel = Nivel::create($request->all());
        return response()->json($nivel, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Nivel $nivele)
    {
        return response()->json($nivele);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Nivel $nivel)
    {
        $nivel->update($request->all());
        return response()->json($nivel);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Nivel $nivel)
    {
        $nivel->delete();
        return response()->json(null, 204);
    }
}
