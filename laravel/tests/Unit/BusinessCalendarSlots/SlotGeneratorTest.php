<?php

use App\Features\BusinessCalendarSlots\Services\SlotGenerator;
use Carbon\CarbonImmutable;

test('generates half-open slots inside multiple local windows', function () {
    $slots = app(SlotGenerator::class)->generate(
        timezone: 'Asia/Ho_Chi_Minh',
        localDate: '2026-11-16',
        windows: [
            ['opens_at_local' => '09:00', 'closes_at_local' => '12:00'],
            ['opens_at_local' => '13:00', 'closes_at_local' => '17:00'],
        ],
        durationMinutes: 120,
        stepMinutes: 60,
        now: CarbonImmutable::parse('2026-11-15 00:00:00', 'UTC'),
    );

    expect($slots)->toHaveCount(5)
        ->and($slots[0]->startsAt->format('H:i'))->toBe('09:00')
        ->and($slots[1]->startsAt->format('H:i'))->toBe('10:00')
        ->and($slots[2]->startsAt->format('H:i'))->toBe('13:00')
        ->and($slots[4]->endsAt->format('H:i'))->toBe('17:00');
});

test('excludes slots that started before the injected clock', function () {
    $slots = app(SlotGenerator::class)->generate(
        timezone: 'Asia/Ho_Chi_Minh',
        localDate: '2026-11-16',
        windows: [['opens_at_local' => '09:00', 'closes_at_local' => '12:00']],
        durationMinutes: 60,
        stepMinutes: 30,
        now: CarbonImmutable::parse('2026-11-16 10:15:00', 'Asia/Ho_Chi_Minh'),
    );

    expect($slots)->toHaveCount(2)
        ->and($slots[0]->startsAt->format('H:i'))->toBe('10:30')
        ->and($slots[1]->startsAt->format('H:i'))->toBe('11:00');
});

test('rejects a local time that does not exist during a DST gap', function () {
    expect(fn () => app(SlotGenerator::class)->generate(
        timezone: 'Europe/Berlin',
        localDate: '2026-03-29',
        windows: [['opens_at_local' => '02:00', 'closes_at_local' => '04:00']],
        durationMinutes: 30,
        stepMinutes: 30,
        now: CarbonImmutable::parse('2026-03-28 00:00:00', 'UTC'),
    ))->toThrow(InvalidArgumentException::class);
});
