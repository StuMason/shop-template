import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import {
    TicketReplyForm,
    TicketThread,
    type TicketMessage,
} from '@/components/ticket-thread';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { show as orderShow } from '@/routes/admin/orders';
import { index, reply, status as statusRoute } from '@/routes/admin/tickets';

type AdminTicketDetail = {
    id: number;
    subject: string;
    status: string;
    customer: string;
    email: string;
    order_number: string | null;
    order_id: number | null;
    messages: TicketMessage[];
};

export default function AdminSupportShow({
    ticket,
}: {
    ticket: AdminTicketDetail;
}) {
    const statusForm = useForm();
    const nextStatus = ticket.status === 'open' ? 'closed' : 'open';

    return (
        <>
            <Head title={ticket.subject} />
            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <div className="flex flex-wrap items-center gap-3">
                    <Link
                        href={index()}
                        className="text-muted-foreground hover:text-foreground"
                        aria-label="Back to support queue"
                    >
                        <ArrowLeft className="size-5" aria-hidden="true" />
                    </Link>
                    <h1 className="text-xl font-semibold tracking-tight">
                        {ticket.subject}
                    </h1>
                    <Badge
                        variant={
                            ticket.status === 'open' ? 'default' : 'outline'
                        }
                    >
                        {ticket.status}
                    </Badge>
                    <span className="text-sm text-muted-foreground">
                        {ticket.customer} · {ticket.email}
                    </span>
                    {ticket.order_id && (
                        <Link
                            href={orderShow(ticket.order_id)}
                            className="text-sm underline"
                        >
                            Order {ticket.order_number}
                        </Link>
                    )}
                    <Button
                        variant="outline"
                        size="sm"
                        className="ml-auto"
                        disabled={statusForm.processing}
                        onClick={() => {
                            statusForm.transform(() => ({
                                status: nextStatus,
                            }));
                            statusForm.patch(statusRoute(ticket.id).url, {
                                preserveScroll: true,
                            });
                        }}
                    >
                        {ticket.status === 'open'
                            ? 'Close ticket'
                            : 'Reopen ticket'}
                    </Button>
                </div>

                <TicketThread messages={ticket.messages} />

                <div className="max-w-xl">
                    <TicketReplyForm action={reply(ticket.id).url} />
                </div>
            </div>
        </>
    );
}
