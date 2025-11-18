<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DepartmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $departments = [
            [
                'name' => 'Human Resources',
                'description' => 'Departemen yang mengelola sumber daya manusia',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'IT & Development',
                'description' => 'Departemen teknologi informasi dan pengembangan',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Finance & Accounting',
                'description' => 'Departemen keuangan dan akuntansi',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Marketing',
                'description' => 'Departemen pemasaran dan promosi',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Operations',
                'description' => 'Departemen operasional',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Sales',
                'description' => 'Departemen penjualan',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('departments')->insert($departments);
    }
}
