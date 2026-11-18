<?php

namespace App\Console\Commands;

use App\Models\User;
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

        // el Administrador tiene todo lo del panel (aditivo: no le quita nada),
        // MENOS lo del artista: con eso vería "Mi espacio" sin tener ficha
        $deArtista = array_keys(config('permisos.artista', []));
        $admin->givePermissionTo(array_values(array_diff(array_keys($declarados), $deArtista)));
        $sobranAlAdmin = array_values(array_intersect($deArtista, $admin->permissions->pluck('name')->all()));
        if ($sobranAlAdmin) {
            $admin->revokePermissionTo($sobranAlAdmin);
        }

        // los demás roles se crean con sus permisos SOLO la primera vez: después
        // se administran desde el panel y un deploy no les devuelve lo quitado
        foreach (config('permisos.roles', []) as $nombre => $permisosDelRol) {
            $rol = Role::firstOrCreate(['name' => $nombre, 'guard_name' => $guard]);
            if ($rol->wasRecentlyCreated) {
                $rol->syncPermissions(array_values(array_intersect($permisosDelRol, array_keys($declarados))));
                $this->info("Rol creado: {$nombre}");

                // rol Artista recién creado: lo reciben las cuentas que ya tenían ficha
                if ($nombre === config('permisos.rol_artista')) {
                    $cuentas = User::whereHas('persona')->get();
                    $cuentas->each->assignRole($rol);
                    $this->info("Rol {$nombre} asignado a {$cuentas->count()} cuentas con ficha de persona.");
                }
            }
        }
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

        return [...$permisos, ...config('permisos.extra'), ...config('permisos.artista', [])];
    }
}
