import { Link } from '@inertiajs/react';
import { ShoppingBag } from 'lucide-react';
import { useState } from 'react';
import { BasketLine } from '@/components/storefront/basket-line';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetFooter,
    SheetHeader,
    SheetTitle,
    SheetTrigger,
} from '@/components/ui/sheet';
import { show as basketShow } from '@/routes/cart';
import type { Basket } from '@/types';

/**
 * Slide-over basket. Mounted once in the storefront header; the /basket
 * page covers the no-JS and shareable-URL cases.
 */
export function BasketDrawer({ basket }: { basket: Basket | null }) {
    const [open, setOpen] = useState(false);
    const itemCount = basket?.item_count ?? 0;

    return (
        <Sheet open={open} onOpenChange={setOpen}>
            <SheetTrigger asChild>
                <Button
                    variant="ghost"
                    size="icon"
                    className="relative"
                    aria-label={`Basket, ${itemCount} ${itemCount === 1 ? 'item' : 'items'}`}
                >
                    <ShoppingBag className="size-5" aria-hidden="true" />
                    {itemCount > 0 && (
                        <Badge className="absolute -top-1 -right-1 size-5 justify-center rounded-full p-0 text-[11px] tabular-nums">
                            {itemCount > 9 ? '9+' : itemCount}
                        </Badge>
                    )}
                </Button>
            </SheetTrigger>

            <SheetContent
                side="right"
                className="flex w-full flex-col sm:max-w-md"
            >
                <SheetHeader>
                    <SheetTitle>Your basket</SheetTitle>
                    <SheetDescription>
                        {itemCount > 0
                            ? `${itemCount} ${itemCount === 1 ? 'item' : 'items'}`
                            : 'Nothing here yet.'}
                    </SheetDescription>
                </SheetHeader>

                {basket && basket.items.length > 0 ? (
                    <>
                        <div className="flex-1 divide-y overflow-y-auto px-4">
                            {basket.items.map((item) => (
                                <BasketLine key={item.id} item={item} />
                            ))}
                        </div>
                        <SheetFooter className="border-t">
                            <div className="flex items-center justify-between text-sm">
                                <span>Subtotal</span>
                                <span className="font-semibold">
                                    {basket.subtotal_formatted}
                                </span>
                            </div>
                            <p className="text-xs text-muted-foreground">
                                Shipping is calculated at checkout.
                            </p>
                            <Button asChild onClick={() => setOpen(false)}>
                                <Link href={basketShow()}>
                                    View basket &amp; checkout
                                </Link>
                            </Button>
                        </SheetFooter>
                    </>
                ) : (
                    <div className="flex flex-1 flex-col items-center justify-center gap-3 p-6 text-center">
                        <ShoppingBag
                            className="size-10 text-muted-foreground"
                            aria-hidden="true"
                        />
                        <p className="text-sm text-muted-foreground">
                            Your basket is empty.
                        </p>
                        <Button
                            variant="outline"
                            onClick={() => setOpen(false)}
                        >
                            Keep browsing
                        </Button>
                    </div>
                )}
            </SheetContent>
        </Sheet>
    );
}
