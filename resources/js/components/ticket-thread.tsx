import { useForm } from '@inertiajs/react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { cn } from '@/lib/utils';

export type TicketMessage = {
    id: number;
    body: string;
    author: string;
    is_staff_reply: boolean;
    created_at: string;
};

export function TicketThread({ messages }: { messages: TicketMessage[] }) {
    return (
        <ol className="flex flex-col gap-4">
            {messages.map((message) => (
                <li
                    key={message.id}
                    className={cn(
                        'max-w-xl rounded-xl border p-4 text-sm',
                        message.is_staff_reply
                            ? 'self-start bg-muted/40'
                            : 'self-end',
                    )}
                >
                    <p className="mb-1 flex items-baseline justify-between gap-4">
                        <span className="font-medium">{message.author}</span>
                        <span className="text-xs text-muted-foreground">
                            {message.created_at}
                        </span>
                    </p>
                    <p className="whitespace-pre-line">{message.body}</p>
                </li>
            ))}
        </ol>
    );
}

export function TicketReplyForm({
    action,
    disabled = false,
    draft = null,
}: {
    action: string;
    disabled?: boolean;
    /** AI-drafted reply the staff member can adopt with one click. */
    draft?: string | null;
}) {
    const { data, setData, post, processing, errors, reset } = useForm({
        body: '',
    });

    return (
        <form
            onSubmit={(event) => {
                event.preventDefault();
                post(action, {
                    preserveScroll: true,
                    onSuccess: () => reset(),
                });
            }}
            className="flex flex-col gap-3"
        >
            {draft && data.body === '' && (
                <div className="rounded-lg border border-dashed p-3 text-sm">
                    <div className="mb-2 flex items-center justify-between gap-2">
                        <span className="font-medium">
                            Suggested reply (AI-drafted from the order data)
                        </span>
                        <Button
                            type="button"
                            variant="secondary"
                            size="sm"
                            onClick={() => setData('body', draft)}
                        >
                            Use draft
                        </Button>
                    </div>
                    <p className="whitespace-pre-line text-muted-foreground">
                        {draft}
                    </p>
                </div>
            )}
            <div className="grid gap-2">
                <Label htmlFor="reply-body">Reply</Label>
                <textarea
                    id="reply-body"
                    value={data.body}
                    onChange={(event) => setData('body', event.target.value)}
                    rows={4}
                    required
                    disabled={disabled}
                    className="rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs"
                />
                <InputError message={errors.body} />
            </div>
            <Button
                type="submit"
                disabled={processing || disabled}
                className="self-end"
            >
                {processing ? 'Sending…' : 'Send reply'}
            </Button>
        </form>
    );
}
