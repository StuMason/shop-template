<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductVariantRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'sku' => [
                'required',
                'string',
                'max:64',
                Rule::unique('product_variants', 'sku')->ignore($this->route('variant')),
            ],
            'printful_variant_id' => ['nullable', 'integer'],
            'price' => ['required', 'integer', 'min:0'],
            'compare_at_price' => ['nullable', 'integer', 'min:0', 'gt:price'],
            'stock' => ['required', 'integer', 'min:0'],
            'low_stock_threshold' => ['integer', 'min:0'],
            'is_default' => ['boolean'],
            'option_value_ids' => ['array'],
            'option_value_ids.*' => ['integer', Rule::exists('product_option_values', 'id')],
        ];
    }
}
