import { Head, Link, usePage } from '@inertiajs/react';
import {
    Bell,
    LifeBuoy,
    MapPin,
    ReceiptText,
    Settings,
    Store,
} from 'lucide-react';
import { home } from '@/routes';
import { index as addresses } from '@/routes/account/addresses';
import { index as notifications } from '@/routes/account/notifications';
import { index as orders } from '@/routes/account/orders';
import { index as tickets } from '@/routes/account/tickets';
import { edit as profileEdit } from '@/routes/profile';
import type { User } from '@/types';

const LINKS = [
    {
        title: 'Orders',
        description: 'Track and review your orders.',
        href: orders(),
        icon: ReceiptText,
    },
    {
        title: 'Addresses',
        description: 'Manage your delivery addresses.',
        href: addresses(),
        icon: MapPin,
    },
    {
        title: 'Notifications',
        description: 'Order updates and messages.',
        href: notifications(),
        icon: Bell,
    },
    {
        title: 'Support',
        description: 'Get help with an order.',
        href: tickets(),
        icon: LifeBuoy,
    },
    {
        title: 'Settings',
        description: 'Profile, password and security.',
        href: profileEdit(),
        icon: Settings,
    },
    {
        title: 'Back to the shop',
        description: 'Keep browsing.',
        href: home(),
        icon: Store,
    },
];

export default function AccountDashboard() {
    const { auth } = usePage<{
        auth: { user: User };
        [key: string]: unknown;
    }>().props;

    return (
        <>
            <Head title="My account" />
            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <h1 className="text-xl font-semibold tracking-tight">
                    Hi {auth.user.name.split(' ')[0]}
                </h1>
                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    {LINKS.map((link) => (
                        <Link
                            key={link.title}
                            href={link.href}
                            className="group rounded-xl border p-4 transition-colors hover:bg-accent"
                        >
                            <link.icon
                                className="mb-3 size-6 text-muted-foreground group-hover:text-foreground"
                                aria-hidden="true"
                            />
                            <p className="font-medium">{link.title}</p>
                            <p className="text-sm text-muted-foreground">
                                {link.description}
                            </p>
                        </Link>
                    ))}
                </div>
            </div>
        </>
    );
}
