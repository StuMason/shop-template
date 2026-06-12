import { useForm } from '@inertiajs/react';
import { Plus, Trash2 } from 'lucide-react';
import { useState } from 'react';
import type { AdminOption } from '@/components/admin/option-editor';
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
    destroy as destroyVariant,
    store as storeVariant,
    update as updateVariant,
} from '@/routes/admin/products/variants';

export type AdminVariant = {
    id: number;
    sku: string;
    price: number;
    compare_at_price: number | null;
    stock: number;
    low_stock_threshold: number;
    is_default: boolean;
    option_value_ids: number[];
    display_name: string;
};

/** Pence → "12.34" for editing. */
function toPounds(pence: number | null): string {
    return pence === null ? '' : (pence / 100).toFixed(2);
}

/** "12.34" → pence, safely. */
function toPence(pounds: string): number | null {
    if (pounds.trim() === '') {
        return null;
    }

    const value = Number.parseFloat(pounds);

    return Number.isNaN(value) ? null : Math.round(value * 100);
}

function VariantDialog({
    productId,
    options,
    variant,
    onClose,
}: {
    productId: number;
    options: AdminOption[];
    variant?: AdminVariant;
    onClose: () => void;
}) {
    const { data, setData, processing, errors, transform, post, put } =
        useForm<{
            sku: string;
            price: string;
            compare_at_price: string;
            stock: number;
            is_default: boolean;
            option_value_ids: number[];
        }>({
            sku: variant?.sku ?? '',
            price: toPounds(variant?.price ?? 0),
            compare_at_price: toPounds(variant?.compare_at_price ?? null),
            stock: variant?.stock ?? 0,
            is_default: variant?.is_default ?? false,
            option_value_ids: variant?.option_value_ids ?? [],
        });

    transform((current) => ({
        ...current,
        price: toPence(current.price) ?? 0,
        compare_at_price: toPence(current.compare_at_price),
    }));

    function submit(event: React.FormEvent) {
        event.preventDefault();

        const requestOptions = { onSuccess: onClose, preserveScroll: true };

        if (variant) {
            put(updateVariant([productId, variant.id]).url, requestOptions);
        } else {
            post(storeVariant(productId).url, requestOptions);
        }
    }

    return (
        <DialogContent>
            <DialogHeader>
                <DialogTitle>
                    {variant ? `Edit ${variant.sku}` : 'Add variant'}
                </DialogTitle>
                <DialogDescription>
                    Prices are in pounds; stock is tracked per variant.
                </DialogDescription>
            </DialogHeader>
            <form onSubmit={submit} className="flex flex-col gap-4">
                <div className="grid gap-2">
                    <Label htmlFor="variant-sku">SKU</Label>
                    <Input
                        id="variant-sku"
                        value={data.sku}
                        onChange={(event) => setData('sku', event.target.value)}
                        required
                    />
                    <InputError message={errors.sku} />
                </div>

                <div className="grid grid-cols-2 gap-4">
                    <div className="grid gap-2">
                        <Label htmlFor="variant-price">Price (£)</Label>
                        <Input
                            id="variant-price"
                            type="number"
                            min="0"
                            step="0.01"
                            value={data.price}
                            onChange={(event) =>
                                setData('price', event.target.value)
                            }
                            required
                        />
                        <InputError message={errors.price} />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="variant-compare">Compare at (£)</Label>
                        <Input
                            id="variant-compare"
                            type="number"
                            min="0"
                            step="0.01"
                            value={data.compare_at_price}
                            onChange={(event) =>
                                setData('compare_at_price', event.target.value)
                            }
                        />
                        <InputError message={errors.compare_at_price} />
                    </div>
                </div>

                <div className="grid grid-cols-2 gap-4">
                    <div className="grid gap-2">
                        <Label htmlFor="variant-stock">Stock</Label>
                        <Input
                            id="variant-stock"
                            type="number"
                            min="0"
                            value={data.stock}
                            onChange={(event) =>
                                setData(
                                    'stock',
                                    Number.parseInt(
                                        event.target.value || '0',
                                        10,
                                    ),
                                )
                            }
                            required
                        />
                        <InputError message={errors.stock} />
                    </div>
                    <label className="mt-6 flex items-center gap-2 text-sm">
                        <Checkbox
                            checked={data.is_default}
                            onCheckedChange={(checked) =>
                                setData('is_default', checked === true)
                            }
                        />
                        Default variant
                    </label>
                </div>

                {options.map((option) => (
                    <fieldset key={option.id} className="grid gap-2">
                        <legend className="text-sm font-medium">
                            {option.name}
                        </legend>
                        <div className="flex flex-wrap gap-2">
                            {option.values.map((value) => {
                                const checked = data.option_value_ids.includes(
                                    value.id,
                                );
                                const otherIdsInOption = option.values
                                    .map((v) => v.id)
                                    .filter((id) => id !== value.id);

                                return (
                                    <label
                                        key={value.id}
                                        className={`cursor-pointer rounded-md border px-3 py-1.5 text-sm ${
                                            checked
                                                ? 'border-primary bg-primary text-primary-foreground'
                                                : 'hover:bg-accent'
                                        }`}
                                    >
                                        <input
                                            type="radio"
                                            className="sr-only"
                                            name={`variant-option-${option.id}`}
                                            checked={checked}
                                            onChange={() =>
                                                setData('option_value_ids', [
                                                    ...data.option_value_ids.filter(
                                                        (id) =>
                                                            !otherIdsInOption.includes(
                                                                id,
                                                            ) &&
                                                            id !== value.id,
                                                    ),
                                                    value.id,
                                                ])
                                            }
                                        />
                                        {value.value}
                                    </label>
                                );
                            })}
                        </div>
                    </fieldset>
                ))}
                <InputError message={errors.option_value_ids} />

                <DialogFooter>
                    <Button type="submit" disabled={processing}>
                        {processing
                            ? 'Saving…'
                            : variant
                              ? 'Save variant'
                              : 'Add variant'}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    );
}

