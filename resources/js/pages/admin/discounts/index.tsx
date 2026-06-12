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
import { destroy, store, update } from '@/routes/admin/discounts';

type DiscountRow = {
    id: number;
    code: string;
    type: string;
    value: number;
    min_subtotal: number | null;
    starts_at: string | null;
    ends_at: string | null;
    max_uses: number | null;
    once_per_customer: boolean;
    used_count: number;
    is_active: boolean;
};

function describeValue(discount: DiscountRow): string {
    return discount.type === 'percent'
        ? `${discount.value}% off`
        : `£${(discount.value / 100).toFixed(2)} off`;
}

function DiscountDialog({
    discount,
    onClose,
}: {
    discount?: DiscountRow;
    onClose: () => void;
}) {
    const { data, setData, post, put, processing, errors, transform } = useForm(
        {
            code: discount?.code ?? '',
            type: discount?.type ?? 'percent',
            value:
                discount?.type === 'fixed'
                    ? (discount.value / 100).toFixed(2)
                    : String(discount?.value ?? 10),
            min_subtotal: discount?.min_subtotal
                ? (discount.min_subtotal / 100).toFixed(2)
                : '',
            starts_at: discount?.starts_at ?? '',
            ends_at: discount?.ends_at ?? '',
            max_uses: discount?.max_uses ? String(discount.max_uses) : '',
            once_per_customer: discount?.once_per_customer ?? false,
            is_active: discount?.is_active ?? true,
        },
    );

    function submit(event: React.FormEvent) {
        event.preventDefault();

        transform((current) => ({
            ...current,
            value:
                current.type === 'fixed'
                    ? Math.round(Number.parseFloat(current.value || '0') * 100)
                    : Number.parseInt(current.value || '0', 10),
            min_subtotal:
                current.min_subtotal.trim() === ''
                    ? null
                    : Math.round(Number.parseFloat(current.min_subtotal) * 100),
            starts_at: current.starts_at || null,
            ends_at: current.ends_at || null,
            max_uses:
                current.max_uses.trim() === ''
                    ? null
                    : Number.parseInt(current.max_uses, 10),
        }));

        const options = { onSuccess: onClose, preserveScroll: true };

        if (discount) {
            put(update(discount.id).url, options);
        } else {
            post(store().url, options);
        }
    }

    return (
        <DialogContent>
            <DialogHeader>
                <DialogTitle>
                    {discount ? `Edit ${discount.code}` : 'New discount'}
                </DialogTitle>
                <DialogDescription>
                    Percentage discounts take a whole number; fixed discounts
                    are in pounds.
                </DialogDescription>
            </DialogHeader>
            <form onSubmit={submit} className="flex flex-col gap-4">
                <div className="grid grid-cols-2 gap-4">
                    <div className="grid gap-2">
                        <Label htmlFor="discount-code">Code</Label>
                        <Input
                            id="discount-code"
                            value={data.code}
                            onChange={(event) =>
                                setData(
                                    'code',
                                    event.target.value.toUpperCase(),
                                )
                            }
                            placeholder="WELCOME10"
                            required
                        />
                        <InputError message={errors.code} />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="discount-type">Type</Label>
                        <select
                            id="discount-type"
                            value={data.type}
                            onChange={(event) =>
                                setData('type', event.target.value)
                            }
                            className="h-9 rounded-md border border-input bg-transparent px-3 text-sm shadow-xs"
                        >
                            <option value="percent">Percent</option>
                            <option value="fixed">Fixed amount</option>
                        </select>
                    </div>
                </div>

                <div className="grid grid-cols-2 gap-4">
                    <div className="grid gap-2">
                        <Label htmlFor="discount-value">
                            {data.type === 'percent'
                                ? 'Percent off'
                                : 'Amount off (£)'}
                        </Label>
                        <Input
                            id="discount-value"
                            type="number"
                            min={data.type === 'percent' ? '1' : '0.01'}
                            max={data.type === 'percent' ? '100' : undefined}
                            step={data.type === 'percent' ? '1' : '0.01'}
                            value={data.value}
                            onChange={(event) =>
                                setData('value', event.target.value)
                            }
                            required
                        />
                        <InputError message={errors.value} />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="discount-min">
                            Minimum spend (£, optional)
                        </Label>
                        <Input
                            id="discount-min"
                            type="number"
                            min="0"
                            step="0.01"
                            value={data.min_subtotal}
                            onChange={(event) =>
                                setData('min_subtotal', event.target.value)
                            }
                        />
                        <InputError message={errors.min_subtotal} />
                    </div>
                </div>

                <div className="grid grid-cols-2 gap-4">
                    <div className="grid gap-2">
                        <Label htmlFor="discount-starts">
                            Starts (optional)
                        </Label>
                        <Input
                            id="discount-starts"
                            type="date"
                            value={data.starts_at}
                            onChange={(event) =>
                                setData('starts_at', event.target.value)
                            }
                        />
                        <InputError message={errors.starts_at} />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="discount-ends">Ends (optional)</Label>
                        <Input
                            id="discount-ends"
                            type="date"
                            value={data.ends_at}
                            onChange={(event) =>
                                setData('ends_at', event.target.value)
                            }
                        />
                        <InputError message={errors.ends_at} />
                    </div>
                </div>

                <div className="grid grid-cols-2 items-end gap-4">
                    <div className="grid gap-2">
                        <Label htmlFor="discount-max">
                            Max uses (optional)
                        </Label>
                        <Input
                            id="discount-max"
                            type="number"
                            min="1"
                            value={data.max_uses}
                            onChange={(event) =>
                                setData('max_uses', event.target.value)
                            }
                        />
                        <InputError message={errors.max_uses} />
                    </div>
                    <label className="mb-2 flex items-center gap-2 text-sm">
                        <Checkbox
                            checked={data.is_active}
                            onCheckedChange={(checked) =>
                                setData('is_active', checked === true)
                            }
                        />
                        Active
                    </label>
                </div>

                <label className="flex items-center gap-2 text-sm">
                    <Checkbox
                        checked={data.once_per_customer}
                        onCheckedChange={(checked) =>
                            setData('once_per_customer', checked === true)
                        }
                    />
                    Once per customer (matched by email or account)
                </label>

                <DialogFooter>
                    <Button type="submit" disabled={processing}>
                        {processing ? 'Saving…' : 'Save discount'}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    );
}

