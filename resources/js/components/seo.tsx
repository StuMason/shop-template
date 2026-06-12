import { Head, usePage } from '@inertiajs/react';

type SeoProps = {
    title: string;
    description?: string;
    canonical?: string;
    image?: string;
    type?: 'website' | 'product';
    noindex?: boolean;
    jsonLd?: Record<string, unknown>[];
};

/**
 * Per-page SEO tags. Every storefront page should render one of these.
 * head-keys ensure these override the baseline tags in app.blade.php.
 */
export function Seo({
    title,
    description,
    canonical,
    image,
    type = 'website',
    noindex = false,
    jsonLd = [],
}: SeoProps) {
    const { url, props } = usePage<{
        shop: { url: string };
        [key: string]: unknown;
    }>();
    // Canonicals must be absolute; Inertia's url (and wayfinder .url()) are
    // path-relative, so prefix the app URL shared by the backend (SSR-safe).
    const base = props.shop.url;
    const path =
        canonical ?? (typeof url === 'string' ? url.split('?')[0] : '');
    const resolvedCanonical = path
        ? path.startsWith('http')
            ? path
            : `${base}${path === '/' ? '' : path}`
        : undefined;

    return (
        <Head title={title}>
            {description && (
                <meta
                    head-key="description"
                    name="description"
                    content={description}
                />
            )}
            {resolvedCanonical && (
                <link
                    head-key="canonical"
                    rel="canonical"
                    href={resolvedCanonical}
                />
            )}
            <meta head-key="og:title" property="og:title" content={title} />
            {description && (
                <meta
                    head-key="og:description"
                    property="og:description"
                    content={description}
                />
            )}
            <meta head-key="og:type" property="og:type" content={type} />
            {image && (
                <meta head-key="og:image" property="og:image" content={image} />
            )}
            <meta
                head-key="twitter:card"
                name="twitter:card"
                content={image ? 'summary_large_image' : 'summary'}
            />
            {noindex && (
                <meta head-key="robots" name="robots" content="noindex" />
            )}
            {jsonLd.map((schema, index) => (
                <script
                    head-key={`json-ld-${index}`}
                    key={index}
                    type="application/ld+json"
                >
                    {JSON.stringify(schema)}
                </script>
            ))}
        </Head>
    );
}
