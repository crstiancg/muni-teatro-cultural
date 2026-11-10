<?php

use App\Http\Controllers\CompartirPerfilController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\SpaController;
use Illuminate\Support\Facades\Route;

// link para compartir perfiles con vista previa (Open Graph) en WhatsApp/Facebook
Route::get('/compartir/{persona:slug}', CompartirPerfilController::class)->name('compartir.perfil');

Route::get('/sitemap.xml', [SitemapController::class, 'sitemap']);
Route::get('/robots.txt', [SitemapController::class, 'robots']);

// todo lo demás es la SPA, con el <head> de cada página escrito por SpaController
Route::fallback(SpaController::class);
