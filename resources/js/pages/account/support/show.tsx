import { Head, Link } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import { TicketReplyForm, TicketThread } from '@/components/ticket-thread';
import type { TicketMessage } from '@/components/ticket-thread';
import { Badge } from '@/components/ui/badge';
import { index, reply } from '@/routes/account/tickets';

type TicketDetail = {
    id: number;
    subject: string;
    status: string;
    order_number: string | null;
    messages: TicketMessage[];
};

export default function AccountSupportShow({
    ticket,
}: {
    ticket: TicketDetail;
}) {
    return (
        <>
            <Head title={ticket.subject} />
            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <div className="flex flex-wrap items-center gap-3">
                    <Link
                        href={index()}
                        className="text-muted-foreground hover:text-foreground"
                        aria-label="Back to support"
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
                    {ticket.order_number && (
                        <span className="text-sm text-muted-foreground">
                            Order {ticket.order_number}
                        </span>
                    )}
                </div>

                <TicketThread messages={ticket.messages} />

                <div className="max-w-xl">
                    <TicketReplyForm action={reply(ticket.id).url} />
                </div>
            </div>
        </>
    );
}
