import { Link, router } from '@inertiajs/react';
import { Minus, Plus, Trash2 } from 'lucide-react';
import { ProductImage } from '@/components/storefront/product-image';
import { Button } from '@/components/ui/button';
import { destroy, update } from '@/routes/cart/items';
import { show as productShow } from '@/routes/products';
import type { BasketItem } from '@/types';

/**
 * One basket line with quantity stepper, shared by the drawer and the
 * basket page.
 */
export function BasketLine({ item }: { item: BasketItem }) {
    function setQuantity(quantity: number) {
        router.patch(
            update(item.id).url,
            { quantity },
            { preserveScroll: true },
        );
    }

    function remove() {
        router.delete(destroy(item.id).url, { preserveScroll: true });
    }

    return (
        <div className="flex gap-4 py-4">
            <Link
                href={productShow(item.product.slug)}
                className="block size-20 shrink-0 overflow-hidden rounded-lg border bg-muted"
                tabIndex={-1}
                aria-hidden="true"
            >
                <ProductImage image={item.product.image} sizes="80px" />
            </Link>

            <div className="flex flex-1 flex-col gap-1">
                <div className="flex items-start justify-between gap-2">
                    <div>
                        <Link
                            href={productShow(item.product.slug)}
                            className="text-sm font-medium hover:underline"
                        >
                            {item.product.name}
                        </Link>
                        {item.variant.options && (
                            <p className="text-xs text-muted-foreground">
                                {item.variant.options}
                            </p>
                        )}
                    </div>
                    <p className="text-sm font-semibold">{item.line_total}</p>
                </div>

                <div className="mt-auto flex items-center justify-between">
                    <div
                        className="flex items-center rounded-md border"
                        role="group"
                        aria-label={`Quantity for ${item.product.name}`}
                    >
                        <Button
                            variant="ghost"
                            size="icon"
                            className="size-8"
                            aria-label="Decrease quantity"
                            onClick={() => setQuantity(item.quantity - 1)}
                        >
                            <Minus className="size-3.5" aria-hidden="true" />
                        </Button>
                        <span
                            className="w-8 text-center text-sm tabular-nums"
                            aria-live="polite"
                        >
                            {item.quantity}
                        </span>
                        <Button
                            variant="ghost"
                            size="icon"
                            className="size-8"
                            aria-label="Increase quantity"
                            disabled={item.quantity >= item.max_quantity}
                            onClick={() => setQuantity(item.quantity + 1)}
                        >
                            <Plus className="size-3.5" aria-hidden="true" />
                        </Button>
                    </div>

                    <Button
                        variant="ghost"
                        size="icon"
                        className="size-8 text-muted-foreground"
                        aria-label={`Remove ${item.product.name} from basket`}
                        onClick={remove}
                    >
                        <Trash2 className="size-4" aria-hidden="true" />
                    </Button>
                </div>
            </div>
        </div>
    );
}
