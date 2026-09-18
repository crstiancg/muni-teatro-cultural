<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        // User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);


        //client_id = 01a0b014-f3f5-71e2-8cbd-d8a9d0ca21b9
        //cliente_secret = wTLBeRCnNqWbmzrQ1KJphcxBKdts654k5bz5WUIH

        DB::table('oauth_clients')->insert([
            'id' => '01a0b014-f3f5-71e2-8cbd-d8a9d0ca21b9',
            'name' => 'Laravel Personal Access Client',
            // Passport valida el secret con Hash::check(), así que tiene que
            // guardarse hasheado; el valor plano sigue siendo el que va en
            // el .env del frontend (QCLI_APP_SECRET).
            'secret' => Hash::make('wTLBeRCnNqWbmzrQ1KJphcxBKdts654k5bz5WUIH'),
            'provider' => 'users',
            'revoked' => false,
            'redirect_uris' => '[]',
            'grant_types' => '["password","refresh_token"]',
        ]);

        $this->call([
            UbigeoSeeder::class,
            PermissionSeeder::class,
            ComisionSeeder::class,
        ]);
    }
}
