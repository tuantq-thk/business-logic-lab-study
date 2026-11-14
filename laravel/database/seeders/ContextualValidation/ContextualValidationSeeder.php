<?php

namespace Database\Seeders\ContextualValidation;

use App\Features\ContextualValidation\Models\WorkshopEvent;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class ContextualValidationSeeder extends Seeder
{
    public function run(): void
    {
        $now = CarbonImmutable::now('UTC');
        $registrationOpensAt = $now->subHour();
        $registrationClosesAt = $now->addDays(6);
        $startsAt = $now->addDays(7);

        $this->upsertEvent('BL-06 Contextual Validation Lab', [
            'description' => 'Compare transport validation with policy validation at write time.',
            'capacity' => 12,
            'starts_at_utc' => $startsAt,
            'registration_opens_at_utc' => $registrationOpensAt,
            'registration_closes_at_utc' => $registrationClosesAt,
        ]);

        $this->upsertEvent('BL-06 Invite-only Deep Dive', [
            'description' => 'A protected scenario for server-side eligibility checks.',
            'capacity' => 8,
            'invite_code_hash' => Hash::make('LAB-INVITE-2026'),
            'starts_at_utc' => $startsAt,
            'registration_opens_at_utc' => $registrationOpensAt,
            'registration_closes_at_utc' => $registrationClosesAt,
        ]);
    }

    /** @param array<string, mixed> $attributes */
    private function upsertEvent(string $title, array $attributes): void
    {
        $event = WorkshopEvent::query()->firstOrNew(['title' => $title]);
        $event->forceFill(array_merge(
            WorkshopEvent::factory()->raw(),
            $attributes,
            ['title' => $title],
        ))->saveOrFail();
    }
}
