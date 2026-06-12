import { Head, Link } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { index } from '@/routes/account/orders';

type OrderDetail = {
    number: string;
    status: string;
    placed_at: string;
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
};

function AddressBlock({
    title,
    address,
}: {
    title: string;
    address: Record<string, string | null>;
}) {
    return (
        <div className="rounded-xl border p-4 text-sm">
            <p className="mb-1 font-medium">{title}</p>
            <p className="text-muted-foreground">
                {[
                    address.name,
                    address.line1,
                    address.line2,
                    address.city,
                    address.county,
                    address.postcode,
                    address.country,
                ]
                    .filter(Boolean)
                    .join(', ')}
            </p>
        </div>
    );
}

export default function AccountOrderShow({ order }: { order: OrderDetail }) {
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
                        Order {order.number}
                    </h1>
                    <Badge variant="secondary">{order.status}</Badge>
                    <span className="text-sm text-muted-foreground">
                        Placed {order.placed_at}
                    </span>
                </div>

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
                        <tfoot className="bg-muted/30 text-sm">
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

                <div className="grid gap-4 sm:grid-cols-2">
                    <AddressBlock
                        title="Shipping address"
                        address={order.shipping_address}
                    />
                    <AddressBlock
                        title="Billing address"
                        address={order.billing_address}
                    />
                </div>
            </div>
        </>
    );
}
