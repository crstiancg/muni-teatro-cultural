<?php

namespace App\Http\Controllers;

use App\Models\Profesion;
use Illuminate\Http\Request;

class ProfesionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $profesiones = Profesion::paginate(10);
        return response()->json($profesiones);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $profesion = Profesion::create($request->all());
        return response()->json($profesion, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Profesion $profesion)
    {
        return response()->json($profesione);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Profesion $profesion)
    {
        $profesione->update($request->all());
        return response()->json($profesione);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Profesion $profesion)
    {
        $profesione->delete();
        return response()->json(null, 204);
    }
}
