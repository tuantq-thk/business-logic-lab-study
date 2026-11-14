<?php

namespace App\Features\BookingOverlap\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreRoomBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'room_id' => ['required', 'integer', 'exists:business_calendar_rooms,id,active,1'],
            'date' => ['required', 'date_format:Y-m-d'],
            'start' => ['required', 'date_format:H:i'],
            'duration' => ['required', 'integer', 'between:15,480'],
            'idempotency_key' => ['nullable', 'uuid'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'room_id.exists' => __('booking_overlap.validation.room_unavailable'),
            'date.date_format' => __('booking_overlap.validation.date_format'),
            'start.date_format' => __('booking_overlap.validation.start_format'),
            'duration.between' => __('booking_overlap.validation.duration_range'),
            'idempotency_key.uuid' => __('booking_overlap.validation.idempotency_key'),
        ];
    }
}
