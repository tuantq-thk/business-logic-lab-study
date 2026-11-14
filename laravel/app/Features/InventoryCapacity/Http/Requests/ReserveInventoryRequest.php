<?php

namespace App\Features\InventoryCapacity\Http\Requests;

use App\Features\InventoryCapacity\Models\InventoryItem;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ReserveInventoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'items' => ['required', 'array', 'size:1'],
            'items.*.order_ref' => ['required', 'string', 'max:64'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:1000000'],
            'items.*.ttl_minutes' => ['required', 'integer', 'min:1', 'max:1440'],
        ];
    }

    /**
     * @return array{order_ref: string, quantity: int, ttl_minutes: int}
     */
    public function itemData(InventoryItem $item): array
    {
        $data = $this->validated()['items'][$item->getKey()] ?? null;

        abort_unless(is_array($data), 422);

        return [
            'order_ref' => (string) $data['order_ref'],
            'quantity' => (int) $data['quantity'],
            'ttl_minutes' => (int) $data['ttl_minutes'],
        ];
    }
}
