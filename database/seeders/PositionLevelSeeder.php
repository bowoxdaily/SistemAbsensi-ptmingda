<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PositionLevelSeeder extends Seeder
{
    public function run(): void
    {
        // Nonaktifkan jabatan lama yang digantikan versi berlevel
        DB::table('positions')
            ->whereIn('code', ['OPR', 'SPV', 'KBG', 'ASSMGR'])
            ->update(['status' => 'inactive']);

        // Jabatan baru dengan level
        $positions = [
            // Operator: 2 level
            ['code' => 'OPR1', 'name' => 'OPERATOR', 'level' => 1, 'description' => 'Operator Level 1', 'status' => 'active'],
            ['code' => 'OPR2', 'name' => 'OPERATOR', 'level' => 2, 'description' => 'Operator Level 2', 'status' => 'active'],
            // Supervisor: 3 level
            ['code' => 'SPV1', 'name' => 'SUPERVISOR', 'level' => 1, 'description' => 'Supervisor Level 1', 'status' => 'active'],
            ['code' => 'SPV2', 'name' => 'SUPERVISOR', 'level' => 2, 'description' => 'Supervisor Level 2', 'status' => 'active'],
            ['code' => 'SPV3', 'name' => 'SUPERVISOR', 'level' => 3, 'description' => 'Supervisor Level 3', 'status' => 'active'],
            // Kabag: 3 level
            ['code' => 'KBG1', 'name' => 'KABAG', 'level' => 1, 'description' => 'Kepala Bagian Level 1', 'status' => 'active'],
            ['code' => 'KBG2', 'name' => 'KABAG', 'level' => 2, 'description' => 'Kepala Bagian Level 2', 'status' => 'active'],
            ['code' => 'KBG3', 'name' => 'KABAG', 'level' => 3, 'description' => 'Kepala Bagian Level 3', 'status' => 'active'],
            // Asmen: 3 level
            ['code' => 'ASMN1', 'name' => 'ASMEN', 'level' => 1, 'description' => 'Asisten Manager Level 1', 'status' => 'active'],
            ['code' => 'ASMN2', 'name' => 'ASMEN', 'level' => 2, 'description' => 'Asisten Manager Level 2', 'status' => 'active'],
            ['code' => 'ASMN3', 'name' => 'ASMEN', 'level' => 3, 'description' => 'Asisten Manager Level 3', 'status' => 'active'],
        ];

        foreach ($positions as $pos) {
            DB::table('positions')->insertOrIgnore(array_merge($pos, [
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }
}
