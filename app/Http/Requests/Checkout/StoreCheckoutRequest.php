<?php

namespace App\Http\Requests\Checkout;

use App\Actions\Checkout\CheckoutData;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCheckoutRequest extends FormRequest
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
            'email' => ['required', 'email', 'max:255'],
            'shipping_method_id' => [
                'required',
                'integer',
                Rule::exists('shipping_methods', 'id')->where('is_active', true),
            ],
            'customer_note' => ['nullable', 'string', 'max:2000'],
            'billing_same_as_shipping' => ['boolean'],
            ...CheckoutData::addressRules('shipping_address'),
            ...$this->boolean('billing_same_as_shipping', true)
                ? []
                : CheckoutData::addressRules('billing_address'),
        ];
    }

    public function toCheckoutData(): CheckoutData
    {
        return new CheckoutData(
            email: $this->validated('email'),
            shippingAddress: $this->validated('shipping_address'),
            shippingMethodId: (int) $this->validated('shipping_method_id'),
            billingAddress: $this->boolean('billing_same_as_shipping', true)
                ? null
                : $this->validated('billing_address'),
            customerNote: $this->validated('customer_note'),
        );
    }
}
