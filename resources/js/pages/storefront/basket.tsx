import { Link, useForm, usePage } from '@inertiajs/react';
import { ShoppingBag, X } from 'lucide-react';
import InputError from '@/components/input-error';
import { Seo } from '@/components/seo';
import { BasketLine } from '@/components/storefront/basket-line';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    destroy as discountDestroy,
    store as discountStore,
} from '@/routes/cart/discount';
import { show as checkoutShow } from '@/routes/checkout';
import { index as productsIndex } from '@/routes/products';
import type { Basket } from '@/types';

function DiscountControl({ basket }: { basket: Basket }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        code: '',
    });
    const removeForm = useForm();

    if (basket.discount_code) {
        return (
            <div className="flex items-center gap-2 text-sm">
                <span className="text-muted-foreground">
                    Code{' '}
                    <span className="font-medium text-foreground">
                        {basket.discount_code}
                    </span>{' '}
                    applied (−{basket.discount_formatted})
                </span>
                <Button
                    variant="ghost"
                    size="icon"
                    className="size-7"
                    aria-label={`Remove code ${basket.discount_code}`}
                    disabled={removeForm.processing}
                    onClick={() =>
                        removeForm.delete(discountDestroy().url, {
                            preserveScroll: true,
                        })
                    }
                >
                    <X className="size-4" aria-hidden="true" />
                </Button>
            </div>
        );
    }

    return (
        <form
            onSubmit={(event) => {
                event.preventDefault();
                post(discountStore().url, {
                    preserveScroll: true,
                    onSuccess: () => reset(),
                });
            }}
            className="flex flex-col gap-1"
        >
            <div className="flex gap-2">
                <Input
                    value={data.code}
                    onChange={(event) =>
                        setData('code', event.target.value.toUpperCase())
                    }
                    placeholder="Discount code"
                    aria-label="Discount code"
                    className="w-44"
                />
                <Button
                    type="submit"
                    variant="secondary"
                    disabled={processing || data.code === ''}
                >
                    Apply
                </Button>
            </div>
            <InputError message={errors.code} />
        </form>
    );
}

export default function BasketPage() {
    const { basket } = usePage<{
        basket: Basket | null;
        [key: string]: unknown;
    }>().props;

    const hasItems = basket !== null && basket.items.length > 0;

    return (
        <>
            <Seo title="Your basket" noindex />

            <div className="mx-auto w-full max-w-3xl px-4 py-10 sm:px-6">
                <h1 className="mb-6 text-2xl font-semibold tracking-tight">
                    Your basket
                </h1>

                {hasItems ? (
                    <div className="flex flex-col gap-6">
                        <div className="divide-y rounded-xl border px-4">
                            {basket.items.map((item) => (
                                <BasketLine key={item.id} item={item} />
                            ))}
                        </div>

                        <div className="flex flex-col items-end gap-2">
                            <DiscountControl basket={basket} />
                            <p className="flex items-baseline gap-3">
                                <span className="text-muted-foreground">
                                    Subtotal
                                </span>
                                <span>{basket.subtotal_formatted}</span>
                            </p>
                            {basket.discount_formatted && (
                                <p className="flex items-baseline gap-3 text-sm text-muted-foreground">
                                    <span>Discount</span>
                                    <span>−{basket.discount_formatted}</span>
                                </p>
                            )}
                            <p className="flex items-baseline gap-3 text-lg">
                                <span className="text-muted-foreground">
                                    Total
                                </span>
                                <span className="font-semibold">
                                    {basket.total_formatted}
                                </span>
                            </p>
                            <p className="text-sm text-muted-foreground">
                                Shipping is calculated at checkout.
                            </p>
                            <Button size="lg" asChild>
                                <Link href={checkoutShow()}>Checkout</Link>
                            </Button>
                        </div>
                    </div>
                ) : (
                    <div className="flex flex-col items-center gap-4 rounded-xl border py-16 text-center">
                        <ShoppingBag
                            className="size-12 text-muted-foreground"
                            aria-hidden="true"
                        />
                        <p className="text-muted-foreground">
                            Your basket is empty.
                        </p>
                        <Button asChild>
                            <Link href={productsIndex()}>Browse products</Link>
                        </Button>
                    </div>
                )}
            </div>
        </>
    );
}
