import { Head, useForm } from '@inertiajs/react';
import { BellOff } from 'lucide-react';
import { Pagination } from '@/components/storefront/pagination';
import { Button } from '@/components/ui/button';
import { readAll } from '@/routes/account/notifications';
import { cn } from '@/lib/utils';
import type { Paginated } from '@/types';

type NotificationRow = {
    id: string;
    message: string;
    read: boolean;
    created_at: string;
};

export default function AccountNotifications({
    notifications,
    unreadCount,
}: {
    notifications: Paginated<NotificationRow>;
    unreadCount: number;
}) {
    const markAllForm = useForm();

    return (
        <>
            <Head title="Notifications" />
            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <div className="flex items-center justify-between">
                    <h1 className="text-xl font-semibold tracking-tight">
                        Notifications
                    </h1>
                    {unreadCount > 0 && (
                        <Button
                            variant="outline"
                            size="sm"
                            disabled={markAllForm.processing}
                            onClick={() =>
                                markAllForm.post(readAll().url, {
                                    preserveScroll: true,
                                })
                            }
                        >
                            Mark all read ({unreadCount})
                        </Button>
                    )}
                </div>

                {notifications.data.length > 0 ? (
                    <div className="flex flex-col gap-6">
                        <ul className="divide-y rounded-xl border">
                            {notifications.data.map((notification) => (
                                <li
                                    key={notification.id}
                                    className={cn(
                                        'flex items-start justify-between gap-4 px-4 py-3 text-sm',
                                        !notification.read && 'bg-accent/40',
                                    )}
                                >
                                    <p>
                                        {!notification.read && (
                                            <span className="mr-2 inline-block size-2 rounded-full bg-primary align-middle">
                                                <span className="sr-only">
                                                    Unread:
                                                </span>
                                            </span>
                                        )}
                                        {notification.message}
                                    </p>
                                    <span className="shrink-0 text-muted-foreground">
                                        {notification.created_at}
                                    </span>
                                </li>
                            ))}
                        </ul>
                        <Pagination links={notifications.links} />
                    </div>
                ) : (
                    <div className="flex flex-col items-center gap-3 py-16 text-center text-muted-foreground">
                        <BellOff className="size-10" aria-hidden="true" />
                        <p>Nothing here yet — order updates will appear.</p>
                    </div>
                )}
            </div>
        </>
    );
}
