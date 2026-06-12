import { Head, useForm } from '@inertiajs/react';
import { Plus, Trash2 } from 'lucide-react';
import { useState } from 'react';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    destroy as destroyCategory,
    store as storeCategory,
    update as updateCategory,
} from '@/routes/admin/categories';

type AdminCategory = {
    id: number;
    name: string;
    slug: string;
    parent: string | null;
    parent_id: number | null;
    position: number;
    is_active: boolean;
    products_count: number;
    description: string | null;
    meta_title: string | null;
    meta_description: string | null;
};

type AdminCategoriesIndexProps = {
    categories: AdminCategory[];
};

function CategoryDialog({
    category,
    categories,
    onClose,
}: {
    category?: AdminCategory;
    categories: AdminCategory[];
    onClose: () => void;
}) {
    const { data, setData, post, put, processing, errors } = useForm<{
        name: string;
        description: string;
        parent_id: number | null;
        position: number;
        is_active: boolean;
    }>({
        name: category?.name ?? '',
        description: category?.description ?? '',
        parent_id: category?.parent_id ?? null,
        position: category?.position ?? 0,
        is_active: category?.is_active ?? true,
    });

    const parentChoices = categories.filter(
        (candidate) => candidate.id !== category?.id,
    );

    function submit(event: React.FormEvent) {
        event.preventDefault();

        const requestOptions = { onSuccess: onClose, preserveScroll: true };

        if (category) {
            put(updateCategory(category.id).url, requestOptions);
        } else {
            post(storeCategory().url, requestOptions);
        }
    }

    return (
        <DialogContent>
            <DialogHeader>
                <DialogTitle>
                    {category ? `Edit ${category.name}` : 'New category'}
                </DialogTitle>
                <DialogDescription>
                    Categories group products for navigation and SEO.
                </DialogDescription>
            </DialogHeader>
            <form onSubmit={submit} className="flex flex-col gap-4">
                <div className="grid gap-2">
                    <Label htmlFor="category-name">Name</Label>
                    <Input
                        id="category-name"
                        value={data.name}
                        onChange={(event) =>
                            setData('name', event.target.value)
                        }
                        required
                    />
                    <InputError message={errors.name} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="category-description">Description</Label>
                    <textarea
                        id="category-description"
                        value={data.description}
                        onChange={(event) =>
                            setData('description', event.target.value)
                        }
                        rows={3}
                        className="rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs"
                    />
                    <InputError message={errors.description} />
                </div>

                <div className="grid grid-cols-2 gap-4">
                    <div className="grid gap-2">
                        <Label htmlFor="category-parent">Parent</Label>
                        <select
                            id="category-parent"
                            value={data.parent_id ?? ''}
                            onChange={(event) =>
                                setData(
                                    'parent_id',
                                    event.target.value === ''
                                        ? null
                                        : Number(event.target.value),
                                )
                            }
                            className="h-9 rounded-md border border-input bg-transparent px-3 text-sm shadow-xs"
                        >
                            <option value="">None (top level)</option>
                            {parentChoices.map((candidate) => (
                                <option key={candidate.id} value={candidate.id}>
                                    {candidate.name}
                                </option>
                            ))}
                        </select>
                        <InputError message={errors.parent_id} />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="category-position">Position</Label>
                        <Input
                            id="category-position"
                            type="number"
                            min="0"
                            value={data.position}
                            onChange={(event) =>
                                setData(
                                    'position',
                                    Number.parseInt(
                                        event.target.value || '0',
                                        10,
                                    ),
                                )
                            }
                        />
                        <InputError message={errors.position} />
                    </div>
                </div>

                <label className="flex items-center gap-2 text-sm">
                    <Checkbox
                        checked={data.is_active}
                        onCheckedChange={(checked) =>
                            setData('is_active', checked === true)
                        }
                    />
                    Active (visible on the storefront)
                </label>

                <DialogFooter>
                    <Button type="submit" disabled={processing}>
                        {processing
                            ? 'Saving…'
                            : category
                              ? 'Save category'
                              : 'Create category'}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    );
}

