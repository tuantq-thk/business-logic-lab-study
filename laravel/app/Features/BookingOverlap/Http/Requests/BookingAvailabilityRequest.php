<?php

namespace App\Features\BookingOverlap\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class BookingAvailabilityRequest extends FormRequest
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
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'room_id.exists' => __('booking_overlap.validation.room_unavailable'),
            'date.date_format' => __('booking_overlap.validation.date_format'),
        ];
    }
}
