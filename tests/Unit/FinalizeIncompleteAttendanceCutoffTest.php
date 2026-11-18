<?php

use Carbon\Carbon;

it('uses two weekdays as finalization cutoff', function () {
    expect(Carbon::parse('2026-11-17')->subWeekdays(2)->toDateString())->toBe('2026-11-13')
        ->and(Carbon::parse('2026-11-16')->subWeekdays(2)->toDateString())->toBe('2026-11-12')
        ->and(Carbon::parse('2026-11-14')->subWeekdays(2)->toDateString())->toBe('2026-11-12'); // Saturday excludes Friday attendance.
});
