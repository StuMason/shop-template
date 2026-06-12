import { Head, Link, useForm } from '@inertiajs/react';
import { Plus } from 'lucide-react';
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
import { show, store } from '@/routes/account/tickets';

type TicketRow = {
    id: number;
    subject: string;
    status: string;
    last_message_at: string;
};

function NewTicketDialog({
    orderNumbers,
    onClose,
}: {
    orderNumbers: string[];
    onClose: () => void;
}) {
    const { data, setData, post, processing, errors } = useForm({
        subject: '',
        body: '',
        order_number: '',
    });

    return (
        <DialogContent>
            <DialogHeader>
                <DialogTitle>New support ticket</DialogTitle>
                <DialogDescription>
                    Tell us what's up and we'll get back to you by email.
                </DialogDescription>
            </DialogHeader>
            <form
                onSubmit={(event) => {
                    event.preventDefault();
                    post(store().url, { onSuccess: onClose });
                }}
                className="flex flex-col gap-4"
            >
                <div className="grid gap-2">
                    <Label htmlFor="ticket-subject">Subject</Label>
                    <Input
                        id="ticket-subject"
                        value={data.subject}
                        onChange={(event) =>
                            setData('subject', event.target.value)
                        }
                        required
                    />
                    <InputError message={errors.subject} />
                </div>
                {orderNumbers.length > 0 && (
                    <div className="grid gap-2">
                        <Label htmlFor="ticket-order">
                            Related order{' '}
                            <span className="text-muted-foreground">
                                (optional)
                            </span>
                        </Label>
                        <select
                            id="ticket-order"
                            value={data.order_number}
                            onChange={(event) =>
                                setData('order_number', event.target.value)
                            }
                            className="h-9 rounded-md border border-input bg-transparent px-3 text-sm shadow-xs"
                        >
                            <option value="">No specific order</option>
                            {orderNumbers.map((number) => (
                                <option key={number} value={number}>
                                    {number}
                                </option>
                            ))}
                        </select>
                        <InputError message={errors.order_number} />
                    </div>
                )}
                <div className="grid gap-2">
                    <Label htmlFor="ticket-body">Message</Label>
                    <textarea
                        id="ticket-body"
                        value={data.body}
                        onChange={(event) =>
                            setData('body', event.target.value)
                        }
                        rows={5}
                        required
                        className="rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs"
                    />
                    <InputError message={errors.body} />
                </div>
                <DialogFooter>
                    <Button type="submit" disabled={processing}>
                        {processing ? 'Sending…' : 'Open ticket'}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    );
}

export default function AccountSupportIndex({
    tickets,
    orderNumbers,
}: {
    tickets: TicketRow[];
    orderNumbers: string[];
}) {
    const [open, setOpen] = useState(false);

    return (
        <>
            <Head title="Support" />
            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <div className="flex items-center justify-between">
                    <h1 className="text-xl font-semibold tracking-tight">
                        Support
                    </h1>
                    <Dialog open={open} onOpenChange={setOpen}>
                        <DialogTrigger asChild>
                            <Button>
                                <Plus className="size-4" aria-hidden="true" />
                                New ticket
                            </Button>
                        </DialogTrigger>
                        {open && (
                            <NewTicketDialog
                                orderNumbers={orderNumbers}
                                onClose={() => setOpen(false)}
                            />
                        )}
                    </Dialog>
                </div>

                {tickets.length > 0 ? (
                    <ul className="divide-y rounded-xl border">
                        {tickets.map((ticket) => (
                            <li key={ticket.id}>
                                <Link
                                    href={show(ticket.id)}
                                    className="flex items-center justify-between gap-4 px-4 py-3 text-sm hover:bg-accent/50"
                                >
                                    <span className="font-medium">
                                        {ticket.subject}
                                    </span>
                                    <span className="flex items-center gap-3">
                                        <span className="text-muted-foreground">
                                            {ticket.last_message_at}
                                        </span>
                                        <Badge
                                            variant={
                                                ticket.status === 'open'
                                                    ? 'default'
                                                    : 'outline'
                                            }
                                        >
                                            {ticket.status}
                                        </Badge>
                                    </span>
                                </Link>
                            </li>
                        ))}
                    </ul>
                ) : (
                    <p className="py-12 text-center text-muted-foreground">
                        No tickets yet. Need a hand? Open one.
                    </p>
                )}
            </div>
        </>
    );
}
