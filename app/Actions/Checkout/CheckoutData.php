<?php

namespace App\Actions\Checkout;

/**
 * Everything checkout needs to turn a cart into an order. Built from the web
 * FormRequest or from MCP tool input — both funnel through the same shape.
 */
readonly class CheckoutData
{
    /**
     * @param  array<string, string|null>  $shippingAddress
     * @param  array<string, string|null>|null  $billingAddress  null = same as shipping
     */
    public function __construct(
        public string $email,
        public array $shippingAddress,
        public int $shippingMethodId,
        public ?array $billingAddress = null,
        public ?string $customerNote = null,
    ) {}

    /**
     * @return array<string, string|null>
     */
    public function billingAddressOrShipping(): array
    {
        return $this->billingAddress ?? $this->shippingAddress;
    }

    /**
     * Shared validation rules for an address payload, used by the checkout
     * FormRequest and the MCP StartCheckout tool.
     *
     * @return array<string, list<string>>
     */
    public static function addressRules(string $prefix): array
    {
        return [
            $prefix => ['required', 'array'],
            "{$prefix}.name" => ['required', 'string', 'max:255'],
            "{$prefix}.line1" => ['required', 'string', 'max:255'],
            "{$prefix}.line2" => ['nullable', 'string', 'max:255'],
            "{$prefix}.city" => ['required', 'string', 'max:255'],
            "{$prefix}.county" => ['nullable', 'string', 'max:255'],
            "{$prefix}.postcode" => ['required', 'string', 'max:32'],
            "{$prefix}.country" => ['required', 'string', 'size:2'],
            "{$prefix}.phone" => ['nullable', 'string', 'max:32'],
        ];
    }
}
