<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Employee;
use App\Models\SubDepartment;
use App\Models\Department;
use Illuminate\Support\Facades\DB;

class MigrateSubDepartmentData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'subdepartment:migrate-data {--dry-run : Preview changes without saving}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate sub_department text field to sub_department_id relation';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->info('Running in DRY-RUN mode - no changes will be saved');
        }

        $this->info('Starting sub department data migration...');

        // Get all employees with old sub_department_old text field
        $employees = Employee::whereNotNull('sub_department_old')
            ->where('sub_department_old', '!=', '')
            ->with('department')
            ->get();

        if ($employees->isEmpty()) {
            $this->info('No employees with sub_department_old data found.');
            return 0;
        }

        $this->info("Found {$employees->count()} employees with sub_department data");

        // Group by department and sub department name
        $grouped = $employees->groupBy(function ($employee) {
            return $employee->department_id . '|' . trim($employee->sub_department_old);
        });

        $this->info("Found {$grouped->count()} unique sub department combinations");
        $this->newLine();

        $created = 0;
        $updated = 0;
        $skipped = 0;

        DB::beginTransaction();

        try {
            foreach ($grouped as $key => $group) {
                [$departmentId, $subDeptName] = explode('|', $key);

                $department = Department::find($departmentId);

                if (!$department) {
                    $this->warn("Skipping sub department '$subDeptName' - department not found");
                    $skipped += $group->count();
                    continue;
                }

                // Check if sub department already exists
                $subDepartment = SubDepartment::where('department_id', $departmentId)
                    ->where('name', $subDeptName)
                    ->first();

                if (!$subDepartment) {
                    if ($dryRun) {
                        $this->line("[DRY-RUN] Would create: {$department->name} > {$subDeptName} ({$group->count()} employees)");
                    } else {
                        $subDepartment = SubDepartment::create([
                            'department_id' => $departmentId,
                            'name' => $subDeptName,
                            'description' => "Migrated from old sub_department field",
                            'is_active' => true,
                        ]);
                        $this->info("Created: {$department->name} > {$subDeptName}");
                        $created++;
                    }
                } else {
                    if ($dryRun) {
                        $this->line("[DRY-RUN] Already exists: {$department->name} > {$subDeptName} ({$group->count()} employees)");
                    }
                }

                // Update employees
                if (!$dryRun && $subDepartment) {
                    foreach ($group as $employee) {
                        $employee->sub_department_id = $subDepartment->id;
                        $employee->save();
                        $updated++;
                    }
                    $this->line("  Updated {$group->count()} employees");
                }
            }

            if ($dryRun) {
                DB::rollBack();
                $this->newLine();
                $this->info('DRY-RUN completed. No changes were saved.');
                $this->info('Run without --dry-run flag to apply changes.');
            } else {
                DB::commit();
                $this->newLine();
                $this->info('Migration completed successfully!');
                $this->table(
                    ['Action', 'Count'],
                    [
                        ['Sub Departments Created', $created],
                        ['Employees Updated', $updated],
                        ['Employees Skipped', $skipped],
                    ]
                );
            }

            return 0;
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('Migration failed: ' . $e->getMessage());
            return 1;
        }
    }
}
