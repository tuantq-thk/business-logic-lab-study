<?php

namespace Database\Factories\CancellationRescheduling;

use App\Features\CancellationRescheduling\Enums\CourseStatus;
use App\Features\CancellationRescheduling\Models\Course;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Course> */
class CourseFactory extends Factory
{
    protected $model = Course::class;

    public function definition(): array
    {
        $starts = CarbonImmutable::now('UTC')->startOfDay()->addWeeks(2)->setTime(9, 0);

        return [
            'title' => fake()->sentence(3),
            'starts_at_utc' => $starts,
            'cancel_cutoff_at_utc' => $starts->subDays(2),
            'capacity' => 20,
            'price_minor' => 12900,
            'currency' => 'USD',
            'status' => CourseStatus::Open,
        ];
    }
}
