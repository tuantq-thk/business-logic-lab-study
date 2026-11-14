<?php

namespace App\Features\BookingOverlap\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CancelRoomBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [];
    }
}
