<?php

namespace App\Features\InventoryCapacity\Http\Requests;

use App\Features\InventoryCapacity\Models\InventoryItem;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class AdjustInventoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('adjust', InventoryItem::class) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'delta' => ['required', 'integer', 'not_in:0', 'between:-1000000,1000000'],
            'reason' => ['required', 'string', 'min:3', 'max:500'],
            'reference' => ['nullable', 'string', 'max:64'],
        ];
    }
}
