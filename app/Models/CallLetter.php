<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CallLetter extends Model
{
    protected $fillable = ['employee_id', 'letter_number', 'issue_date', 'meeting_at', 'location', 'purpose', 'description', 'document_path', 'issued_by'];

    protected $casts = ['issue_date' => 'date', 'meeting_at' => 'datetime'];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function issuer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }
}

