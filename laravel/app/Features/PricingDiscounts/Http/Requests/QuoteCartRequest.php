<?php

namespace App\Features\PricingDiscounts\Http\Requests;

use App\Features\PricingDiscounts\Models\PricingPromotion;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class QuoteCartRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'quantities' => ['required', 'array', 'size:2'],
            'quantities.book-php' => ['required', 'integer', 'min:1', 'max:99'],
            'quantities.book-sql' => ['required', 'integer', 'min:1', 'max:99'],
            'rules' => ['nullable', 'array', 'max:5'],
            'rules.*' => ['string'],
        ];
    }

    protected function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $rules = $this->input('rules', []);
            if (! is_array($rules)) {
                return;
            }

            $availableCodes = PricingPromotion::query()
                ->currentlyActive(now())
                ->whereIn('code', array_values(array_filter($rules, 'is_string')))
                ->pluck('code')
                ->all();

            foreach ($rules as $index => $code) {
                if (! is_string($code) || ! in_array($code, $availableCodes, true)) {
                    $validator->errors()->add("rules.{$index}", __('validation.in', [
                        'attribute' => __('pricing_discounts.validation.promotion'),
                    ]));
                }
            }
        });
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'quantities.book-php' => __('pricing_discounts.validation.php_quantity'),
            'quantities.book-sql' => __('pricing_discounts.validation.sql_quantity'),
            'rules.*' => __('pricing_discounts.validation.promotion'),
        ];
    }

    /**
     * @return array<string, int>
     */
    public function quantities(): array
    {
        return [
            'book-php' => $this->integer('quantities.book-php'),
            'book-sql' => $this->integer('quantities.book-sql'),
        ];
    }

    /**
     * @return list<string>
     */
    public function selectedRuleCodes(): array
    {
        $rules = $this->input('rules', []);

        return is_array($rules) ? array_values(array_unique($rules)) : [];
    }
}
