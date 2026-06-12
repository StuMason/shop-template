import { ProductCard } from '@/components/storefront/product-card';
import { Skeleton } from '@/components/ui/skeleton';
import type { ProductCard as ProductCardData } from '@/types';

type ProductGridProps = {
    products: ProductCardData[];
    /** Number of cards rendered eagerly for LCP (the first row). */
    priorityCount?: number;
};

export function ProductGrid({ products, priorityCount = 4 }: ProductGridProps) {
    return (
        <div className="grid grid-cols-2 gap-x-4 gap-y-8 sm:grid-cols-3 lg:grid-cols-4">
            {products.map((product, index) => (
                <ProductCard
                    key={product.id}
                    product={product}
                    priority={index < priorityCount}
                />
            ))}
        </div>
    );
}

export function ProductGridSkeleton({ count = 4 }: { count?: number }) {
    return (
        <div className="grid grid-cols-2 gap-x-4 gap-y-8 sm:grid-cols-3 lg:grid-cols-4">
            {Array.from({ length: count }, (_, index) => (
                <div key={index} className="flex flex-col gap-2">
                    <Skeleton className="aspect-square rounded-xl" />
                    <Skeleton className="h-4 w-3/4" />
                    <Skeleton className="h-4 w-1/3" />
                </div>
            ))}
        </div>
    );
}
