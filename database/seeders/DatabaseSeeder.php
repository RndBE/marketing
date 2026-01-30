<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        // User::factory(10)->create();



        $this->call([
            MarketingSeeder::class,
            UserSeeder::class,
            PicSeeder::class,
            AlurPenawaranSeeder::class,
            RbacSeeder::class,
            UsulanPermissionSeeder::class,
            PenawaranTermTemplateSeeder::class,
            ProductSeeder::class,
            ProductDetailSeeder::class,
        ]);
    }
}
