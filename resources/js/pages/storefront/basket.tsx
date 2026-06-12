import { Link, usePage } from '@inertiajs/react';
import { ShoppingBag } from 'lucide-react';
import { Seo } from '@/components/seo';
import { BasketLine } from '@/components/storefront/basket-line';
import { Button } from '@/components/ui/button';
import { index as productsIndex } from '@/routes/products';
import type { Basket } from '@/types';

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
                            <p className="flex items-baseline gap-3 text-lg">
                                <span className="text-muted-foreground">
                                    Subtotal
                                </span>
                                <span className="font-semibold">
                                    {basket.subtotal_formatted}
                                </span>
                            </p>
                            <p className="text-sm text-muted-foreground">
                                Shipping is calculated at checkout.
                            </p>
                            {/* Checkout button is wired up in the checkout phase. */}
                            <Button size="lg" disabled>
                                Checkout (coming soon)
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