export default function AdminCategoriesIndex({
    categories,
}: AdminCategoriesIndexProps) {
    const [openDialog, setOpenDialog] = useState<number | 'new' | null>(null);
    const { delete: destroy, processing } = useForm();

    return (
        <>
            <Head title="Categories" />
            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <div className="flex items-center justify-between">
                    <h1 className="text-xl font-semibold tracking-tight">
                        Categories
                    </h1>
                    <Dialog
                        open={openDialog === 'new'}
                        onOpenChange={(open) =>
                            setOpenDialog(open ? 'new' : null)
                        }
                    >
                        <DialogTrigger asChild>
                            <Button>
                                <Plus className="size-4" aria-hidden="true" />
                                New category
                            </Button>
                        </DialogTrigger>
                        {openDialog === 'new' && (
                            <CategoryDialog
                                categories={categories}
                                onClose={() => setOpenDialog(null)}
                            />
                        )}
                    </Dialog>
                </div>

                {categories.length > 0 ? (
                    <div className="overflow-hidden rounded-xl border">
                        <table className="w-full text-sm">
                            <thead className="bg-muted/50 text-left">
                                <tr>
                                    <th className="px-4 py-3 font-medium">
                                        Name
                                    </th>
                                    <th className="px-4 py-3 font-medium">
                                        Parent
                                    </th>
                                    <th className="px-4 py-3 font-medium">
                                        Products
                                    </th>
                                    <th className="px-4 py-3 font-medium">
                                        Status
                                    </th>
                                    <th className="px-4 py-3">
                                        <span className="sr-only">Actions</span>
                                    </th>
                                </tr>
                            </thead>
                            <tbody className="divide-y">
                                {categories.map((category) => (
                                    <tr
                                        key={category.id}
                                        className="hover:bg-muted/30"
                                    >
                                        <td className="px-4 py-3 font-medium">
                                            {category.name}
                                            <span className="ml-2 text-muted-foreground">
                                                /{category.slug}
                                            </span>
                                        </td>
                                        <td className="px-4 py-3 text-muted-foreground">
                                            {category.parent ?? '—'}
                                        </td>
                                        <td className="px-4 py-3">
                                            {category.products_count}
                                        </td>
                                        <td className="px-4 py-3">
                                            <Badge
                                                variant={
                                                    category.is_active
                                                        ? 'default'
                                                        : 'outline'
                                                }
                                            >
                                                {category.is_active
                                                    ? 'active'
                                                    : 'hidden'}
                                            </Badge>
                                        </td>
                                        <td className="px-4 py-3 text-right">
                                            <Dialog
                                                open={
                                                    openDialog === category.id
                                                }
                                                onOpenChange={(open) =>
                                                    setOpenDialog(
                                                        open
                                                            ? category.id
                                                            : null,
                                                    )
                                                }
                                            >
                                                <DialogTrigger asChild>
                                                    <Button
                                                        variant="ghost"
                                                        size="sm"
                                                    >
                                                        Edit
                                                    </Button>
                                                </DialogTrigger>
                                                {openDialog === category.id && (
                                                    <CategoryDialog
                                                        category={category}
                                                        categories={categories}
                                                        onClose={() =>
                                                            setOpenDialog(null)
                                                        }
                                                    />
                                                )}
                                            </Dialog>
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                aria-label={`Delete ${category.name}`}
                                                disabled={processing}
                                                onClick={() => {
                                                    if (
                                                        confirm(
                                                            `Delete ${category.name}?`,
                                                        )
                                                    ) {
                                                        destroy(
                                                            destroyCategory(
                                                                category.id,
                                                            ).url,
                                                            {
                                                                preserveScroll: true,
                                                            },
                                                        );
                                                    }
                                                }}
                                            >
                                                <Trash2
                                                    className="size-4"
                                                    aria-hidden="true"
                                                />
                                            </Button>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                ) : (
                    <p className="py-12 text-center text-muted-foreground">
                        No categories yet — create the first one.
                    </p>
                )}
            </div>
        </>
    );
}
