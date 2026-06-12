import { useForm, usePage } from '@inertiajs/react';
import InputError from '@/components/input-error';
import { Seo } from '@/components/seo';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { store } from '@/routes/checkout';
import type { Basket } from '@/types';

type AddressForm = {
    name: string;
    line1: string;
    line2: string;
    city: string;
    county: string;
    postcode: string;
    country: string;
    phone: string;
};

type CheckoutShowProps = {
    shippingMethods: {
        id: number;
        name: string;
        description: string | null;
        price: string;
    }[];
    email: string | null;
    defaultAddress: Partial<AddressForm> | null;
    countries: string[];
};

const EMPTY_ADDRESS: AddressForm = {
    name: '',
    line1: '',
    line2: '',
    city: '',
    county: '',
    postcode: '',
    country: 'GB',
    phone: '',
};

function AddressFields({
    prefix,
    value,
    errors,
    countries,
    onChange,
}: {
    prefix: string;
    value: AddressForm;
    errors: Record<string, string>;
    countries: string[];
    onChange: (field: keyof AddressForm, fieldValue: string) => void;
}) {
    const field = (name: keyof AddressForm) => `${prefix}.${name}`;

    return (
        <div className="grid gap-4">
            <div className="grid gap-2">
                <Label htmlFor={field('name')}>Full name</Label>
                <Input
                    id={field('name')}
                    autoComplete="name"
                    value={value.name}
                    onChange={(event) => onChange('name', event.target.value)}
                    required
                />
                <InputError message={errors[field('name')]} />
            </div>
            <div className="grid gap-2">
                <Label htmlFor={field('line1')}>Address line 1</Label>
                <Input
                    id={field('line1')}
                    autoComplete="address-line1"
                    value={value.line1}
                    onChange={(event) => onChange('line1', event.target.value)}
                    required
                />
                <InputError message={errors[field('line1')]} />
            </div>
            <div className="grid gap-2">
                <Label htmlFor={field('line2')}>
                    Address line 2{' '}
                    <span className="text-muted-foreground">(optional)</span>
                </Label>
                <Input
                    id={field('line2')}
                    autoComplete="address-line2"
                    value={value.line2}
                    onChange={(event) => onChange('line2', event.target.value)}
                />
            </div>
            <div className="grid gap-4 sm:grid-cols-2">
                <div className="grid gap-2">
                    <Label htmlFor={field('city')}>Town / city</Label>
                    <Input
                        id={field('city')}
                        autoComplete="address-level2"
                        value={value.city}
                        onChange={(event) =>
                            onChange('city', event.target.value)
                        }
                        required
                    />
                    <InputError message={errors[field('city')]} />
                </div>
                <div className="grid gap-2">
                    <Label htmlFor={field('postcode')}>Postcode</Label>
                    <Input
                        id={field('postcode')}
                        autoComplete="postal-code"
                        value={value.postcode}
                        onChange={(event) =>
                            onChange('postcode', event.target.value)
                        }
                        required
                    />
                    <InputError message={errors[field('postcode')]} />
                </div>
            </div>
            <div className="grid gap-4 sm:grid-cols-2">
                <div className="grid gap-2">
                    <Label htmlFor={field('country')}>Country</Label>
                    <select
                        id={field('country')}
                        autoComplete="country"
                        value={value.country}
                        onChange={(event) =>
                            onChange('country', event.target.value)
                        }
                        className="h-9 rounded-md border border-input bg-transparent px-3 text-sm shadow-xs"
                    >
                        {(countries.length > 0 ? countries : ['GB']).map(
                            (country) => (
                                <option key={country} value={country}>
                                    {country}
                                </option>
                            ),
                        )}
                    </select>
                    <InputError message={errors[field('country')]} />
                </div>
                <div className="grid gap-2">
                    <Label htmlFor={field('phone')}>
                        Phone{' '}
                        <span className="text-muted-foreground">
                            (optional)
                        </span>
                    </Label>
                    <Input
                        id={field('phone')}
                        type="tel"
                        autoComplete="tel"
                        value={value.phone}
                        onChange={(event) =>
                            onChange('phone', event.target.value)
                        }
                    />
                </div>
            </div>
        </div>
    );
}

