<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;

// Instalación inicial: permisos + usuario administrador.
// Los permisos se declaran en config/permisos.php (no acá) y ya se sincronizan
// solos con cada "php artisan migrate"; esto es por si se corre el seeder aparte.
class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        Artisan::call('permisos:sincronizar');

        // firstOrCreate y no updateOrCreate: si el admin ya existe, NO se le
        // vuelve a poner la contraseña "password"
        $user = User::firstOrCreate(
            ['email' => 'password@gmail.com'],
            ['name' => 'Administrador', 'password' => bcrypt('password')]
        );
        $user->assignRole(config('permisos.rol_admin'));
    }
}
