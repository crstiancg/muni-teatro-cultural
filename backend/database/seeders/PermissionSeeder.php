<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = Role::updateOrCreate(['name' => 'Administrador', 'guard_name' => 'api']);

        $modulos = [
            'usuarios' => 'Usuarios',
            'roles' => 'Roles',
            'permisos' => 'Permisos',
            'personas' => 'Personas',
            'comisiones' => 'Comisiones',
            'profesiones' => 'Profesiones',
            'universidades' => 'Universidades',
            'carreras' => 'Carreras',
        ];

        $acciones = [
            'index' => 'Ver lista de',
            'crear' => 'Crear',
            'editar' => 'Editar',
            'eliminar' => 'Eliminar',
        ];

        foreach ($modulos as $modulo => $etiqueta) {
            foreach ($acciones as $accion => $verbo) {
                Permission::updateOrCreate(
                    ['name' => "admin-{$modulo}-{$accion}", 'guard_name' => 'api'],
                    ['description' => "{$verbo} {$etiqueta}"]
                )->assignRole([$admin]);
            }
        }

        // agrupaciones: las crean los artistas; el admin solo las ve y las revisa
        foreach (['admin-agrupaciones-index' => 'Ver lista de Agrupaciones', 'admin-agrupaciones-aprobar' => 'Aprobar agrupaciones'] as $nombre => $descripcion) {
            Permission::updateOrCreate(
                ['name' => $nombre, 'guard_name' => 'api'],
                ['description' => $descripcion]
            )->assignRole([$admin]);
        }

        // fuera del CRUD: aprobar u observar el perfil público de un artista
        Permission::updateOrCreate(
            ['name' => 'admin-personas-aprobar', 'guard_name' => 'api'],
            ['description' => 'Aprobar perfiles públicos']
        )->assignRole([$admin]);

        $user = User::updateOrCreate(
            ['email' => 'password@gmail.com'],
            ['name' => 'Administrador', 'password' => bcrypt('password')]
        );
        $user->assignRole('Administrador');
    }
}
