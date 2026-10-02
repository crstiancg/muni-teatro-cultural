<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;
use ZipArchive;

// Arma un .zip listo para subir a cPanel: backend + front compilado + vendor de
// producción. En el servidor no se instala ni se compila nada: se descomprime,
// se completa el .env y se corren las migraciones (ver DEPLOY.md).
class EmpaquetarDeploy extends Command
{
    protected $signature = 'deploy:empaquetar
        {--con-datos : incluye el backup actual (base de datos y archivos subidos)}
        {--forzar : empaqueta aunque el build del front apunte a 127.0.0.1}';

    protected $description = 'Genera el .zip de producción para cPanel';

    // nunca entran al paquete (prefijos de ruta relativos a backend/)
    private const EXCLUIR = [
        '.git/', 'node_modules/', 'vendor/', 'tests/', '.phpunit.cache/',
        'storage/app/', 'storage/logs/', 'storage/framework/cache/', 'storage/framework/sessions/',
        'storage/framework/views/', 'storage/framework/testing/',
        // config cacheada en esta máquina: rutas de Windows -> error 500 en el servidor
        'bootstrap/cache/',
        // enlace a storage/app/public: en el servidor se crea con storage:link
        // (sin barra final: el enlace en sí también es una entrada)
        'public/storage', 'public/hot',
    ];

    // carpetas vacías que Laravel necesita para escribir
    private const CARPETAS = [
        'storage/app/public', 'storage/app/private', 'storage/app/purifier',
        'storage/framework/cache/data', 'storage/framework/sessions', 'storage/framework/views',
        'storage/logs', 'bootstrap/cache',
    ];

    public function handle(): int
    {
        if (! $this->frontListo()) {
            return self::FAILURE;
        }

        $tmp = storage_path('app/deploy-tmp/racc');
        File::deleteDirectory(dirname($tmp));
        File::ensureDirectoryExists($tmp);

        $this->info('1/4 Copiando el proyecto (sin .env, sin caché, sin archivos de prueba)...');
        $this->copiarProyecto($tmp);

        $this->info('2/4 Instalando dependencias de producción (composer --no-dev)...');
        $composer = Process::path($tmp)->timeout(1200)
            ->run('composer install --no-dev --optimize-autoloader --no-interaction --no-progress');
        if ($composer->failed()) {
            $this->error($composer->errorOutput() ?: $composer->output());

            return self::FAILURE;
        }
        // package:discover regenera estos dos: son portables (sin rutas absolutas)
        foreach (File::glob("{$tmp}/bootstrap/cache/*.php") as $cache) {
            if (! Str::endsWith($cache, ['packages.php', 'services.php'])) {
                File::delete($cache);
            }
        }

        File::put("{$tmp}/.env.ejemplo-produccion", $this->envEjemplo());

        if ($this->option('con-datos')) {
            $this->info('3/4 Agregando el backup actual (base de datos y archivos)...');
            Artisan::call('backup:diario');
            $ultimo = collect(File::glob(storage_path('app/backups/backup-*.zip')))->sort()->last();
            File::ensureDirectoryExists("{$tmp}/datos-iniciales");
            File::copy($ultimo, "{$tmp}/datos-iniciales/" . basename($ultimo));
        } else {
            $this->info('3/4 Sin datos: producción arranca con la base vacía (usa --con-datos para llevarlos).');
        }

        $this->info('4/4 Comprimiendo...');
        File::ensureDirectoryExists(storage_path('app/deploy'));
        $zipRuta = storage_path('app/deploy/racc-deploy-' . now()->format('Y-m-d_His') . '.zip');
        $this->comprimir($tmp, $zipRuta);
        File::deleteDirectory(dirname($tmp));

        $this->newLine();
        $this->info('Paquete listo: ' . $zipRuta . ' (' . round(filesize($zipRuta) / 1048576, 1) . ' MB)');
        $this->line('Siguiente paso: DEPLOY.md, sección "Subir el paquete".');
        $this->warn('El servidor debe tener la misma versión de PHP (o mayor) que esta máquina: ' . PHP_VERSION);

        return self::SUCCESS;
    }