export default function CheckoutShow({
    shippingMethods,
    email,
    defaultAddress,
    countries,
}: CheckoutShowProps) {
    const { basket } = usePage<{
        basket: Basket | null;
        [key: string]: unknown;
    }>().props;

    const { data, setData, post, processing, errors } = useForm<{
        email: string;
        shipping_method_id: number | null;
        shipping_address: AddressForm;
        billing_same_as_shipping: boolean;
        billing_address: AddressForm;
        customer_note: string;
    }>({
        email: email ?? '',
        shipping_method_id: shippingMethods[0]?.id ?? null,
        shipping_address: { ...EMPTY_ADDRESS, ...defaultAddress },
        billing_same_as_shipping: true,
        billing_address: EMPTY_ADDRESS,
        customer_note: '',
    });

    return (
        <>
            <Seo title="Checkout" noindex />

            <div className="mx-auto w-full max-w-3xl px-4 py-10 sm:px-6">
                <h1 className="mb-8 text-2xl font-semibold tracking-tight">
                    Checkout
                </h1>

                <form
                    onSubmit={(event) => {
                        event.preventDefault();
                        post(store().url);
                    }}
                    className="grid gap-10"
                >
                    <section
                        aria-labelledby="contact-heading"
                        className="grid gap-4"
                    >
                        <h2
                            id="contact-heading"
                            className="text-lg font-semibold"
                        >
                            Contact
                        </h2>
                        <div className="grid gap-2">
                            <Label htmlFor="email">Email</Label>
                            <Input
                                id="email"
                                type="email"
                                autoComplete="email"
                                value={data.email}
                                onChange={(event) =>
                                    setData('email', event.target.value)
                                }
                                required
                            />
                            <InputError message={errors.email} />
                        </div>
                    </section>

                    <section
                        aria-labelledby="shipping-heading"
                        className="grid gap-4"
                    >
                        <h2
                            id="shipping-heading"
                            className="text-lg font-semibold"
                        >
                            Shipping address
                        </h2>
                        <AddressFields
                            prefix="shipping_address"
                            value={data.shipping_address}
                            errors={errors as Record<string, string>}
                            countries={countries}
                            onChange={(field, fieldValue) =>
                                setData('shipping_address', {
                                    ...data.shipping_address,
                                    [field]: fieldValue,
                                })
                            }
                        />
                        <label className="flex items-center gap-2 text-sm">
                            <Checkbox
                                checked={data.billing_same_as_shipping}
                                onCheckedChange={(checked) =>
                                    setData(
                                        'billing_same_as_shipping',
                                        checked === true,
                                    )
                                }
                            />
                            Billing address is the same as shipping
                        </label>
                    </section>

                    {!data.billing_same_as_shipping && (
                        <section
                            aria-labelledby="billing-heading"
                            className="grid gap-4"
                        >
                            <h2
                                id="billing-heading"
                                className="text-lg font-semibold"
                            >
                                Billing address
                            </h2>
                            <AddressFields
                                prefix="billing_address"
                                value={data.billing_address}
                                errors={errors as Record<string, string>}
                                countries={countries}
                                onChange={(field, fieldValue) =>
                                    setData('billing_address', {
                                        ...data.billing_address,
                                        [field]: fieldValue,
                                    })
                                }
                            />
                        </section>
                    )}

                    <section
                        aria-labelledby="method-heading"
                        className="grid gap-4"
                    >
                        <h2
                            id="method-heading"
                            className="text-lg font-semibold"
                        >
                            Delivery
                        </h2>
                        <fieldset className="grid gap-2">
                            <legend className="sr-only">
                                Choose a delivery method
                            </legend>
                            {shippingMethods.map((method) => (
                                <label
                                    key={method.id}
                                    className={`flex cursor-pointer items-center justify-between gap-4 rounded-lg border p-4 text-sm has-checked:border-primary ${
                                        data.shipping_method_id === method.id
                                            ? 'border-primary'
                                            : ''
                                    }`}
                                >
                                    <span className="flex items-center gap-3">
                                        <input
                                            type="radio"
                                            name="shipping_method_id"
                                            checked={
                                                data.shipping_method_id ===
                                                method.id
                                            }
                                            onChange={() =>
                                                setData(
                                                    'shipping_method_id',
                                                    method.id,
                                                )
                                            }
                                        />
                                        <span>
                                            <span className="font-medium">
                                                {method.name}
                                            </span>
                                            {method.description && (
                                                <span className="block text-muted-foreground">
                                                    {method.description}
                                                </span>
                                            )}
                                        </span>
                                    </span>
                                    <span className="font-semibold">
                                        {method.price}
                                    </span>
                                </label>
                            ))}
                            <InputError message={errors.shipping_method_id} />
                        </fieldset>
                    </section>

                    <section
                        aria-labelledby="summary-heading"
                        className="grid gap-4"
                    >
                        <h2
                            id="summary-heading"
                            className="text-lg font-semibold"
                        >
                            Order summary
                        </h2>
                        <div className="rounded-lg border p-4 text-sm">
                            {basket?.items.map((item) => (
                                <div
                                    key={item.id}
                                    className="flex justify-between gap-2 py-1"
                                >
                                    <span>
                                        {item.quantity} × {item.product.name}
                                        {item.variant.options
                                            ? ` (${item.variant.options})`
                                            : ''}
                                    </span>
                                    <span>{item.line_total}</span>
                                </div>
                            ))}
                            <div className="mt-2 flex justify-between border-t pt-2">
                                <span>Subtotal</span>
                                <span>{basket?.subtotal_formatted}</span>
                            </div>
                            {basket?.discount_formatted && (
                                <div className="flex justify-between text-muted-foreground">
                                    <span>
                                        Discount ({basket.discount_code})
                                    </span>
                                    <span>−{basket.discount_formatted}</span>
                                </div>
                            )}
                            <div className="flex justify-between font-semibold">
                                <span>Total before delivery</span>
                                <span>{basket?.total_formatted}</span>
                            </div>
                        </div>
                        <InputError
                            message={(errors as Record<string, string>).basket}
                        />
                    </section>

                    <Button
                        type="submit"
                        size="lg"
                        disabled={processing}
                        className="justify-self-start"
                    >
                        {processing && (
                            <Spinner className="size-4" aria-hidden="true" />
                        )}
                        {processing ? 'Placing order…' : 'Continue to payment'}
                    </Button>
                </form>
            </div>
        </>
    );
}
