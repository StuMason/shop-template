import { Form, Head, Link } from '@inertiajs/react';
import { Pagination } from '@/components/storefront/pagination';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { index as ticketsIndex, show } from '@/routes/admin/tickets';
import type { Paginated } from '@/types';

type AdminTicketRow = {
    id: number;
    subject: string;
    status: string;
    customer: string;
    email: string;
    last_message_at: string;
};

export default function AdminSupportIndex({
    tickets,
    filters,
}: {
    tickets: Paginated<AdminTicketRow>;
    filters: { status: string };
}) {
    return (
        <>
            <Head title="Support" />
            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <div className="flex items-center justify-between">
                    <h1 className="text-xl font-semibold tracking-tight">
                        Support
                    </h1>
                    <Form action={ticketsIndex()} className="flex gap-2">
                        <select
                            name="status"
                            defaultValue={filters.status}
                            aria-label="Filter by status"
                            className="h-9 rounded-md border border-input bg-transparent px-3 text-sm shadow-xs"
                        >
                            <option value="">All</option>
                            <option value="open">Open</option>
                            <option value="closed">Closed</option>
                        </select>
                        <Button type="submit" variant="secondary">
                            Filter
                        </Button>
                    </Form>
                </div>

                {tickets.data.length > 0 ? (
                    <div className="flex flex-col gap-6">
                        <div className="overflow-hidden rounded-xl border">
                            <table className="w-full text-sm">
                                <thead className="bg-muted/50 text-left">
                                    <tr>
                                        <th className="px-4 py-3 font-medium">
                                            Subject
                                        </th>
                                        <th className="px-4 py-3 font-medium">
                                            Customer
                                        </th>
                                        <th className="px-4 py-3 font-medium">
                                            Last activity
                                        </th>
                                        <th className="px-4 py-3 font-medium">
                                            Status
                                        </th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y">
                                    {tickets.data.map((ticket) => (
                                        <tr
                                            key={ticket.id}
                                            className="hover:bg-muted/30"
                                        >
                                            <td className="px-4 py-3">
                                                <Link
                                                    href={show(ticket.id)}
                                                    className="font-medium hover:underline"
                                                >
                                                    {ticket.subject}
                                                </Link>
                                            </td>
                                            <td className="px-4 py-3 text-muted-foreground">
                                                {ticket.customer} ·{' '}
                                                {ticket.email}
                                            </td>
                                            <td className="px-4 py-3 text-muted-foreground">
                                                {ticket.last_message_at}
                                            </td>
                                            <td className="px-4 py-3">
                                                <Badge
                                                    variant={
                                                        ticket.status === 'open'
                                                            ? 'default'
                                                            : 'outline'
                                                    }
                                                >
                                                    {ticket.status}
                                                </Badge>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                        <Pagination links={tickets.links} />
                    </div>
                ) : (
                    <p className="py-12 text-center text-muted-foreground">
                        No tickets.
                    </p>
                )}
            </div>
        </>
    );
}
