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
        // Seed departments, positions, dan work schedules
        $this->call([
            DepartmentSeeder::class,
            PositionSeeder::class,
            WorkScheduleSeeder::class,
        ]);

        // Create admin user
        User::create([
            'name' => 'Administrator',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'status' => 'aktif',
        ]);

        // Create manager user
        User::create([
            'name' => 'Manager HR',
            'email' => 'manager@example.com',
            'password' => bcrypt('password'),
            'role' => 'manager',
            'status' => 'aktif',
        ]);

        // Create sample karyawan user
        User::create([
            'name' => 'Karyawan Demo',
            'email' => 'karyawan@example.com',
            'password' => bcrypt('password'),
            'role' => 'karyawan',
            'status' => 'aktif',
        ]);
    }
}
