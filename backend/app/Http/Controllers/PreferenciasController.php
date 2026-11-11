<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

// Tema del panel del usuario logueado. Actualización parcial: cambiar el modo
// no toca el acento ni el neutro.
class PreferenciasController extends Controller
{
    public function update(Request $request)
    {
        $datos = $request->validate([
            'acento' => ['sometimes', 'string', Rule::in(config('panel.temas.acentos'))],
            'neutro' => ['sometimes', 'string', Rule::in(config('panel.temas.neutros'))],
            'modo' => ['sometimes', 'string', Rule::in(config('panel.temas.modos'))],
        ]);

        $user = $request->user();
        // forceFill: preferencias NO es fillable; esta es la única puerta y está validada
        $user->forceFill(['preferencias' => [...$user->preferenciasPanel(), ...$datos]])->save();

        return response()->json($user->preferenciasPanel());
    }
}
