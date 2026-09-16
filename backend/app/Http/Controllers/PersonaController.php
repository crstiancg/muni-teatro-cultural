<?php

namespace App\Http\Controllers;

use App\Models\Persona;
use Illuminate\Http\Request;

class PersonaController extends Controller
{

    public function index()
    {
        $personas = Persona::paginate(1);
        return response()->json($personas);
    }


    public function store(Request $request)
    {
        $persona = Persona::create($request->all());
        return response()->json($persona, 201);
    }


    public function show(Persona $persona)
    {
        return response()->json($persona);
    }

    public function update(Request $request, Persona $persona)
    {
        $persona->update($request->all());
        return response()->json($persona);
    }

    public function destroy(Persona $persona)
    {
        $persona->delete();
        return response()->json(null, 204);
    }
}
