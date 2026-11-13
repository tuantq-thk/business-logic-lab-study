<?php

namespace App\Features\StateTransition\Http\Requests;

use App\Features\StateTransition\Enums\SupportTicketStatus;
use App\Features\StateTransition\Models\SupportTicket;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class ChangeSupportTicketStatusRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $ticket = $this->route('ticket');

        return $ticket instanceof SupportTicket
            && ($this->user()?->can('changeStatus', $ticket) ?? false);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'to_status' => ['required', new Enum(SupportTicketStatus::class)],
            'reason' => ['nullable', 'string', 'max:500'],
            'version' => ['required', 'integer', 'min:0'],
        ];
    }
}
