<?php

use App\Models\WarningLetter;
use Carbon\Carbon;

it('considers warning letters inactive on or after their completion date', function () {
    Carbon::setTestNow('2026-11-17 12:00:00');

    $warningLetter = new WarningLetter(['status' => 'aktif', 'completion_date' => '2026-11-17']);

    expect($warningLetter->isActive())->toBeFalse();

    $warningLetter->completion_date = '2026-11-18';
    expect($warningLetter->isActive())->toBeTrue();

    Carbon::setTestNow();
});
