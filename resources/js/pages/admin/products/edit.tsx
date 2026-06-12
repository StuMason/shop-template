import { Head, Link, useForm } from '@inertiajs/react';
import { ExternalLink, Trash2, Upload } from 'lucide-react';
import { useRef } from 'react';
import type { AdminOption } from '@/components/admin/option-editor';
import { OptionEditor } from '@/components/admin/option-editor';
import type { AdminVariant } from '@/components/admin/variant-editor';
import { VariantEditor } from '@/components/admin/variant-editor';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Separator } from '@/components/ui/separator';
import {
    destroy as destroyProduct,
    update as updateProduct,
} from '@/routes/admin/products';
import {
    destroy as destroyMedia,
    store as storeMedia,
} from '@/routes/admin/products/media';
import { show as productShow } from '@/routes/products';

type AdminProductDetail = {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    status: string;
    meta_title: string | null;
    meta_description: string | null;
    category_ids: number[];
    options: AdminOption[];
    variants: AdminVariant[];
    images: { id: number; url: string; name: string }[];
};

type AdminProductsEditProps = {
    product: AdminProductDetail;
    categories: { id: number; name: string }[];
};

function ImageManager({ product }: { product: AdminProductDetail }) {
    const fileInput = useRef<HTMLInputElement>(null);
    const uploadForm = useForm<{ images: File[] }>({ images: [] });
    const deleteForm = useForm();

    function uploadSelected(files: FileList | null) {
        if (!files || files.length === 0) {
            return;
        }

        uploadForm.setData('images', Array.from(files));
        uploadForm.post(storeMedia(product.id).url, {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => uploadForm.reset(),
        });
    }

    return (
        <section aria-labelledby="images-heading" className="flex flex-col gap-3">
            <div className="flex items-center justify-between">
                <h2 id="images-heading" className="text-base font-semibold">
                    Images
                </h2>
                <div>
                    <input
                        ref={fileInput}
                        type="file"
                        accept="image/jpeg,image/png,image/webp,image/avif"
                        multiple
                        className="sr-only"
                        aria-label="Upload product images"
                        onChange={(event) =>
                            uploadSelected(event.target.files)
                        }
                    />
                    <Button
                        variant="outline"
                        size="sm"
                        disabled={uploadForm.processing}
                        onClick={() => fileInput.current?.click()}
                    >
                        <Upload className="size-4" aria-hidden="true" />
                        {uploadForm.processing ? 'Uploading…' : 'Upload images'}
                    </Button>
                </div>
            </div>
            <InputError message={uploadForm.errors.images} />

            {product.images.length === 0 ? (
                <p className="text-sm text-muted-foreground">
                    No images yet. The storefront shows a placeholder pattern
                    until you upload one.
                </p>
            ) : (
                <ul className="flex flex-wrap gap-3">
                    {product.images.map((image) => (
                        <li key={image.id} className="group relative">
                            <img
                                src={image.url}
                                alt={image.name}
                                className="size-24 rounded-lg border object-cover"
                            />
                            <Button
                                variant="destructive"
                                size="icon"
                                aria-label={`Delete ${image.name}`}
                                className="absolute -top-2 -right-2 size-6 opacity-0 transition-opacity group-hover:opacity-100 focus-visible:opacity-100"
                                disabled={deleteForm.processing}
                                onClick={() =>
                                    deleteForm.delete(
                                        destroyMedia([product.id, image.id])
                                            .url,
                                        { preserveScroll: true },
                                    )
                                }
                            >
                                <Trash2
                                    className="size-3.5"
                                    aria-hidden="true"
                                />
                            </Button>
                        </li>
                    ))}
                </ul>
            )}
        </section>
    );
}

