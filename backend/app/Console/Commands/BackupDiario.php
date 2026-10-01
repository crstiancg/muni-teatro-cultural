<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use ZipArchive;

// Backup de la base y de los archivos subidos (fotos, certificados) en un .zip.
// Sin mysqldump: en hosting compartido exec() suele estar deshabilitado, así que
// la base se exporta con PHP. Se conservan los últimos N backups.
//
// En cPanel: un solo cron cada minuto que corra el scheduler de Laravel
//   * * * * * php /home/USUARIO/backend/artisan schedule:run >> /dev/null 2>&1
// y este comando queda agendado a diario en routes/console.php.
class BackupDiario extends Command
{
    protected $signature = 'backup:diario {--conservar=7 : cuántos backups guardar}';

    protected $description = 'Respalda la base de datos y storage/app/public en un .zip';

    public function handle(): int
    {
        $carpeta = storage_path('app/backups');
        File::ensureDirectoryExists($carpeta);

        $nombre = 'backup-' . now()->format('Y-m-d_His');
        $sql = "{$carpeta}/{$nombre}.sql";
        $zipRuta = "{$carpeta}/{$nombre}.zip";

        $this->exportarBase($sql);

        $zip = new ZipArchive();
        if ($zip->open($zipRuta, ZipArchive::CREATE) !== true) {
            $this->error("No se pudo crear {$zipRuta}");

            return self::FAILURE;
        }
        $zip->addFile($sql, 'base.sql');
        foreach (File::allFiles(storage_path('app/public')) as $archivo) {
            $zip->addFile($archivo->getPathname(), 'storage/' . str_replace('\\', '/', $archivo->getRelativePathname()));
        }
        $zip->close();
        File::delete($sql);

        // rotación: solo los más recientes
        collect(File::glob("{$carpeta}/backup-*.zip"))
            ->sort()
            ->reverse()
            ->slice((int) $this->option('conservar'))
            ->each(fn ($viejo) => File::delete($viejo));

        $this->info("Backup creado: {$zipRuta} (" . round(filesize($zipRuta) / 1048576, 2) . ' MB)');

        return self::SUCCESS;
    }

    private function exportarBase(string $destino): void
    {
        $f = fopen($destino, 'w');
        fwrite($f, "-- Backup {$this->laravel->make('config')->get('app.name')} " . now()->toDateTimeString() . "\n");
        fwrite($f, "SET FOREIGN_KEY_CHECKS=0;\n\n");

        foreach (DB::select('SHOW TABLES') as $fila) {
            $tabla = array_values((array) $fila)[0];
            $crear = array_values((array) DB::selectOne("SHOW CREATE TABLE `{$tabla}`"))[1];
            fwrite($f, "DROP TABLE IF EXISTS `{$tabla}`;\n{$crear};\n\n");

            // de a 500 filas para no cargar tablas grandes enteras en memoria
            DB::table($tabla)->orderByRaw('1')->chunk(500, function ($filas) use ($f, $tabla) {
                $valores = $filas->map(fn ($r) => '(' . collect((array) $r)
                    ->map(fn ($v) => $v === null ? 'NULL' : DB::getPdo()->quote((string) $v))
                    ->implode(',') . ')')->implode(",\n");
                fwrite($f, "INSERT INTO `{$tabla}` VALUES\n{$valores};\n");
            });
            fwrite($f, "\n");
        }

        fwrite($f, "SET FOREIGN_KEY_CHECKS=1;\n");
        fclose($f);
    }
}
