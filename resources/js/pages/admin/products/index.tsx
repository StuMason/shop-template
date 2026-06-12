import { Form, Head, Link } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import { Pagination } from '@/components/storefront/pagination';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { PlaceholderPattern } from '@/components/ui/placeholder-pattern';
import {
    create,
    edit,
    index as productsIndex,
} from '@/routes/admin/products';
import type { ImagePayload, Paginated } from '@/types';

type AdminProductRow = {
    id: number;
    name: string;
    slug: string;
    status: string;
    price: string | null;
    variants_count: number;
    image: ImagePayload | null;
};

type AdminProductsIndexProps = {
    products: Paginated<AdminProductRow>;
    filters: { q: string };
};

const STATUS_VARIANTS: Record<
    string,
    'default' | 'secondary' | 'outline' | 'destructive'
> = {
    published: 'default',
    draft: 'secondary',
    archived: 'outline',
};

export default function AdminProductsIndex({
    products,
    filters,
}: AdminProductsIndexProps) {
    return (
        <>
            <Head title="Products" />
            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <div className="flex flex-wrap items-center justify-between gap-4">
                    <h1 className="text-xl font-semibold tracking-tight">
                        Products
                    </h1>
                    <div className="flex items-center gap-3">
                        <Form action={productsIndex()}>
                            <Input
                                type="search"
                                name="q"
                                defaultValue={filters.q}
                                placeholder="Search products…"
                                aria-label="Search products"
                                className="w-56"
                            />
                        </Form>
                        <Button asChild>
                            <Link href={create()}>
                                <Plus className="size-4" aria-hidden="true" />
                                New product
                            </Link>
                        </Button>
                    </div>
                </div>

                {products.data.length > 0 ? (
                    <div className="flex flex-col gap-6">
                        <div className="overflow-hidden rounded-xl border">
                            <table className="w-full text-sm">
                                <thead className="bg-muted/50 text-left">
                                    <tr>
                                        <th className="px-4 py-3 font-medium">
                                            Product
                                        </th>
                                        <th className="px-4 py-3 font-medium">
                                            Status
                                        </th>
                                        <th className="px-4 py-3 font-medium">
                                            Price
                                        </th>
                                        <th className="px-4 py-3 font-medium">
                                            Variants
                                        </th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y">
                                    {products.data.map((product) => (
                                        <tr
                                            key={product.id}
                                            className="hover:bg-muted/30"
                                        >
                                            <td className="px-4 py-3">
                                                <Link
                                                    href={edit(product.id)}
                                                    className="flex items-center gap-3 font-medium hover:underline"
                                                >
                                                    <span className="relative size-10 shrink-0 overflow-hidden rounded-md border bg-muted">
                                                        {product.image ? (
                                                            <img
                                                                src={
                                                                    product
                                                                        .image
                                                                        .src
                                                                }
                                                                alt=""
                                                                className="size-full object-cover"
                                                            />
                                                        ) : (
                                                            <PlaceholderPattern className="absolute inset-0 size-full stroke-neutral-900/10 dark:stroke-neutral-100/10" />
                                                        )}
                                                    </span>
                                                    {product.name}
                                                </Link>
                                            </td>
                                            <td className="px-4 py-3">
                                                <Badge
                                                    variant={
                                                        STATUS_VARIANTS[
                                                            product.status
                                                        ] ?? 'secondary'
                                                    }
                                                >
                                                    {product.status}
                                                </Badge>
                                            </td>
                                            <td className="px-4 py-3">
                                                {product.price ?? '—'}
                                            </td>
                                            <td className="px-4 py-3">
                                                {product.variants_count}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                        <Pagination links={products.links} />
                    </div>
                ) : (
                    <p className="py-12 text-center text-muted-foreground">
                        No products found.
                    </p>
                )}
            </div>
        </>
    );
}