export default function AdminProductsEdit({
    product,
    categories,
}: AdminProductsEditProps) {
    const { data, setData, put, processing, errors } = useForm<{
        name: string;
        slug: string;
        description: string;
        status: string;
        meta_title: string;
        meta_description: string;
        category_ids: number[];
    }>({
        name: product.name,
        slug: product.slug,
        description: product.description ?? '',
        status: product.status,
        meta_title: product.meta_title ?? '',
        meta_description: product.meta_description ?? '',
        category_ids: product.category_ids,
    });

    const deleteForm = useForm();

    return (
        <>
            <Head title={`Edit: ${product.name}`} />
            <div className="flex h-full flex-1 flex-col gap-8 p-4">
                <div className="flex flex-wrap items-center justify-between gap-4">
                    <h1 className="text-xl font-semibold tracking-tight">
                        {product.name}
                    </h1>
                    {product.status === 'published' && (
                        <Button variant="outline" size="sm" asChild>
                            <Link href={productShow(product.slug)}>
                                <ExternalLink
                                    className="size-4"
                                    aria-hidden="true"
                                />
                                View in shop
                            </Link>
                        </Button>
                    )}
                </div>

                <form
                    onSubmit={(event) => {
                        event.preventDefault();
                        put(updateProduct(product.id).url, {
                            preserveScroll: true,
                        });
                    }}
                    className="flex max-w-2xl flex-col gap-6"
                >
                    <div className="grid gap-4 sm:grid-cols-2">
                        <div className="grid gap-2">
                            <Label htmlFor="name">Name</Label>
                            <Input
                                id="name"
                                value={data.name}
                                onChange={(event) =>
                                    setData('name', event.target.value)
                                }
                                required
                            />
                            <InputError message={errors.name} />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="slug">Slug</Label>
                            <Input
                                id="slug"
                                value={data.slug}
                                onChange={(event) =>
                                    setData('slug', event.target.value)
                                }
                                required
                            />
                            <InputError message={errors.slug} />
                        </div>
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="description">Description</Label>
                        <textarea
                            id="description"
                            value={data.description}
                            onChange={(event) =>
                                setData('description', event.target.value)
                            }
                            rows={6}
                            className="rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs"
                        />
                        <InputError message={errors.description} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="status">Status</Label>
                        <select
                            id="status"
                            value={data.status}
                            onChange={(event) =>
                                setData('status', event.target.value)
                            }
                            className="h-9 w-48 rounded-md border border-input bg-transparent px-3 text-sm shadow-xs"
                        >
                            <option value="draft">Draft</option>
                            <option value="published">Published</option>
                            <option value="archived">Archived</option>
                        </select>
                        <InputError message={errors.status} />
                    </div>

                    <fieldset className="grid gap-2">
                        <legend className="text-sm font-medium">
                            Categories
                        </legend>
                        <div className="flex flex-wrap gap-x-6 gap-y-2">
                            {categories.map((category) => (
                                <label
                                    key={category.id}
                                    className="flex items-center gap-2 text-sm"
                                >
                                    <Checkbox
                                        checked={data.category_ids.includes(
                                            category.id,
                                        )}
                                        onCheckedChange={(checked) =>
                                            setData(
                                                'category_ids',
                                                checked === true
                                                    ? [
                                                          ...data.category_ids,
                                                          category.id,
                                                      ]
                                                    : data.category_ids.filter(
                                                          (id) =>
                                                              id !==
                                                              category.id,
                                                      ),
                                            )
                                        }
                                    />
                                    {category.name}
                                </label>
                            ))}
                        </div>
                    </fieldset>

                    <div className="grid gap-4 sm:grid-cols-2">
                        <div className="grid gap-2">
                            <Label htmlFor="meta_title">Meta title</Label>
                            <Input
                                id="meta_title"
                                value={data.meta_title}
                                onChange={(event) =>
                                    setData('meta_title', event.target.value)
                                }
                            />
                            <InputError message={errors.meta_title} />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="meta_description">
                                Meta description
                            </Label>
                            <Input
                                id="meta_description"
                                value={data.meta_description}
                                onChange={(event) =>
                                    setData(
                                        'meta_description',
                                        event.target.value,
                                    )
                                }
                            />
                            <InputError message={errors.meta_description} />
                        </div>
                    </div>

                    <div>
                        <Button type="submit" disabled={processing}>
                            {processing ? 'Saving…' : 'Save changes'}
                        </Button>
                    </div>
                </form>

                <Separator />
                <OptionEditor
                    productId={product.id}
                    options={product.options}
                />
                <Separator />
                <VariantEditor
                    productId={product.id}
                    options={product.options}
                    variants={product.variants}
                />
                <Separator />
                <ImageManager product={product} />
                <Separator />

                <section
                    aria-labelledby="danger-heading"
                    className="flex items-center justify-between rounded-xl border border-destructive/40 p-4"
                >
                    <div>
                        <h2
                            id="danger-heading"
                            className="text-base font-semibold"
                        >
                            Delete product
                        </h2>
                        <p className="text-sm text-muted-foreground">
                            Removes the product, its variants and images.
                        </p>
                    </div>
                    <Button
                        variant="destructive"
                        disabled={deleteForm.processing}
                        onClick={() => {
                            if (confirm(`Delete ${product.name}?`)) {
                                deleteForm.delete(
                                    destroyProduct(product.id).url,
                                );
                            }
                        }}
                    >
                        Delete
                    </Button>
                </section>
            </div>
        </>
    );
}
