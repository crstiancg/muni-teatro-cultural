<?php

namespace App\Providers;

use App\Models\Actividad;
use App\Models\Agrupacion;
use App\Models\AgrupacionActividad;
use App\Models\Capacitacion;
use App\Models\FormacionAcademica;
use App\Models\Persona;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;
use Laravel\Passport\Passport;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Passport::enablePasswordGrant();

        // alias cortos en archivos.archivable_type en vez del FQCN: si se mueve
        // o renombra un modelo, los registros no quedan huérfanos.
        // morphMap y no enforceMorphMap: spatie/permission ya guarda
        // App\Models\User en model_has_roles y enforce rompería esos datos.
        Relation::morphMap([
            'persona' => Persona::class,
            'capacitacion' => Capacitacion::class,
            'formacion_academica' => FormacionAcademica::class,
            'actividad' => Actividad::class,
            'agrupacion' => Agrupacion::class,
            'agrupacion_actividad' => AgrupacionActividad::class,
        ]);
    }
}
