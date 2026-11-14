<?php

namespace App\Features\ContextualValidation\Http\Requests;

use App\Features\ContextualValidation\Enums\TicketType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreEventRegistrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('invite_code') && is_string($this->input('invite_code'))) {
            $this->merge(['invite_code' => trim($this->input('invite_code'))]);
        }
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'event_id' => ['required', 'integer', 'exists:contextual_validation_events,id'],
            'ticket_type' => ['required', new Enum(TicketType::class)],
            'invite_code' => ['nullable', 'string', 'max:120'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'event_id.exists' => __('contextual_validation.validation.event_unavailable'),
            'ticket_type.enum' => __('contextual_validation.validation.ticket_type'),
            'invite_code.string' => __('contextual_validation.validation.invite_code'),
            'invite_code.max' => __('contextual_validation.validation.invite_code'),
        ];
    }
}