    private function frontListo(): bool
    {
        if (! is_file(resource_path('spa/index.html'))) {
            $this->error('Falta el front: corre "quasar build" y "php artisan spa:publicar".');

            return false;
        }

        // el error clásico: un build hecho con la API local apuntando a 127.0.0.1
        $apuntaLocal = collect(File::glob(public_path('assets/*.js')))
            ->contains(fn ($js) => Str::contains(File::get($js), ['127.0.0.1:8000', 'localhost:8000']));
        if ($apuntaLocal && ! $this->option('forzar')) {
            $this->error('El build del front apunta a 127.0.0.1:8000: en producción no funcionaría.');
            $this->line('Deja QCLI_API_BACKEND_URL vacío en fronted/.env, corre "quasar build" y "php artisan spa:publicar".');

            return false;
        }

        return true;
    }

    private function copiarProyecto(string $destino): void
    {
        foreach (File::allFiles(base_path(), true) as $archivo) {
            $relativa = str_replace('\\', '/', $archivo->getRelativePathname());

            $excluido = Str::startsWith($relativa, self::EXCLUIR)
                // .env reales (con contraseñas) y claves de Passport: se generan en el servidor
                || (Str::startsWith(basename($relativa), '.env') && basename($relativa) !== '.env.example')
                || preg_match('#^storage/oauth-.*\.key$#', $relativa)
                || $relativa === '.phpunit.result.cache';

            if ($excluido) {
                continue;
            }

            File::ensureDirectoryExists(dirname("{$destino}/{$relativa}"));
            File::copy($archivo->getPathname(), "{$destino}/{$relativa}");
        }

        foreach (self::CARPETAS as $carpeta) {
            File::ensureDirectoryExists("{$destino}/{$carpeta}");
            // conserva los .gitignore del proyecto: dejan la carpeta "vacía pero presente"
            if (is_file(base_path("{$carpeta}/.gitignore"))) {
                File::copy(base_path("{$carpeta}/.gitignore"), "{$destino}/{$carpeta}/.gitignore");
            }
        }
    }

    private function comprimir(string $origen, string $zipRuta): void
    {
        $zip = new ZipArchive();
        $zip->open($zipRuta, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        foreach (File::allFiles($origen, true) as $archivo) {
            $zip->addFile($archivo->getPathname(), 'racc/' . str_replace('\\', '/', $archivo->getRelativePathname()));
        }
        foreach (self::CARPETAS as $carpeta) {
            $zip->addEmptyDir("racc/{$carpeta}");
        }

        $zip->close();
    }

    private function envEjemplo(): string
    {
        return <<<'ENV'
        # Copiar a ".env" y completar. NUNCA subir este archivo a git.
        APP_NAME="Registro Cultural de Puno"
        APP_ENV=production
        APP_KEY=
        APP_DEBUG=false
        APP_URL=https://tudominio
        FRONTEND_URL=https://tudominio

        LOG_CHANNEL=daily
        LOG_LEVEL=error

        DB_CONNECTION=mysql
        DB_HOST=localhost
        DB_PORT=3306
        DB_DATABASE=
        DB_USERNAME=
        DB_PASSWORD=

        SESSION_DRIVER=file
        CACHE_STORE=file
        QUEUE_CONNECTION=sync

        PASSPORT_PASSWORD_CLIENT_ID=
        PASSPORT_PASSWORD_CLIENT_SECRET=

        APIS_NET_PE_TOKEN=

        AGRUPACIONES_MAXIMO=2
        USUARIOS_OCULTOS=1

        # solo si el hosting no permite symlinks (storage:link)
        # PUBLIC_DISK_EN_PUBLIC=true
        ENV;
    }
}
