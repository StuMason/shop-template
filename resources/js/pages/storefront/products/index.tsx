import { Form } from '@inertiajs/react';
import { Seo } from '@/components/seo';
import { Pagination } from '@/components/storefront/pagination';
import { ProductGrid } from '@/components/storefront/product-grid';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { index as productsIndex } from '@/routes/products';
import type { CategorySummary, Paginated, ProductCard } from '@/types';

type ProductsIndexProps = {
    products: Paginated<ProductCard>;
    categories: CategorySummary[];
    filters: { q: string; category: string; sort: string };
};

const SORT_OPTIONS = [
    { value: 'newest', label: 'Newest' },
    { value: 'price_asc', label: 'Price: low to high' },
    { value: 'price_desc', label: 'Price: high to low' },
];

export default function ProductsIndex({
    products,
    categories,
    filters,
}: ProductsIndexProps) {
    const isFiltered = filters.q !== '' || filters.category !== '';

    return (
        <>
            <Seo
                title={filters.q ? `Search: ${filters.q}` : 'All products'}
                description="Browse the full product range."
                canonical={productsIndex.url()}
                noindex={filters.q !== ''}
            />

            <div className="mx-auto w-full max-w-7xl px-4 py-10 sm:px-6">
                <h1 className="mb-6 text-2xl font-semibold tracking-tight">
                    {filters.q
                        ? `Results for “${filters.q}”`
                        : 'All products'}
                </h1>

                {/* Filters submit as a plain GET form: crawlable, zero JS state. */}
                <Form
                    action={productsIndex()}
                    className="mb-8 flex flex-wrap items-end gap-4"
                >
                    <div className="flex flex-col gap-1.5">
                        <Label htmlFor="search">Search</Label>
                        <Input
                            id="search"
                            type="search"
                            name="q"
                            defaultValue={filters.q}
                            placeholder="Search products…"
                            className="w-56"
                        />
                    </div>
                    <fieldset className="flex flex-col gap-1.5">
                        <legend className="sr-only">Filter and sort</legend>
                        <Label htmlFor="category">Category</Label>
                        <select
                            id="category"
                            name="category"
                            defaultValue={filters.category}
                            className="h-9 rounded-md border border-input bg-transparent px-3 text-sm shadow-xs"
                        >
                            <option value="">All categories</option>
                            {categories.map((category) => (
                                <option
                                    key={category.id}
                                    value={category.slug}
                                >
                                    {category.name}
                                </option>
                            ))}
                        </select>
                    </fieldset>
                    <div className="flex flex-col gap-1.5">
                        <Label htmlFor="sort">Sort by</Label>
                        <select
                            id="sort"
                            name="sort"
                            defaultValue={filters.sort}
                            className="h-9 rounded-md border border-input bg-transparent px-3 text-sm shadow-xs"
                        >
                            {SORT_OPTIONS.map((option) => (
                                <option key={option.value} value={option.value}>
                                    {option.label}
                                </option>
                            ))}
                        </select>
                    </div>
                    <Button type="submit" variant="secondary">
                        Apply
                    </Button>
                </Form>

                {products.data.length > 0 ? (
                    <div className="flex flex-col gap-10">
                        <ProductGrid products={products.data} />
                        <Pagination links={products.links} />
                    </div>
                ) : (
                    <p className="py-12 text-center text-muted-foreground">
                        {isFiltered
                            ? 'Nothing matches those filters. Try widening your search.'
                            : 'No products yet.'}
                    </p>
                )}
            </div>
        </>
    );
}
