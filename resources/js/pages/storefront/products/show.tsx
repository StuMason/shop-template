import { Link, usePage, WhenVisible } from '@inertiajs/react';
import { useMemo, useState } from 'react';
import { Seo } from '@/components/seo';
import { AddToBasketButton } from '@/components/storefront/add-to-basket-button';
import {
    ProductGrid,
    ProductGridSkeleton,
} from '@/components/storefront/product-grid';
import { ProductImage } from '@/components/storefront/product-image';
import { cn } from '@/lib/utils';
import { show as categoryShow } from '@/routes/categories';
import type {
    ProductCard,
    ProductDetail,
    ProductVariant,
    ShopInfo,
} from '@/types';

type ProductShowProps = {
    product: ProductDetail;
    relatedProducts?: ProductCard[];
};

export default function ProductShow({
    product,
    relatedProducts,
}: ProductShowProps) {
    const { shop } = usePage<{ shop: ShopInfo; [key: string]: unknown }>()
        .props;

    const defaultVariant =
        product.variants.find((variant) => variant.is_default) ??
        product.variants[0];

    const [selectedValues, setSelectedValues] = useState<
        Record<number, number>
    >(() => {
        const initial: Record<number, number> = {};
        product.options.forEach((option) => {
            const valueId = option.values.find((value) =>
                defaultVariant?.option_value_ids.includes(value.id),
            )?.id;

            if (valueId !== undefined) {
                initial[option.id] = valueId;
            }
        });

        return initial;
    });

    const selectedVariant = useMemo<ProductVariant | undefined>(() => {
        if (product.options.length === 0) {
            return defaultVariant;
        }

        const chosen = Object.values(selectedValues);

        return product.variants.find(
            (variant) =>
                chosen.length === product.options.length &&
                chosen.every((id) => variant.option_value_ids.includes(id)),
        );
    }, [product, selectedValues, defaultVariant]);

    const [activeImage, setActiveImage] = useState(0);

    const jsonLd = useMemo(() => {
        const offers = product.variants.map((variant) => ({
            '@type': 'Offer',
            sku: variant.sku,
            price: variant.price_amount,
            priceCurrency: shop.currency,
            availability: variant.in_stock
                ? 'https://schema.org/InStock'
                : 'https://schema.org/OutOfStock',
        }));

        return [
            {
                '@context': 'https://schema.org',
                '@type': 'Product',
                name: product.name,
                description: product.meta_description ?? product.description,
                image: product.images.map((image) => image.src),
                sku: defaultVariant?.sku,
                offers,
                /* AggregateRating: only emit once real reviews exist. */
            },
            {
                '@context': 'https://schema.org',
                '@type': 'BreadcrumbList',
                itemListElement: [
                    ...product.categories.map((category, index) => ({
                        '@type': 'ListItem',
                        position: index + 1,
                        name: category.name,
                        item: categoryShow.url(category.slug),
                    })),
                    {
                        '@type': 'ListItem',
                        position: product.categories.length + 1,
                        name: product.name,
                    },
                ],
            },
        ];
    }, [product, shop, defaultVariant]);

    return (
        <>
            <Seo
                title={product.meta_title ?? product.name}
                description={
                    product.meta_description ??
                    product.description?.slice(0, 160)
                }
                image={product.images[0]?.src}
                type="product"
                jsonLd={jsonLd}
            />

            <div className="mx-auto w-full max-w-7xl px-4 py-10 sm:px-6">
                {product.categories.length > 0 && (
                    <nav aria-label="Breadcrumb" className="mb-6 text-sm">
                        <ol className="flex flex-wrap items-center gap-1 text-muted-foreground">
                            {product.categories.map((category) => (
                                <li
                                    key={category.slug}
                                    className="after:mx-1 after:content-['/'] last:after:content-none"
                                >
                                    <Link
                                        href={categoryShow(category.slug)}
                                        className="hover:text-foreground hover:underline"
                                    >
                                        {category.name}
                                    </Link>
                                </li>
                            ))}
                            <li aria-current="page" className="text-foreground">
                                {product.name}
                            </li>
                        </ol>
                    </nav>
                )}

                <div className="grid gap-10 lg:grid-cols-2">
                    {/* Gallery */}
                    <div className="flex flex-col gap-3">
                        <div className="aspect-square overflow-hidden rounded-xl border bg-muted">
                            <ProductImage
                                image={product.images[activeImage] ?? null}
                                priority
                                sizes="(min-width: 1024px) 50vw, 100vw"
                            />
                        </div>
                        {product.images.length > 1 && (
                            <ul className="flex gap-2" role="list">
                                {product.images.map((image, index) => (
                                    <li key={image.id}>
                                        <button
                                            type="button"
                                            onClick={() =>
                                                setActiveImage(index)
                                            }
                                            aria-label={`View image ${index + 1}`}
                                            aria-pressed={index === activeImage}
                                            className={cn(
                                                'block size-16 overflow-hidden rounded-lg border',
                                                index === activeImage &&
                                                    'ring-2 ring-ring',
                                            )}
                                        >
                                            <ProductImage
                                                image={image}
                                                sizes="64px"
                                            />
                                        </button>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </div>

                    {/* Details */}
                    <div className="flex flex-col gap-6">
                        <div>
                            <h1 className="text-3xl font-semibold tracking-tight">
                                {product.name}
                            </h1>
                            <p className="mt-2 flex items-baseline gap-3 text-2xl">
                                <span className="font-semibold">
                                    {selectedVariant?.price ?? '—'}
                                </span>
                                {selectedVariant?.compare_at_price && (
                                    <s className="text-lg text-muted-foreground">
                                        {selectedVariant.compare_at_price}
                                    </s>
                                )}
                            </p>
                        </div>

                        {product.options.map((option) => (
                            <fieldset key={option.id}>
                                <legend className="mb-2 text-sm font-medium">
                                    {option.name}
                                </legend>
                                <div className="flex flex-wrap gap-2">
                                    {option.values.map((value) => {
                                        const checked =
                                            selectedValues[option.id] ===
                                            value.id;

                                        return (
                                            <label
                                                key={value.id}
                                                className={cn(
                                                    'cursor-pointer rounded-md border px-4 py-2 text-sm has-focus-visible:ring-2 has-focus-visible:ring-ring',
                                                    checked
                                                        ? 'border-primary bg-primary text-primary-foreground'
                                                        : 'hover:bg-accent',
                                                )}
                                            >
                                                <input
                                                    type="radio"
                                                    name={`option-${option.id}`}
                                                    value={value.id}
                                                    checked={checked}
                                                    onChange={() =>
                                                        setSelectedValues(
                                                            (previous) => ({
                                                                ...previous,
                                                                [option.id]:
                                                                    value.id,
                                                            }),
                                                        )
                                                    }
                                                    className="sr-only"
                                                />
                                                {value.value}
                                            </label>
                                        );
                                    })}
                                </div>
                            </fieldset>
                        ))}

                        <div className="flex flex-col gap-2">
                            <AddToBasketButton variant={selectedVariant} />
                            {selectedVariant?.low_stock && (
                                <p className="text-sm text-amber-600 dark:text-amber-500">
                                    Low stock — order soon.
                                </p>
                            )}
                        </div>

                        {product.description && (
                            <div className="prose prose-sm dark:prose-invert max-w-none whitespace-pre-line text-muted-foreground">
                                {product.description}
                            </div>
                        )}
                    </div>
                </div>

                <section
                    aria-labelledby="related-heading"
                    className="mt-16 border-t pt-10"
                >
                    <h2
                        id="related-heading"
                        className="mb-6 text-xl font-semibold tracking-tight"
                    >
                        You might also like
                    </h2>
                    <WhenVisible
                        data="relatedProducts"
                        buffer={300}
                        fallback={<ProductGridSkeleton count={4} />}
                    >
                        {relatedProducts && relatedProducts.length > 0 ? (
                            <ProductGrid
                                products={relatedProducts}
                                priorityCount={0}
                            />
                        ) : (
                            <p className="text-muted-foreground">
                                Nothing related yet.
                            </p>
                        )}
                    </WhenVisible>
                </section>
            </div>
        </>
    );
}
