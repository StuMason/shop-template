import { Link } from '@inertiajs/react';
import { ProductImage } from '@/components/storefront/product-image';
import { show } from '@/routes/products';
import type { ProductCard as ProductCardData } from '@/types';

type ProductCardProps = {
    product: ProductCardData;
    priority?: boolean;
};

/**
 * Product grid card. The title link is stretched across the whole card so
 * the card has exactly one interactive element.
 */
export function ProductCard({ product, priority = false }: ProductCardProps) {
    return (
        <article className="group relative flex flex-col gap-2">
            <div className="aspect-square overflow-hidden rounded-xl border bg-muted">
                <ProductImage
                    image={product.image}
                    priority={priority}
                    className="transition-transform duration-300 group-hover:scale-105"
                />
            </div>
            <div className="flex flex-col gap-0.5">
                <h3 className="text-sm font-medium">
                    <Link
                        href={show(product.slug)}
                        prefetch
                        cacheFor="30s"
                        className="after:absolute after:inset-0 after:rounded-xl"
                    >
                        {product.name}
                    </Link>
                </h3>
                <p className="flex items-baseline gap-2 text-sm">
                    <span className="font-semibold">{product.price}</span>
                    {product.compare_at_price && (
                        <s className="text-muted-foreground">
                            {product.compare_at_price}
                        </s>
                    )}
                    {!product.in_stock && (
                        <span className="text-muted-foreground">
                            Out of stock
                        </span>
                    )}
                </p>
            </div>
        </article>
    );
}
