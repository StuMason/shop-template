import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import { useState } from 'react';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { index, status as statusRoute } from '@/routes/admin/orders';
import { store as storeRefund } from '@/routes/admin/payments/refunds';

type AdminOrderDetail = {
    id: number;
    number: string;
    email: string;
    customer: string | null;
    status: string;
    placed_at: string;
    customer_note: string | null;
    subtotal: string;
    shipping_total: string;
    shipping_method: string;
    total: string;
    shipping_address: Record<string, string | null>;
    billing_address: Record<string, string | null>;
    items: {
        id: number;
        product_name: string;
        variant_name: string;
        sku: string;
        quantity: number;
        unit_price: string;
        line_total: string;
    }[];
    payments: {
        id: number;
        gateway: string;
        status: string;
        amount: number;
        refunded: number;
        created_at: string | null;
    }[];
    available_transitions: string[];
};

function RefundDialog({
    payment,
    onClose,
}: {
    payment: AdminOrderDetail['payments'][number];
    onClose: () => void;
}) {
    const refundable = payment.amount - payment.refunded;
    const { data, setData, post, processing, errors, transform } = useForm<{
        amount: string;
        reason: string;
    }>({
        amount: (refundable / 100).toFixed(2),
        reason: '',
    });

    return (
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Record refund</DialogTitle>
                <DialogDescription>
                    Execute the refund at your payment provider first — this
                    records it against the order.
                </DialogDescription>
            </DialogHeader>
            <form
                onSubmit={(event) => {
                    event.preventDefault();
                    transform((current) => ({
                        ...current,
                        amount: Math.round(
                            Number.parseFloat(current.amount || '0') * 100,
                        ),
                    }));
                    post(storeRefund(payment.id).url, {
                        preserveScroll: true,
                        onSuccess: onClose,
                    });
                }}
                className="flex flex-col gap-4"
            >
                <div className="grid gap-2">
                    <Label htmlFor="refund-amount">
                        Amount (£) — refundable: £
                        {(refundable / 100).toFixed(2)}
                    </Label>
                    <Input
                        id="refund-amount"
                        type="number"
                        min="0.01"
                        step="0.01"
                        value={data.amount}
                        onChange={(event) =>
                            setData('amount', event.target.value)
                        }
                        required
                    />
                    <InputError message={errors.amount} />
                </div>
                <div className="grid gap-2">
                    <Label htmlFor="refund-reason">Reason (optional)</Label>
                    <Input
                        id="refund-reason"
                        value={data.reason}
                        onChange={(event) =>
                            setData('reason', event.target.value)
                        }
                    />
                    <InputError message={errors.reason} />
                </div>
                <DialogFooter>
                    <Button type="submit" disabled={processing}>
                        {processing ? 'Recording…' : 'Record refund'}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    );
}