export function VariantEditor({
    productId,
    options,
    variants,
}: {
    productId: number;
    options: AdminOption[];
    variants: AdminVariant[];
}) {
    const [openDialog, setOpenDialog] = useState<number | 'new' | null>(null);
    const { delete: destroy, processing } = useForm();

    return (
        <section
            aria-labelledby="variants-heading"
            className="flex flex-col gap-3"
        >
            <div className="flex items-center justify-between">
                <h2 id="variants-heading" className="text-base font-semibold">
                    Variants
                </h2>
                <Dialog
                    open={openDialog === 'new'}
                    onOpenChange={(open) => setOpenDialog(open ? 'new' : null)}
                >
                    <DialogTrigger asChild>
                        <Button variant="outline" size="sm">
                            <Plus className="size-4" aria-hidden="true" />
                            Add variant
                        </Button>
                    </DialogTrigger>
                    {openDialog === 'new' && (
                        <VariantDialog
                            productId={productId}
                            options={options}
                            onClose={() => setOpenDialog(null)}
                        />
                    )}
                </Dialog>
            </div>

            <div className="overflow-hidden rounded-xl border">
                <table className="w-full text-sm">
                    <thead className="bg-muted/50 text-left">
                        <tr>
                            <th className="px-4 py-3 font-medium">SKU</th>
                            <th className="px-4 py-3 font-medium">Options</th>
                            <th className="px-4 py-3 font-medium">Price</th>
                            <th className="px-4 py-3 font-medium">Stock</th>
                            <th className="px-4 py-3">
                                <span className="sr-only">Actions</span>
                            </th>
                        </tr>
                    </thead>
                    <tbody className="divide-y">
                        {variants.map((variant) => (
                            <tr key={variant.id} className="hover:bg-muted/30">
                                <td className="px-4 py-3 font-medium">
                                    {variant.sku}
                                    {variant.is_default && (
                                        <Badge
                                            variant="secondary"
                                            className="ml-2"
                                        >
                                            default
                                        </Badge>
                                    )}
                                </td>
                                <td className="px-4 py-3 text-muted-foreground">
                                    {variant.display_name}
                                </td>
                                <td className="px-4 py-3">
                                    £{toPounds(variant.price)}
                                </td>
                                <td className="px-4 py-3">{variant.stock}</td>
                                <td className="px-4 py-3 text-right">
                                    <Dialog
                                        open={openDialog === variant.id}
                                        onOpenChange={(open) =>
                                            setOpenDialog(
                                                open ? variant.id : null,
                                            )
                                        }
                                    >
                                        <DialogTrigger asChild>
                                            <Button variant="ghost" size="sm">
                                                Edit
                                            </Button>
                                        </DialogTrigger>
                                        {openDialog === variant.id && (
                                            <VariantDialog
                                                productId={productId}
                                                options={options}
                                                variant={variant}
                                                onClose={() =>
                                                    setOpenDialog(null)
                                                }
                                            />
                                        )}
                                    </Dialog>
                                    <Button
                                        variant="ghost"
                                        size="icon"
                                        aria-label={`Delete ${variant.sku}`}
                                        disabled={
                                            processing || variants.length <= 1
                                        }
                                        onClick={() => {
                                            if (
                                                confirm(
                                                    `Delete variant ${variant.sku}?`,
                                                )
                                            ) {
                                                destroy(
                                                    destroyVariant([
                                                        productId,
                                                        variant.id,
                                                    ]).url,
                                                    { preserveScroll: true },
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
        </section>
    );
}
