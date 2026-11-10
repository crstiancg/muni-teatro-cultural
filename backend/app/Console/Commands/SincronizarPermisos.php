<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

// Crea los permisos declarados en config/permisos.php que falten y se los da al
// Administrador. Idempotente: se puede correr siempre. Corre solo después de
// cada "php artisan migrate" (ver AppServiceProvider).
class SincronizarPermisos extends Command
{
    protected $signature = 'permisos:sincronizar';

    protected $description = 'Crea los permisos de config/permisos.php que falten y los asigna al Administrador';

    public function handle(PermissionRegistrar $registro): int
    {
        // en una instalación a medio migrar todavía no existen las tablas de spatie
        if (! Schema::hasTable(config('permission.table_names.permissions'))) {
            $this->warn('Las tablas de permisos todavía no existen: se omite la sincronización.');

            return self::SUCCESS;
        }

        $guard = config('permisos.guard');
        $admin = Role::firstOrCreate(['name' => config('permisos.rol_admin'), 'guard_name' => $guard]);

        $declarados = $this->declarados();
        $nuevos = [];

        foreach ($declarados as $nombre => $descripcion) {
            $permiso = Permission::firstOrNew(['name' => $nombre, 'guard_name' => $guard]);
            if (! $permiso->exists) {
                $nuevos[] = $nombre;
            }
            $permiso->description = $descripcion;
            $permiso->save();
        }

        // el Administrador tiene todo lo declarado (aditivo: no le quita nada)
        $admin->givePermissionTo(array_keys($declarados));
        $registro->forgetCachedPermissions();

        $this->info($nuevos
            ? 'Permisos nuevos: ' . implode(', ', $nuevos)
            : 'Permisos al día (' . count($declarados) . ').');

        $sobrantes = Permission::where('guard_name', $guard)
            ->whereNotIn('name', array_keys($declarados))
            ->pluck('name');
        if ($sobrantes->isNotEmpty()) {
            $this->warn('En la base pero no en config/permisos.php (no se borran): ' . $sobrantes->implode(', '));
        }

        return self::SUCCESS;
    }

    /** @return array<string, string> nombre => descripción */
    private function declarados(): array
    {
        $permisos = [];
        foreach (config('permisos.modulos') as $modulo => $etiqueta) {
            foreach (config('permisos.acciones') as $accion => $verbo) {
                $permisos["admin-{$modulo}-{$accion}"] = "{$verbo} {$etiqueta}";
            }
        }

        return [...$permisos, ...config('permisos.extra')];
    }
}
