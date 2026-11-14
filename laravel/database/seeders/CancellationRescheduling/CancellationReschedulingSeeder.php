<?php

namespace Database\Seeders\CancellationRescheduling;

use App\Features\CancellationRescheduling\Models\Course;
use App\Features\CancellationRescheduling\Models\CourseRegistration;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;

class CancellationReschedulingSeeder extends Seeder
{
    public function run(): void
    {
        $now = CarbonImmutable::now('UTC');
        $attendee = User::query()->where('email', 'test@example.com')->first();

        foreach ([1, 2] as $week) {
            $starts = $now->startOfDay()->addWeeks($week)->setTime(9, 0);
            $course = Course::query()->firstOrNew(['title' => 'BL-07 Business Rules Cohort '.$week]);
            $course->forceFill([
                'starts_at_utc' => $starts,
                'cancel_cutoff_at_utc' => $starts->subDays(2),
                'capacity' => 8,
                'price_minor' => 12900,
                'currency' => 'USD',
                'status' => 'open',
            ])->saveOrFail();

            if ($week === 1 && $attendee !== null) {
                CourseRegistration::query()->firstOrCreate(
                    ['course_id' => $course->id, 'attendee_id' => $attendee->id],
                    ['paid_amount_minor' => 12900, 'currency' => 'USD'],
                );
            }
        }
    }
}
