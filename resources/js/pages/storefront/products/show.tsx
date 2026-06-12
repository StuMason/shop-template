import { Link, useForm, usePage, WhenVisible } from '@inertiajs/react';
import { Star } from 'lucide-react';
import { useMemo, useState } from 'react';
import InputError from '@/components/input-error';
import { Seo } from '@/components/seo';
import { AddToBasketButton } from '@/components/storefront/add-to-basket-button';
import {
    ProductGrid,
    ProductGridSkeleton,
} from '@/components/storefront/product-grid';
import { ProductImage } from '@/components/storefront/product-image';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { cn } from '@/lib/utils';
import { show as categoryShow } from '@/routes/categories';
import { store as stockNotificationsStore } from '@/routes/stock-notifications';
import type {
    ProductCard,
    ProductDetail,
    ProductVariant,
    ShopInfo,
} from '@/types';

type ProductShowProps = {
    reviews: {
        count: number;
        average: number | null;
        items: {
            id: number;
            name: string;
            rating: number;
            body: string | null;
            date: string;
        }[];
    };
    product: ProductDetail;
    relatedProducts?: ProductCard[];
};

function StarRow({ rating }: { rating: number }) {
    return (
        <span
            className="flex items-center gap-0.5"
            aria-label={`${rating} out of 5 stars`}
        >
            {[1, 2, 3, 4, 5].map((value) => (
                <Star
                    key={value}
                    className={
                        value <= Math.round(rating)
                            ? 'size-4 fill-amber-400 text-amber-400'
                            : 'size-4 text-muted-foreground'
                    }
                    aria-hidden="true"
                />
            ))}
        </span>
    );
}

function NotifyMeForm({ variantId }: { variantId: number }) {
    const { data, setData, post, processing, errors, recentlySuccessful } =
        useForm({
            email: '',
            variant_id: variantId,
        });

    if (recentlySuccessful) {
        return (
            <p className="text-sm text-muted-foreground" role="status">
                Done — we'll email you the moment it's back.
            </p>
        );
    }

    return (
        <form
            onSubmit={(event) => {
                event.preventDefault();
                setData('variant_id', variantId);
                post(stockNotificationsStore().url, {
                    preserveScroll: true,
                });
            }}
            className="grid gap-2"
        >
            <p className="text-sm font-medium">
                Out of stock — get an email when it returns
            </p>
            <div className="flex gap-2">
                <Input
                    type="email"
                    value={data.email}
                    onChange={(event) => setData('email', event.target.value)}
                    placeholder="you@example.com"
                    aria-label="Email for back-in-stock notification"
                    required
                />
                <Button type="submit" variant="secondary" disabled={processing}>
                    Notify me
                </Button>
            </div>
            <InputError message={errors.email} />
        </form>
    );
}

export default function ProductShow({
    product,
    relatedProducts,
    reviews,
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
                ...(reviews.count > 0 && reviews.average !== null
                    ? {
                          aggregateRating: {
                              '@type': 'AggregateRating',
                              ratingValue: reviews.average,
                              reviewCount: reviews.count,
                          },
                      }
                    : {}),
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
    }, [product, reviews, shop, defaultVariant]);

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
                        <div
                            className="aspect-square overflow-hidden rounded-xl border bg-muted"
                            style={{
                                viewTransitionName: `product-image-${product.id}`,
                            }}
                        >
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
                            {selectedVariant &&
                                !selectedVariant.in_stock &&
                                !product.is_digital && (
                                    <NotifyMeForm
                                        variantId={selectedVariant.id}
                                    />
                                )}
                            {product.is_digital && (
                                <p className="text-sm text-muted-foreground">
                                    Digital download — delivered instantly by
                                    email after payment.
                                </p>
                            )}
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
                    {reviews.count > 0 && reviews.average !== null && (
                        <section
                            aria-labelledby="reviews-heading"
                            className="mt-16"
                        >
                            <div className="mb-6 flex items-center gap-3">
                                <h2
                                    id="reviews-heading"
                                    className="text-xl font-semibold tracking-tight"
                                >
                                    Reviews
                                </h2>
                                <StarRow rating={reviews.average} />
                                <span className="text-sm text-muted-foreground">
                                    {reviews.average} · {reviews.count} verified{' '}
                                    {reviews.count === 1
                                        ? 'purchase'
                                        : 'purchases'}
                                </span>
                            </div>
                            <div className="grid gap-4 sm:grid-cols-2">
                                {reviews.items.map((review) => (
                                    <article
                                        key={review.id}
                                        className="rounded-xl border p-4 text-sm"
                                    >
                                        <div className="mb-1 flex items-center gap-2">
                                            <StarRow rating={review.rating} />
                                            <span className="font-medium">
                                                {review.name}
                                            </span>
                                            <span className="text-muted-foreground">
                                                {review.date}
                                            </span>
                                        </div>
                                        {review.body && <p>{review.body}</p>}
                                    </article>
                                ))}
                            </div>
                        </section>
                    )}

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
