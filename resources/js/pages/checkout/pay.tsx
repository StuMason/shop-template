import { router } from '@inertiajs/react';
import { Landmark } from 'lucide-react';
import { useState } from 'react';
import { Seo } from '@/components/seo';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';

type OrderSummary = {
    number: string;
    email: string;
    subtotal: string;
    shipping_total: string;
    shipping_method: string;
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
}: {
    order: OrderSummary;
    payUrl: string;
}) {
    const [redirecting, setRedirecting] = useState(false);

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
                        <div className="flex justify-between">
                            <span>{order.shipping_method}</span>
                            <span>{order.shipping_total}</span>
                        </div>
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

                <Button
                    size="lg"
                    className="w-full"
                    disabled={redirecting}
                    onClick={pay}
                >
                    {redirecting ? (
                        <>
                            <Spinner className="size-4" aria-hidden="true" />
                            Redirecting to your bank…
                        </>
                    ) : (
                        <>
                            <Landmark className="size-4" aria-hidden="true" />
                            Pay {order.total} with your bank
                        </>
                    )}
                </Button>
                <p className="mt-3 text-center text-xs text-muted-foreground">
                    You'll approve this payment securely in your own banking app
                    — no card details needed.
                </p>
            </div>
        </>
    );
}
