import { Form, Head, Link } from '@inertiajs/react';
import { Pagination } from '@/components/storefront/pagination';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { index as ordersIndex, show } from '@/routes/admin/orders';
import type { Paginated } from '@/types';

type AdminOrderRow = {
    id: number;
    number: string;
    email: string;
    status: string;
    total: string;
    placed_at: string;
};

type AdminOrdersIndexProps = {
    orders: Paginated<AdminOrderRow>;
    filters: { status: string; q: string };
    statuses: string[];
};

export default function AdminOrdersIndex({
    orders,
    filters,
    statuses,
}: AdminOrdersIndexProps) {
    return (
        <>
            <Head title="Orders" />
            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <div className="flex flex-wrap items-center justify-between gap-4">
                    <h1 className="text-xl font-semibold tracking-tight">
                        Orders
                    </h1>
                    <Form
                        action={ordersIndex()}
                        className="flex items-end gap-2"
                    >
                        <Input
                            type="search"
                            name="q"
                            defaultValue={filters.q}
                            placeholder="Order number or email…"
                            aria-label="Search orders"
                            className="w-64"
                        />
                        <select
                            name="status"
                            defaultValue={filters.status}
                            aria-label="Filter by status"
                            className="h-9 rounded-md border border-input bg-transparent px-3 text-sm shadow-xs"
                        >
                            <option value="">All statuses</option>
                            {statuses.map((status) => (
                                <option key={status} value={status}>
                                    {status}
                                </option>
                            ))}
                        </select>
                        <Button type="submit" variant="secondary">
                            Filter
                        </Button>
                    </Form>
                </div>

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
                                            Customer
                                        </th>
                                        <th className="px-4 py-3 font-medium">
                                            Placed
                                        </th>
                                        <th className="px-4 py-3 font-medium">
                                            Status
                                        </th>
                                        <th className="px-4 py-3 text-right font-medium">
                                            Total
                                        </th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y">
                                    {orders.data.map((order) => (
                                        <tr
                                            key={order.id}
                                            className="hover:bg-muted/30"
                                        >
                                            <td className="px-4 py-3">
                                                <Link
                                                    href={show(order.id)}
                                                    className="font-medium hover:underline"
                                                >
                                                    {order.number}
                                                </Link>
                                            </td>
                                            <td className="px-4 py-3 text-muted-foreground">
                                                {order.email}
                                            </td>
                                            <td className="px-4 py-3 text-muted-foreground">
                                                {order.placed_at}
                                            </td>
                                            <td className="px-4 py-3">
                                                <Badge variant="secondary">
                                                    {order.status}
                                                </Badge>
                                            </td>
                                            <td className="px-4 py-3 text-right">
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
                        No orders match.
                    </p>
                )}
            </div>
        </>
    );
}
