<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(ContentSeeder::class);

        // Starter password, to be changed through the admin panel.
        User::firstOrCreate(
            ['email' => 'admin@delightre.com'],
            ['name' => 'Delight Admin', 'password' => 'Password'],
        );
    }
}
