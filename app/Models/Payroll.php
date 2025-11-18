<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payroll extends Model
{
    protected $fillable = [
        'employee_id',
        'payroll_code',
        'period_month',
        'payment_date',
        'basic_salary',
        'allowance_transport',
        'allowance_meal',
        'allowance_position',
        'allowance_others',
        'overtime_pay',
        'bonus',
        'deduction_late',
        'deduction_absent',
        'deduction_loan',
        'deduction_bpjs',
        'deduction_tax',
        'deduction_others',
        'total_earnings',
        'total_deductions',
        'net_salary',
        'total_days_present',
        'total_days_late',
        'total_days_absent',
        'total_days_leave',
        'status',
        'notes',
        'sent_at',
        'payment_proof',
        'paid_at',
        'created_by',
    ];

    protected $casts = [
        'payment_date' => 'date',
        'basic_salary' => 'decimal:2',
        'allowance_transport' => 'decimal:2',
        'allowance_meal' => 'decimal:2',
        'allowance_position' => 'decimal:2',
        'allowance_others' => 'decimal:2',
        'overtime_pay' => 'decimal:2',
        'bonus' => 'decimal:2',
        'deduction_late' => 'decimal:2',
        'deduction_absent' => 'decimal:2',
        'deduction_loan' => 'decimal:2',
        'deduction_bpjs' => 'decimal:2',
        'deduction_tax' => 'decimal:2',
        'deduction_others' => 'decimal:2',
        'total_earnings' => 'decimal:2',
        'total_deductions' => 'decimal:2',
        'net_salary' => 'decimal:2',
        'sent_at' => 'datetime',
        'paid_at' => 'datetime',
    ];

    /**
     * Relationship to Employee
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Relationship to User who created the payroll
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Generate unique payroll code
     */
    public static function generatePayrollCode($periodMonth)
    {
        $yearMonth = str_replace('-', '', $periodMonth); // 202510
        $lastPayroll = self::where('payroll_code', 'like', "PAY-{$yearMonth}-%")
            ->orderBy('payroll_code', 'desc')
            ->first();

        if ($lastPayroll) {
            $lastNumber = (int) substr($lastPayroll->payroll_code, -3);
            $newNumber = str_pad($lastNumber + 1, 3, '0', STR_PAD_LEFT);
        } else {
            $newNumber = '001';
        }

        return "PAY-{$yearMonth}-{$newNumber}";
    }

    /**
     * Calculate total earnings
     */
    public function calculateTotalEarnings()
    {
        return $this->basic_salary
            + $this->allowance_transport
            + $this->allowance_meal
            + $this->allowance_position
            + $this->allowance_others
            + $this->overtime_pay
            + $this->bonus;
    }

    /**
     * Calculate total deductions
     */
    public function calculateTotalDeductions()
    {
        return $this->deduction_late
            + $this->deduction_absent
            + $this->deduction_loan
            + $this->deduction_bpjs
            + $this->deduction_tax
            + $this->deduction_others;
    }

    /**
     * Calculate net salary
     */
    public function calculateNetSalary()
    {
        return $this->calculateTotalEarnings() - $this->calculateTotalDeductions();
    }

    /**
     * Format period for display
     */
    public function getFormattedPeriodAttribute()
    {
        $months = [
            '01' => 'Januari',
            '02' => 'Februari',
            '03' => 'Maret',
            '04' => 'April',
            '05' => 'Mei',
            '06' => 'Juni',
            '07' => 'Juli',
            '08' => 'Agustus',
            '09' => 'September',
            '10' => 'Oktober',
            '11' => 'November',
            '12' => 'Desember'
        ];

        list($year, $month) = explode('-', $this->period_month);
        return $months[$month] . ' ' . $year;
    }
}
