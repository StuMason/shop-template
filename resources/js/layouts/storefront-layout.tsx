import { Link, usePage } from '@inertiajs/react';
import { UserRound } from 'lucide-react';
import { useEffect } from 'react';
import { toast } from 'sonner';
import { BasketDrawer } from '@/components/storefront/basket-drawer';
import { home, login } from '@/routes';
import { dashboard } from '@/routes/account';
import { index as productsIndex } from '@/routes/products';
import type { Basket, ShopInfo, User } from '@/types';

type StorefrontPageProps = {
    shop: ShopInfo;
    auth: { user: User | null; isStaff: boolean };
    basket: Basket | null;
    flash: { success: string | null; error: string | null };
    [key: string]: unknown;
};

/**
 * Public storefront chrome: skip link, header with nav and basket, footer,
 * and a polite live region for announcing basket changes to screen readers.
 */
export default function StorefrontLayout({
    children,
}: {
    children: React.ReactNode;
}) {
    const { shop, auth, basket, flash } = usePage<StorefrontPageProps>().props;
    const announcement = flash.success ?? flash.error ?? '';

    useEffect(() => {
        if (flash.success) {
            toast.success(flash.success);
        }

        if (flash.error) {
            toast.error(flash.error);
        }
    }, [flash]);

    return (
        <div className="flex min-h-screen flex-col bg-background text-foreground">
            <a
                href="#main"
                className="sr-only focus:not-sr-only focus:absolute focus:top-2 focus:left-2 focus:z-50 focus:rounded-md focus:bg-primary focus:px-3 focus:py-2 focus:text-primary-foreground"
            >
                Skip to content
            </a>

            <header className="sticky top-0 z-40 border-b bg-background/95 backdrop-blur supports-[backdrop-filter]:bg-background/80">
                <div className="mx-auto flex h-16 w-full max-w-7xl items-center justify-between gap-6 px-4 sm:px-6">
                    <Link
                        href={home()}
                        prefetch
                        className="text-lg font-semibold tracking-tight"
                    >
                        {shop.name}
                    </Link>

                    <nav
                        aria-label="Main navigation"
                        className="flex items-center gap-1 text-sm"
                    >
                        <Link
                            href={productsIndex()}
                            prefetch
                            cacheFor="30s"
                            className="rounded-md px-3 py-2 hover:bg-accent"
                        >
                            Shop
                        </Link>
                        <Link
                            href={auth.user ? dashboard() : login()}
                            className="rounded-md p-2 hover:bg-accent"
                            aria-label={auth.user ? 'My account' : 'Sign in'}
                        >
                            <UserRound className="size-5" aria-hidden="true" />
                        </Link>
                        <BasketDrawer basket={basket} />
                    </nav>
                </div>
            </header>

            <main id="main" className="flex-1">
                {children}
            </main>

            <footer className="border-t py-10">
                <div className="mx-auto flex w-full max-w-7xl flex-col gap-2 px-4 text-sm text-muted-foreground sm:px-6">
                    <p className="font-medium text-foreground">{shop.name}</p>
                    <p>{shop.tagline}</p>
                    <p>
                        <a
                            href={`mailto:${shop.contact_email}`}
                            className="hover:underline"
                        >
                            {shop.contact_email}
                        </a>
                    </p>
                </div>
            </footer>

            <div role="status" aria-live="polite" className="sr-only">
                {announcement}
            </div>
        </div>
    );
}
