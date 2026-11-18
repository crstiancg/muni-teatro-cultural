<?php

use App\Http\Controllers\CompartirPerfilController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\SpaController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// link para compartir perfiles con vista previa (Open Graph) en WhatsApp/Facebook
Route::get('/compartir/{persona:slug}', CompartirPerfilController::class)->name('compartir.perfil');

Route::get('/sitemap.xml', [SitemapController::class, 'sitemap']);
Route::get('/robots.txt', [SitemapController::class, 'robots']);
Route::get('/llms.txt', [SitemapController::class, 'llms']);

// El directorio pasó de /consejeros a /artistas. Los links viejos ya circulan
// (perfiles compartidos, Google, fichas impresas): 301 conservando el resto de
// la ruta y la búsqueda. El 301 le pasa a Google el posicionamiento de la vieja.
Route::get('/consejeros/{resto?}', function (Request $request, ?string $resto = null) {
    $query = $request->getQueryString();

    return redirect('/artistas' . ($resto ? "/{$resto}" : '') . ($query ? "?{$query}" : ''), 301);
})->where('resto', '.*');

// todo lo demás es la SPA, con el <head> de cada página escrito por SpaController
Route::fallback(SpaController::class);
