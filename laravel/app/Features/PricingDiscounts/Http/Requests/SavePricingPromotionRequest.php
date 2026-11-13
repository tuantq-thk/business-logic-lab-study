<?php

namespace App\Features\PricingDiscounts\Http\Requests;

use App\Features\PricingDiscounts\Enums\DiscountKind;
use App\Features\PricingDiscounts\Models\PricingPromotion;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class SavePricingPromotionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage-catalog') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'code' => strtoupper(trim((string) $this->input('code'))),
        ]);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $promotion = $this->route('promotion');
        $ignoreId = $promotion instanceof PricingPromotion ? $promotion->getKey() : null;

        return [
            'code' => [
                'required',
                'string',
                'max:50',
                'regex:/\A[A-Z0-9_-]+\z/',
                Rule::unique('pricing_promotions', 'code')->ignore($ignoreId),
            ],
            'kind' => ['required', Rule::in(array_column(DiscountKind::cases(), 'value'))],
            'value' => ['required', 'integer', 'min:0'],
            'minimum_subtotal_minor' => ['required', 'integer', 'min:0'],
            'priority' => ['required', 'integer', 'min:0', 'max:10000'],
            'stackable' => ['required', 'boolean'],
            'active' => ['required', 'boolean'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
        ];
    }

    protected function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($this->input('kind') === DiscountKind::Percentage->value && (int) $this->input('value') > 100) {
                $validator->errors()->add('value', __('pricing_discounts.admin.validation.percentage_max'));
            }
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function promotionData(): array
    {
        return [
            'code' => $this->string('code')->trim()->upper()->toString(),
            'kind' => $this->string('kind')->toString(),
            'value' => $this->integer('value'),
            'minimum_subtotal_minor' => $this->integer('minimum_subtotal_minor'),
            'priority' => $this->integer('priority'),
            'stackable' => $this->boolean('stackable'),
            'active' => $this->boolean('active'),
            'starts_at' => $this->input('starts_at') ?: null,
            'ends_at' => $this->input('ends_at') ?: null,
        ];
    }
}
