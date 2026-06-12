import { Link } from '@inertiajs/react';
import { Seo } from '@/components/seo';
import { Pagination } from '@/components/storefront/pagination';
import { ProductGrid } from '@/components/storefront/product-grid';
import { show as categoryShow } from '@/routes/categories';
import type { CategorySummary, Paginated, ProductCard } from '@/types';

type CategoryShowProps = {
    category: {
        name: string;
        slug: string;
        description: string | null;
        meta_title: string | null;
        meta_description: string | null;
    };
    children: CategorySummary[];
    products: Paginated<ProductCard>;
};

export default function CategoryShow({
    category,
    children,
    products,
}: CategoryShowProps) {
    return (
        <>
            <Seo
                title={category.meta_title ?? category.name}
                description={
                    category.meta_description ??
                    category.description ??
                    undefined
                }
            />

            <div className="mx-auto w-full max-w-7xl px-4 py-10 sm:px-6">
                <header className="mb-8 flex flex-col gap-2">
                    <h1 className="text-2xl font-semibold tracking-tight">
                        {category.name}
                    </h1>
                    {category.description && (
                        <p className="max-w-2xl text-muted-foreground">
                            {category.description}
                        </p>
                    )}
                    {children.length > 0 && (
                        <ul className="mt-2 flex flex-wrap gap-2">
                            {children.map((child) => (
                                <li key={child.id}>
                                    <Link
                                        href={categoryShow(child.slug)}
                                        prefetch
                                        cacheFor="30s"
                                        className="inline-flex rounded-full border px-3 py-1.5 text-sm hover:bg-accent"
                                    >
                                        {child.name}
                                    </Link>
                                </li>
                            ))}
                        </ul>
                    )}
                </header>

                {products.data.length > 0 ? (
                    <div className="flex flex-col gap-10">
                        <ProductGrid products={products.data} />
                        <Pagination links={products.links} />
                    </div>
                ) : (
                    <p className="py-12 text-center text-muted-foreground">
                        No products in this category yet.
                    </p>
                )}
            </div>
        </>
    );
}
