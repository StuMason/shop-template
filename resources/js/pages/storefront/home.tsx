import { Link, usePage } from '@inertiajs/react';
import { Seo } from '@/components/seo';
import { ProductGrid } from '@/components/storefront/product-grid';
import { Button } from '@/components/ui/button';
import { show as categoryShow } from '@/routes/categories';
import { index as productsIndex } from '@/routes/products';
import type { CategorySummary, ProductCard, ShopInfo } from '@/types';

type HomeProps = {
    latestProducts: ProductCard[];
    categories: CategorySummary[];
};

export default function Home({ latestProducts, categories }: HomeProps) {
    const { shop } = usePage<{ shop: ShopInfo; [key: string]: unknown }>()
        .props;

    return (
        <>
            <Seo title={shop.name} description={shop.tagline} />

            <section className="border-b">
                <div className="mx-auto flex w-full max-w-7xl flex-col items-start gap-4 px-4 py-16 sm:px-6 sm:py-24">
                    <h1 className="max-w-2xl text-4xl font-semibold tracking-tight text-balance sm:text-5xl">
                        {shop.tagline}
                    </h1>
                    <Button asChild size="lg" className="mt-2">
                        <Link href={productsIndex()} prefetch cacheFor="30s">
                            Shop all products
                        </Link>
                    </Button>
                </div>
            </section>

            {categories.length > 0 && (
                <section
                    aria-labelledby="categories-heading"
                    className="mx-auto w-full max-w-7xl px-4 py-12 sm:px-6"
                >
                    <h2
                        id="categories-heading"
                        className="mb-6 text-xl font-semibold tracking-tight"
                    >
                        Browse by category
                    </h2>
                    <ul className="flex flex-wrap gap-2">
                        {categories.map((category) => (
                            <li key={category.id}>
                                <Link
                                    href={categoryShow(category.slug)}
                                    prefetch
                                    cacheFor="30s"
                                    className="inline-flex rounded-full border px-4 py-2 text-sm hover:bg-accent"
                                >
                                    {category.name}
                                </Link>
                            </li>
                        ))}
                    </ul>
                </section>
            )}

            <section
                aria-labelledby="latest-heading"
                className="mx-auto w-full max-w-7xl px-4 pt-4 pb-16 sm:px-6"
            >
                <h2
                    id="latest-heading"
                    className="mb-6 text-xl font-semibold tracking-tight"
                >
                    Latest products
                </h2>
                {latestProducts.length > 0 ? (
                    <ProductGrid products={latestProducts} />
                ) : (
                    <p className="text-muted-foreground">
                        No products yet — add some in the admin area.
                    </p>
                )}
            </section>
        </>
    );
}
