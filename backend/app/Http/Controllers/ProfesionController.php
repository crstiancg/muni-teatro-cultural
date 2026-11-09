<?php

namespace App\Http\Controllers;

use Illuminate\Routing\Controllers\HasMiddleware;
use App\Models\Profesion;
use Illuminate\Http\Request;

class ProfesionController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return static::permisos('profesiones');
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        return $this->generateViewSetList(
            $request,
            Profesion::query(),
            [],
            ['dni', 'nombre'],
            ['id']
        );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $profesion = Profesion::create($request['profesion']);
        return response()->json($profesion, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Profesion $profesione)
    {
        return response()->json($profesione);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Profesion $profesione)
    {
        $profesione->update($request['profesion']);
        return response()->json($profesione);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Profesion $profesione)
    {
        $profesione->delete();
        return response()->json(null, 204);
    }
}
