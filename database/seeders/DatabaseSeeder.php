<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $users = [
            ['name' => 'Owner', 'email' => 'owner@example.com', 'role' => 'owner'],
            ['name' => 'Admin', 'email' => 'admin@example.com', 'role' => 'admin'],
            ['name' => 'Resepsionis', 'email' => 'resepsionis@example.com', 'role' => 'admin'],
        ];
        foreach ($users as $u) {
            if (!User::where('email', $u['email'])->exists()) {
                User::factory()->create($u);
            }
        }

        $this->call([
            KamarSeeder::class,
            PelangganSeeder::class,
            CafeProductSeeder::class,
            BookingOrderSeeder::class,
        ]);
    }
}
