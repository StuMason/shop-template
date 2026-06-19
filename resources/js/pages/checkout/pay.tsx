import { router } from '@inertiajs/react';
import { Coins, Landmark } from 'lucide-react';
import { lazy, Suspense, useState } from 'react';
import type { CryptoCheckout } from '@/components/crypto/usdc-checkout';
import { Seo } from '@/components/seo';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { cn } from '@/lib/utils';

// Code-split: the wallet stack (wagmi/RainbowKit) loads only when a human
// actually chooses to pay with crypto, never in the main or SSR bundle.
const UsdcCheckout = lazy(() => import('@/components/crypto/usdc-checkout'));

type OrderSummary = {
    number: string;
    email: string;
    subtotal: string;
    discount_total: string | null;
    discount_code: string | null;
    shipping_total: string;
    shipping_method: string | null;
    vat_total: string | null;
    total: string;
    shipping_address: Record<string, string | null>;
    items: {
        id: number;
        product_name: string;
        variant_name: string;
        quantity: number;
        line_total: string;
    }[];
};

export default function CheckoutPay({
    order,
    payUrl,
    crypto = null,
}: {
    order: OrderSummary;
    payUrl: string;
    crypto?: CryptoCheckout | null;
}) {
    const [redirecting, setRedirecting] = useState(false);
    const [method, setMethod] = useState<'bank' | 'usdc'>('bank');

    function pay() {
        setRedirecting(true);
        // Inertia::location on the server answers with the external bank URL.
        router.post(
            payUrl,
            {},
            {
                onError: () => setRedirecting(false),
            },
        );
    }

    return (
        <>
            <Seo title={`Pay for order ${order.number}`} noindex />

            <div className="mx-auto w-full max-w-2xl px-4 py-10 sm:px-6">
                <h1 className="mb-2 text-2xl font-semibold tracking-tight">
                    Review &amp; pay
                </h1>
                <p className="mb-8 text-muted-foreground">
                    Order {order.number} · confirmation goes to {order.email}
                </p>

                <div className="mb-8 rounded-xl border">
                    <div className="divide-y px-4 text-sm">
                        {order.items.map((item) => (
                            <div
                                key={item.id}
                                className="flex justify-between gap-2 py-3"
                            >
                                <span>
                                    {item.quantity} × {item.product_name}
                                    {item.variant_name !== 'Default'
                                        ? ` (${item.variant_name})`
                                        : ''}
                                </span>
                                <span>{item.line_total}</span>
                            </div>
                        ))}
                    </div>
                    <div className="space-y-1 border-t bg-muted/30 p-4 text-sm">
                        <div className="flex justify-between">
                            <span>Subtotal</span>
                            <span>{order.subtotal}</span>
                        </div>
                        {order.discount_total && (
                            <div className="flex justify-between text-muted-foreground">
                                <span>Discount ({order.discount_code})</span>
                                <span>−{order.discount_total}</span>
                            </div>
                        )}
                        {order.shipping_method && (
                            <div className="flex justify-between">
                                <span>{order.shipping_method}</span>
                                <span>{order.shipping_total}</span>
                            </div>
                        )}
                        <div className="flex justify-between pt-1 text-base font-semibold">
                            <span>Total</span>
                            <span>{order.total}</span>
                        </div>
                        {order.vat_total && (
                            <div className="flex justify-between text-xs text-muted-foreground">
                                <span>Includes VAT</span>
                                <span>{order.vat_total}</span>
                            </div>
                        )}
                    </div>
                </div>

                {order.shipping_address.line1 && (
                    <div className="mb-8 rounded-xl border p-4 text-sm">
                        <p className="font-medium">Delivering to</p>
                        <p className="text-muted-foreground">
                            {[
                                order.shipping_address.name,
                                order.shipping_address.line1,
                                order.shipping_address.city,
                                order.shipping_address.postcode,
                            ]
                                .filter(Boolean)
                                .join(', ')}
                        </p>
                    </div>
                )}

                {crypto && (
                    <div
                        className="mb-5 grid grid-cols-2 gap-3"
                        role="tablist"
                        aria-label="Payment method"
                    >
                        {(
                            [
                                {
                                    id: 'bank',
                                    label: 'Pay by bank',
                                    icon: Landmark,
                                },
                                {
                                    id: 'usdc',
                                    label: 'Pay with USDC',
                                    icon: Coins,
                                },
                            ] as const
                        ).map((option) => (
                            <button
                                key={option.id}
                                type="button"
                                role="tab"
                                aria-selected={method === option.id}
                                onClick={() => setMethod(option.id)}
                                className={cn(
                                    'flex items-center justify-center gap-2 rounded-lg border p-3 text-sm font-medium transition-colors',
                                    method === option.id
                                        ? 'border-primary bg-primary/5 text-foreground'
                                        : 'text-muted-foreground hover:bg-muted/50',
                                )}
                            >
                                <option.icon
                                    className="size-4"
                                    aria-hidden="true"
                                />
                                {option.label}
                            </button>
                        ))}
                    </div>
                )}

                {crypto && method === 'usdc' ? (
                    <Suspense
                        fallback={
                            <div className="flex justify-center py-6">
                                <Spinner
                                    className="size-5"
                                    aria-hidden="true"
                                />
                            </div>
                        }
                    >
                        <UsdcCheckout crypto={crypto} />
                    </Suspense>
                ) : (
                    <>
                        <Button
                            size="lg"
                            className="w-full"
                            disabled={redirecting}
                            onClick={pay}
                        >
                            {redirecting ? (
                                <>
                                    <Spinner
                                        className="size-4"
                                        aria-hidden="true"
                                    />
                                    Redirecting to your bank…
                                </>
                            ) : (
                                <>
                                    <Landmark
                                        className="size-4"
                                        aria-hidden="true"
                                    />
                                    Pay {order.total} with your bank
                                </>
                            )}
                        </Button>
                        <p className="mt-3 text-center text-xs text-muted-foreground">
                            You'll approve this payment securely in your own
                            banking app — no card details needed.
                        </p>
                    </>
                )}
            </div>
        </>
    );
}
