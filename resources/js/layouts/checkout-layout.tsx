import { Link, usePage } from '@inertiajs/react';
import { useEffect } from 'react';
import { toast } from 'sonner';
import { home } from '@/routes';
import { show as basketShow } from '@/routes/cart';
import type { ShopInfo } from '@/types';

type CheckoutPageProps = {
    shop: ShopInfo;
    flash: { success: string | null; error: string | null };
    [key: string]: unknown;
};

/**
 * Distraction-free checkout chrome: logo, a way back to the basket, and
 * nothing else.
 */
export default function CheckoutLayout({
    children,
}: {
    children: React.ReactNode;
}) {
    const { shop, flash } = usePage<CheckoutPageProps>().props;

    useEffect(() => {
        if (flash.error) {
            toast.error(flash.error);
        }
    }, [flash]);

    return (
        <div className="flex min-h-screen flex-col bg-background text-foreground">
            <header className="border-b">
                <div className="mx-auto flex h-16 w-full max-w-3xl items-center justify-between px-4 sm:px-6">
                    <Link
                        href={home()}
                        className="text-lg font-semibold tracking-tight"
                    >
                        {shop.name}
                    </Link>
                    <Link
                        href={basketShow()}
                        className="text-sm text-muted-foreground hover:text-foreground hover:underline"
                    >
                        Back to basket
                    </Link>
                </div>
            </header>

            <main id="main" className="flex-1">
                {children}
            </main>

            <footer className="border-t py-6">
                <p className="mx-auto w-full max-w-3xl px-4 text-xs text-muted-foreground sm:px-6">
                    Payments are made directly from your bank account — you will
                    be asked to approve the payment in your banking app.
                </p>
            </footer>
        </div>
    );
}
