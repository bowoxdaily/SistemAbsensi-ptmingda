<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PositionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $positions = [
            [
                'code' => 'DIR',
                'name' => 'Direktur',
                'description' => 'Direktur perusahaan yang bertanggung jawab atas seluruh operasional',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'MGR',
                'name' => 'Manager',
                'description' => 'Manager yang memimpin departemen atau divisi tertentu',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'SPV',
                'name' => 'Supervisor',
                'description' => 'Supervisor yang mengawasi tim operasional',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'TL',
                'name' => 'Team Leader',
                'description' => 'Pemimpin tim yang bertanggung jawab atas koordinasi tim',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'SR',
                'name' => 'Senior Staff',
                'description' => 'Staff senior dengan pengalaman dan keahlian tinggi',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'STF',
                'name' => 'Staff',
                'description' => 'Staff pelaksana tugas operasional',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'JR',
                'name' => 'Junior Staff',
                'description' => 'Staff junior atau fresh graduate',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'INT',
                'name' => 'Intern',
                'description' => 'Magang atau peserta program internship',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('positions')->insert($positions);
    }
}
