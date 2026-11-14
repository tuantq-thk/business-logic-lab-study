<?php

namespace Database\Factories\CancellationRescheduling;

use App\Features\CancellationRescheduling\Enums\CourseRegistrationStatus;
use App\Features\CancellationRescheduling\Models\Course;
use App\Features\CancellationRescheduling\Models\CourseRegistration;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<CourseRegistration> */
class CourseRegistrationFactory extends Factory
{
    protected $model = CourseRegistration::class;

    public function definition(): array
    {
        return [
            'course_id' => Course::factory(),
            'attendee_id' => User::factory(),
            'status' => CourseRegistrationStatus::Confirmed,
            'paid_amount_minor' => 12900,
            'currency' => 'USD',
        ];
    }

    public function cancelled(): static
    {
        return $this->state(['status' => CourseRegistrationStatus::Cancelled]);
    }
}
