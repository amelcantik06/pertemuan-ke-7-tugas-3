<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'adminbaru@minimarket.com'],
            [
                'name' => 'Admin Baru',
                'role' => 'admin',
                'password' => 'admin123',
            ]
        );

        User::firstOrCreate(
            ['email' => 'kasir@minimarket.com'],
            [
                'name' => 'Kasir',
                'role' => 'kasir',
                'password' => 'kasir123',
            ]
        );
    }
}
