<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

// campanita del panel: avisos de database notifications del usuario logueado
class NotificacionController extends Controller
{
    public function index(Request $request)
    {
        $usuario = $request->user();

        return response()->json([
            'no_leidas' => $usuario->unreadNotifications()->count(),
            'data' => $usuario->notifications()->limit(20)->get(['id', 'data', 'read_at', 'created_at']),
        ]);
    }

    public function leer(Request $request, string $id)
    {
        $request->user()->notifications()->findOrFail($id)->markAsRead();

        return response()->json(true);
    }

    public function leerTodas(Request $request)
    {
        $request->user()->unreadNotifications->markAsRead();

        return response()->json(true);
    }
}