export default function AdminDiscountsIndex({
    discounts,
}: {
    discounts: DiscountRow[];
}) {
    const [openDialog, setOpenDialog] = useState<number | 'new' | null>(null);
    const deleteForm = useForm();

    return (
        <>
            <Head title="Discounts" />
            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <div className="flex items-center justify-between">
                    <h1 className="text-xl font-semibold tracking-tight">
                        Discounts
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
                                New discount
                            </Button>
                        </DialogTrigger>
                        {openDialog === 'new' && (
                            <DiscountDialog
                                onClose={() => setOpenDialog(null)}
                            />
                        )}
                    </Dialog>
                </div>

                {discounts.length > 0 ? (
                    <div className="overflow-hidden rounded-xl border">
                        <table className="w-full text-sm">
                            <thead className="bg-muted/50 text-left">
                                <tr>
                                    <th className="px-4 py-3 font-medium">
                                        Code
                                    </th>
                                    <th className="px-4 py-3 font-medium">
                                        Discount
                                    </th>
                                    <th className="px-4 py-3 font-medium">
                                        Window
                                    </th>
                                    <th className="px-4 py-3 font-medium">
                                        Uses
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
                                {discounts.map((discount) => (
                                    <tr
                                        key={discount.id}
                                        className="hover:bg-muted/30"
                                    >
                                        <td className="px-4 py-3 font-medium">
                                            {discount.code}
                                        </td>
                                        <td className="px-4 py-3">
                                            {describeValue(discount)}
                                            {discount.min_subtotal && (
                                                <span className="text-muted-foreground">
                                                    {' '}
                                                    over £
                                                    {(
                                                        discount.min_subtotal /
                                                        100
                                                    ).toFixed(2)}
                                                </span>
                                            )}
                                        </td>
                                        <td className="px-4 py-3 text-muted-foreground">
                                            {discount.starts_at ?? '—'} →{' '}
                                            {discount.ends_at ?? '—'}
                                        </td>
                                        <td className="px-4 py-3">
                                            {discount.used_count}
                                            {discount.max_uses
                                                ? ` / ${discount.max_uses}`
                                                : ''}
                                            {discount.once_per_customer && (
                                                <span className="block text-xs text-muted-foreground">
                                                    once per customer
                                                </span>
                                            )}
                                        </td>
                                        <td className="px-4 py-3">
                                            <Badge
                                                variant={
                                                    discount.is_active
                                                        ? 'default'
                                                        : 'outline'
                                                }
                                            >
                                                {discount.is_active
                                                    ? 'active'
                                                    : 'inactive'}
                                            </Badge>
                                        </td>
                                        <td className="px-4 py-3 text-right">
                                            <Dialog
                                                open={
                                                    openDialog === discount.id
                                                }
                                                onOpenChange={(open) =>
                                                    setOpenDialog(
                                                        open
                                                            ? discount.id
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
                                                {openDialog === discount.id && (
                                                    <DiscountDialog
                                                        discount={discount}
                                                        onClose={() =>
                                                            setOpenDialog(null)
                                                        }
                                                    />
                                                )}
                                            </Dialog>
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                aria-label={`Delete ${discount.code}`}
                                                disabled={deleteForm.processing}
                                                onClick={() => {
                                                    if (
                                                        confirm(
                                                            `Delete ${discount.code}?`,
                                                        )
                                                    ) {
                                                        deleteForm.delete(
                                                            destroy(discount.id)
                                                                .url,
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
                        No discount codes yet.
                    </p>
                )}
            </div>
        </>
    );
}