export default function AdminOrderShow({
    order,
}: {
    order: AdminOrderDetail;
}) {
    const statusForm = useForm({ status: '' });
    const [refundDialog, setRefundDialog] = useState<number | null>(null);

    function transition(status: string) {
        if (
            status === 'cancelled' &&
            !confirm(`Cancel order ${order.number} and restock its items?`)
        ) {
            return;
        }

        statusForm.transform(() => ({ status }));
        statusForm.patch(statusRoute(order.id).url, { preserveScroll: true });
    }

    return (
        <>
            <Head title={`Order ${order.number}`} />
            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <div className="flex flex-wrap items-center gap-3">
                    <Link
                        href={index()}
                        className="text-muted-foreground hover:text-foreground"
                        aria-label="Back to orders"
                    >
                        <ArrowLeft className="size-5" aria-hidden="true" />
                    </Link>
                    <h1 className="text-xl font-semibold tracking-tight">
                        {order.number}
                    </h1>
                    <Badge>{order.status}</Badge>
                    <span className="text-sm text-muted-foreground">
                        {order.placed_at} · {order.customer ?? 'Guest'} ·{' '}
                        {order.email}
                    </span>
                    <div className="ml-auto flex gap-2">
                        {order.available_transitions.map((target) => (
                            <Button
                                key={target}
                                size="sm"
                                variant={
                                    target === 'cancelled'
                                        ? 'destructive'
                                        : 'default'
                                }
                                disabled={statusForm.processing}
                                onClick={() => transition(target)}
                            >
                                Mark {target}
                            </Button>
                        ))}
                    </div>
                </div>
                <InputError message={statusForm.errors.status} />

                {order.customer_note && (
                    <div className="rounded-xl border border-amber-300 bg-amber-50 p-4 text-sm dark:border-amber-700 dark:bg-amber-950">
                        <p className="font-medium">Customer note</p>
                        <p>{order.customer_note}</p>
                    </div>
                )}

                <div className="overflow-hidden rounded-xl border">
                    <table className="w-full text-sm">
                        <thead className="bg-muted/50 text-left">
                            <tr>
                                <th className="px-4 py-3 font-medium">Item</th>
                                <th className="px-4 py-3 font-medium">SKU</th>
                                <th className="px-4 py-3 font-medium">Qty</th>
                                <th className="px-4 py-3 font-medium">
                                    Unit price
                                </th>
                                <th className="px-4 py-3 text-right font-medium">
                                    Total
                                </th>
                            </tr>
                        </thead>
                        <tbody className="divide-y">
                            {order.items.map((item) => (
                                <tr key={item.id}>
                                    <td className="px-4 py-3">
                                        {item.product_name}
                                        {item.variant_name !== 'Default' && (
                                            <span className="text-muted-foreground">
                                                {' '}
                                                ({item.variant_name})
                                            </span>
                                        )}
                                    </td>
                                    <td className="px-4 py-3 text-muted-foreground">
                                        {item.sku}
                                    </td>
                                    <td className="px-4 py-3">
                                        {item.quantity}
                                    </td>
                                    <td className="px-4 py-3">
                                        {item.unit_price}
                                    </td>
                                    <td className="px-4 py-3 text-right">
                                        {item.line_total}
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                        <tfoot className="bg-muted/30">
                            <tr>
                                <td colSpan={4} className="px-4 py-2">
                                    Subtotal
                                </td>
                                <td className="px-4 py-2 text-right">
                                    {order.subtotal}
                                </td>
                            </tr>
                            <tr>
                                <td colSpan={4} className="px-4 py-2">
                                    {order.shipping_method}
                                </td>
                                <td className="px-4 py-2 text-right">
                                    {order.shipping_total}
                                </td>
                            </tr>
                            <tr className="font-semibold">
                                <td colSpan={4} className="px-4 py-2">
                                    Total
                                </td>
                                <td className="px-4 py-2 text-right">
                                    {order.total}
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <section aria-labelledby="payments-heading">
                    <h2
                        id="payments-heading"
                        className="mb-3 text-base font-semibold"
                    >
                        Payments
                    </h2>
                    {order.payments.length > 0 ? (
                        <div className="overflow-hidden rounded-xl border">
                            <table className="w-full text-sm">
                                <thead className="bg-muted/50 text-left">
                                    <tr>
                                        <th className="px-4 py-3 font-medium">
                                            Gateway
                                        </th>
                                        <th className="px-4 py-3 font-medium">
                                            Status
                                        </th>
                                        <th className="px-4 py-3 font-medium">
                                            Amount
                                        </th>
                                        <th className="px-4 py-3 font-medium">
                                            Refunded
                                        </th>
                                        <th className="px-4 py-3 font-medium">
                                            Created
                                        </th>
                                        <th className="px-4 py-3">
                                            <span className="sr-only">
                                                Actions
                                            </span>
                                        </th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y">
                                    {order.payments.map((payment) => (
                                        <tr key={payment.id}>
                                            <td className="px-4 py-3">
                                                {payment.gateway}
                                            </td>
                                            <td className="px-4 py-3">
                                                <Badge variant="secondary">
                                                    {payment.status}
                                                </Badge>
                                            </td>
                                            <td className="px-4 py-3">
                                                £
                                                {(
                                                    payment.amount / 100
                                                ).toFixed(2)}
                                            </td>
                                            <td className="px-4 py-3">
                                                £
                                                {(
                                                    payment.refunded / 100
                                                ).toFixed(2)}
                                            </td>
                                            <td className="px-4 py-3 text-muted-foreground">
                                                {payment.created_at}
                                            </td>
                                            <td className="px-4 py-3 text-right">
                                                {payment.status ===
                                                    'succeeded' &&
                                                    payment.refunded <
                                                        payment.amount && (
                                                        <Dialog
                                                            open={
                                                                refundDialog ===
                                                                payment.id
                                                            }
                                                            onOpenChange={(
                                                                open,
                                                            ) =>
                                                                setRefundDialog(
                                                                    open
                                                                        ? payment.id
                                                                        : null,
                                                                )
                                                            }
                                                        >
                                                            <DialogTrigger
                                                                asChild
                                                            >
                                                                <Button
                                                                    variant="outline"
                                                                    size="sm"
                                                                >
                                                                    Record
                                                                    refund
                                                                </Button>
                                                            </DialogTrigger>
                                                            {refundDialog ===
                                                                payment.id && (
                                                                <RefundDialog
                                                                    payment={
                                                                        payment
                                                                    }
                                                                    onClose={() =>
                                                                        setRefundDialog(
                                                                            null,
                                                                        )
                                                                    }
                                                                />
                                                            )}
                                                        </Dialog>
                                                    )}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    ) : (
                        <p className="text-sm text-muted-foreground">
                            No payment attempts yet.
                        </p>
                    )}
                </section>

                <div className="grid gap-4 sm:grid-cols-2">
                    <div className="rounded-xl border p-4 text-sm">
                        <p className="mb-1 font-medium">Shipping address</p>
                        <p className="text-muted-foreground">
                            {[
                                order.shipping_address.name,
                                order.shipping_address.line1,
                                order.shipping_address.line2,
                                order.shipping_address.city,
                                order.shipping_address.postcode,
                                order.shipping_address.country,
                            ]
                                .filter(Boolean)
                                .join(', ')}
                        </p>
                    </div>
                    <div className="rounded-xl border p-4 text-sm">
                        <p className="mb-1 font-medium">Billing address</p>
                        <p className="text-muted-foreground">
                            {[
                                order.billing_address.name,
                                order.billing_address.line1,
                                order.billing_address.line2,
                                order.billing_address.city,
                                order.billing_address.postcode,
                                order.billing_address.country,
                            ]
                                .filter(Boolean)
                                .join(', ')}
                        </p>
                    </div>
                </div>
            </div>
        </>
    );
}
