<?php

namespace App\Http\Requests\Checkout;

use App\Actions\Cart\ResolveCart;
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
                // Digital-only baskets need no delivery.
                $this->cartIsFullyDigital() ? 'nullable' : 'required',
                'integer',
                Rule::exists('shipping_methods', 'id')->where('is_active', true),
            ],
            'customer_note' => ['nullable', 'string', 'max:2000'],
            'billing_same_as_shipping' => ['boolean'],
            ...CheckoutData::addressRules('shipping_address', digitalOnly: $this->cartIsFullyDigital()),
            ...$this->boolean('billing_same_as_shipping', true)
                ? []
                : CheckoutData::addressRules('billing_address', digitalOnly: $this->cartIsFullyDigital()),
        ];
    }

    protected function cartIsFullyDigital(): bool
    {
        $cart = app(ResolveCart::class)->find($this->user(), $this->session()->get('cart_token'));

        return $cart !== null && $cart->isFullyDigital();
    }

    public function toCheckoutData(): CheckoutData
    {
        return new CheckoutData(
            email: $this->validated('email'),
            shippingAddress: $this->validated('shipping_address'),
            shippingMethodId: $this->validated('shipping_method_id') !== null
                ? (int) $this->validated('shipping_method_id')
                : null,
            billingAddress: $this->boolean('billing_same_as_shipping', true)
                ? null
                : $this->validated('billing_address'),
            customerNote: $this->validated('customer_note'),
        );
    }
}
