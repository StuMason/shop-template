import { Head, Link } from '@inertiajs/react';
import { Pagination } from '@/components/storefront/pagination';
import { Badge } from '@/components/ui/badge';
import { show } from '@/routes/account/orders';
import type { Paginated } from '@/types';

type OrderRow = {
    number: string;
    status: string;
    total: string;
    placed_at: string;
};

export default function AccountOrdersIndex({
    orders,
}: {
    orders: Paginated<OrderRow>;
}) {
    return (
        <>
            <Head title="Your orders" />
            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <h1 className="text-xl font-semibold tracking-tight">
                    Your orders
                </h1>

                {orders.data.length > 0 ? (
                    <div className="flex flex-col gap-6">
                        <div className="overflow-hidden rounded-xl border">
                            <table className="w-full text-sm">
                                <thead className="bg-muted/50 text-left">
                                    <tr>
                                        <th className="px-4 py-3 font-medium">
                                            Order
                                        </th>
                                        <th className="px-4 py-3 font-medium">
                                            Placed
                                        </th>
                                        <th className="px-4 py-3 font-medium">
                                            Status
                                        </th>
                                        <th className="px-4 py-3 font-medium">
                                            Total
                                        </th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y">
                                    {orders.data.map((order) => (
                                        <tr
                                            key={order.number}
                                            className="hover:bg-muted/30"
                                        >
                                            <td className="px-4 py-3">
                                                <Link
                                                    href={show(order.number)}
                                                    className="font-medium hover:underline"
                                                >
                                                    {order.number}
                                                </Link>
                                            </td>
                                            <td className="px-4 py-3 text-muted-foreground">
                                                {order.placed_at}
                                            </td>
                                            <td className="px-4 py-3">
                                                <Badge variant="secondary">
                                                    {order.status}
                                                </Badge>
                                            </td>
                                            <td className="px-4 py-3">
                                                {order.total}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                        <Pagination links={orders.links} />
                    </div>
                ) : (
                    <p className="py-12 text-center text-muted-foreground">
                        You haven't placed any orders yet.
                    </p>
                )}
            </div>
        </>
    );
}
