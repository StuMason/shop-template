import { Link } from '@inertiajs/react';
import { CircleAlert, CircleCheck, Clock } from 'lucide-react';
import { Seo } from '@/components/seo';
import { Button } from '@/components/ui/button';
import { home } from '@/routes';
import { index as productsIndex } from '@/routes/products';

type OrderSummary = {
    number: string;
    email: string;
    status: string;
    subtotal: string;
    shipping_total: string;
    shipping_method: string;
    vat_total: string | null;
    vat_number: string | null;
    total: string;
    items: {
        id: number;
        product_name: string;
        variant_name: string;
        quantity: number;
        line_total: string;
    }[];
};

export default function CheckoutConfirmation({
    order,
    paymentStatus,
}: {
    order: OrderSummary;
    paymentStatus: string | null;
}) {
    const isPaid = ['paid', 'processing', 'shipped', 'delivered'].includes(
        order.status,
    );
    const isFailed =
        paymentStatus === 'failed' || paymentStatus === 'abandoned';

    return (
        <>
            <Seo title={`Order ${order.number}`} noindex />

            <div className="mx-auto w-full max-w-2xl px-4 py-10 text-center sm:px-6">
                {isPaid ? (
                    <CircleCheck
                        className="mx-auto size-14 text-green-600"
                        aria-hidden="true"
                    />
                ) : isFailed ? (
                    <CircleAlert
                        className="mx-auto size-14 text-destructive"
                        aria-hidden="true"
                    />
                ) : (
                    <Clock
                        className="mx-auto size-14 text-muted-foreground"
                        aria-hidden="true"
                    />
                )}

                <h1 className="mt-4 text-2xl font-semibold tracking-tight">
                    {isPaid
                        ? 'Thanks — your order is confirmed!'
                        : isFailed
                          ? 'Payment didn’t complete'
                          : 'Waiting for your payment…'}
                </h1>
                <p className="mt-2 text-muted-foreground">
                    {isPaid
                        ? `A confirmation for order ${order.number} is on its way to ${order.email}.`
                        : isFailed
                          ? `Order ${order.number} hasn't been paid. You can try again from the link in your basket, or contact us.`
                          : `We're waiting for your bank to confirm payment for order ${order.number}. This page will be right here when it does.`}
                </p>

                <div className="mt-8 rounded-xl border text-left">
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
                                <span>
                                    Includes VAT
                                    {order.vat_number
                                        ? ` (VAT No. ${order.vat_number})`
                                        : ''}
                                </span>
                                <span>{order.vat_total}</span>
                            </div>
                        )}
                    </div>
                </div>

                <div className="mt-8 flex justify-center gap-3">
                    <Button asChild variant="outline">
                        <Link href={home()}>Back home</Link>
                    </Button>
                    <Button asChild>
                        <Link href={productsIndex()}>Keep shopping</Link>
                    </Button>
                </div>
            </div>
        </>
    );
}
