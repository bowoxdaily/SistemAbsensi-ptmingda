<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        // For each (opl_id, employee_id) group, keep one record (with the latest read_at)
        $groups = DB::table('opl_reads')
            ->select('opl_id', 'employee_id', DB::raw('MAX(read_at) as max_read_at'), DB::raw('MAX(id) as keep_id'))
            ->groupBy('opl_id', 'employee_id')
            ->get();

        $keepIds = [];
        foreach ($groups as $g) {
            $keepIds[] = $g->keep_id;
            // update the kept row's read_at to the group's max_read_at
            DB::table('opl_reads')->where('id', $g->keep_id)->update(['read_at' => $g->max_read_at]);
        }

        if (!empty($keepIds)) {
            // delete all other duplicate rows
            DB::table('opl_reads')->whereNotIn('id', $keepIds)->delete();
        }

        // Add unique index to prevent future duplicates
        Schema::table('opl_reads', function (Blueprint $table) {
            $table->unique(['opl_id', 'employee_id']);
        });
    }

    public function down()
    {
        Schema::table('opl_reads', function (Blueprint $table) {
            $table->dropUnique(['opl_id', 'employee_id']);
        });
    }
};
