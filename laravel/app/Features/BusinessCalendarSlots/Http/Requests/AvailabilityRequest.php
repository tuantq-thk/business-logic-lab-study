<?php

namespace App\Features\BusinessCalendarSlots\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class AvailabilityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'room_id' => ['nullable', 'integer', 'exists:business_calendar_rooms,id,active,1'],
            'date' => ['nullable', 'date_format:Y-m-d'],
            'duration' => ['nullable', 'integer', 'between:15,480'],
            'step' => ['nullable', 'integer', 'between:5,120'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'room_id.exists' => __('business_calendar_slots.validation.room_unavailable'),
            'date.date_format' => __('business_calendar_slots.validation.date_format'),
            'duration.integer' => __('business_calendar_slots.validation.duration_integer'),
            'duration.between' => __('business_calendar_slots.validation.duration_range'),
            'step.integer' => __('business_calendar_slots.validation.step_integer'),
            'step.between' => __('business_calendar_slots.validation.step_range'),
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'room_id' => __('business_calendar_slots.form.room'),
            'date' => __('business_calendar_slots.form.date'),
            'duration' => __('business_calendar_slots.form.duration'),
            'step' => __('business_calendar_slots.form.step'),
        ];
    }
}
