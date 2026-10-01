<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

// Deploy en un solo dominio: el build de Quasar va dentro de Laravel.
//   - assets (js, css, íconos) -> public/   (los sirve Apache directo)
//   - index.html               -> resources/spa/index.html (plantilla de SpaController)
// El index.html NO va a public/: Apache lo serviría tal cual y se perderían las
// meta tags de cada página.
class PublicarSpa extends Command
{
    protected $signature = 'spa:publicar {origen? : carpeta del build (por defecto ../fronted/dist/spa)}';

    protected $description = 'Copia el build de Quasar dentro de Laravel para servirlo con SEO';

    public function handle(): int
    {
        $origen = $this->argument('origen') ?? base_path('../fronted/dist/spa');

        if (! is_file("{$origen}/index.html")) {
            $this->error("No hay build en {$origen}. Corre primero \"quasar build\" en el front.");

            return self::FAILURE;
        }

        File::ensureDirectoryExists(resource_path('spa'));
        File::copy("{$origen}/index.html", resource_path('spa/index.html'));

        foreach (File::allFiles($origen) as $archivo) {
            $relativa = $archivo->getRelativePathname();
            if ($relativa === 'index.html') {
                continue;
            }
            File::ensureDirectoryExists(dirname(public_path($relativa)));
            File::copy($archivo->getPathname(), public_path($relativa));
        }

        $this->info('Front publicado: assets en public/, plantilla en resources/spa/index.html.');

        return self::SUCCESS;
    }
}
