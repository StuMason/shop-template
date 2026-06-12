import { router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { store } from '@/routes/cart/items';
import type { Basket, ProductVariant } from '@/types';

type AddToBasketButtonProps = {
    variant: ProductVariant | undefined;
};

/**
 * Adds the selected variant to the basket. The basket badge updates
 * optimistically and rolls back automatically if the request fails.
 */
export function AddToBasketButton({ variant }: AddToBasketButtonProps) {
    const { basket } = usePage<{
        basket: Basket | null;
        [key: string]: unknown;
    }>().props;
    const [processing, setProcessing] = useState(false);

    function addToBasket() {
        if (!variant) {
            return;
        }

        const request = basket
            ? router.optimistic((props) => {
                  const current = (props as { basket: Basket }).basket;

                  return {
                      basket: {
                          ...current,
                          item_count: current.item_count + 1,
                      },
                  };
              })
            : router;

        request.post(
            store().url,
            { variant_id: variant.id, quantity: 1 },
            {
                preserveScroll: true,
                onStart: () => setProcessing(true),
                onFinish: () => setProcessing(false),
            },
        );
    }

    const label = !variant
        ? 'Unavailable'
        : variant.in_stock
          ? 'Add to basket'
          : 'Out of stock';

    return (
        <Button
            size="lg"
            className="w-full"
            disabled={!variant || !variant.in_stock || processing}
            onClick={addToBasket}
        >
            {processing && <Spinner className="size-4" aria-hidden="true" />}
            {processing ? 'Adding…' : label}
        </Button>
    );
}
