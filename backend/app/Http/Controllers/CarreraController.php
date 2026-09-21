<?php

namespace App\Http\Controllers;

use App\Models\Carrera;
use Illuminate\Http\Request;

class CarreraController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        return $this->generateViewSetList(
            $request,
            Carrera::query(),
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
        $carrera = Carrera::create($request['carrera']);
        return response()->json($carrera, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Carrera $carrera)
    {
        return response()->json($carrera);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Carrera $carrera)
    {
        $carrera->update($request['carrera']);
        return response()->json($carrera);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Carrera $carrera)
    {
        $carrera->delete();
        return response()->json(null, 204);
    }
}
