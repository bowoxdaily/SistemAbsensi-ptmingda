<?php

use Carbon\Carbon;

test('late minutes count actual minutes after scheduled start', function () {
    $scheduled = Carbon::createFromFormat('Y-m-d H:i', '2026-11-16 08:00');
    $checkIn = Carbon::createFromFormat('Y-m-d H:i', '2026-11-16 08:02');

    expect((int) $scheduled->diffInMinutes($checkIn))->toBe(2);
});

test('on-time check-in is not later than scheduled start', function () {
    $scheduled = Carbon::createFromFormat('Y-m-d H:i', '2026-11-16 08:00');
    $checkIn = Carbon::createFromFormat('Y-m-d H:i', '2026-11-16 08:00');

    expect($checkIn->gt($scheduled))->toBeFalse();
});
