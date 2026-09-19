<?php

namespace App\Http\Controllers;

use App\Models\Universidad;
use Illuminate\Http\Request;

class UniversidadController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        return $this->generateViewSetList(
            $request,
            Universidad::query(),
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
        $universidad = Universidad::create($request['universidad']);
        return response()->json($universidad, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Universidad $universidade)
    {
        return response()->json($universidade);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Universidad $universidade)
    {
        $universidade->update($request['universidad']);
        return response()->json($universidade);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Universidad $universidade)
    {
         $universidade->delete();
        return response()->json(null, 204);
    }
}
