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
import { destroy, store, update } from '@/routes/account/addresses';

type AddressRow = {
    id: number;
    label: string | null;
    name: string;
    line1: string;
    line2: string | null;
    city: string;
    county: string | null;
    postcode: string;
    country: string;
    phone: string | null;
    is_default_shipping: boolean;
    is_default_billing: boolean;
};

function AddressDialog({
    address,
    onClose,
}: {
    address?: AddressRow;
    onClose: () => void;
}) {
    const { data, setData, post, put, processing, errors } = useForm({
        label: address?.label ?? '',
        name: address?.name ?? '',
        line1: address?.line1 ?? '',
        line2: address?.line2 ?? '',
        city: address?.city ?? '',
        county: address?.county ?? '',
        postcode: address?.postcode ?? '',
        country: address?.country ?? 'GB',
        phone: address?.phone ?? '',
        is_default_shipping: address?.is_default_shipping ?? false,
        is_default_billing: address?.is_default_billing ?? false,
    });

    function submit(event: React.FormEvent) {
        event.preventDefault();

        const options = { onSuccess: onClose, preserveScroll: true };

        if (address) {
            put(update(address.id).url, options);
        } else {
            post(store().url, options);
        }
    }

    return (
        <DialogContent className="max-h-[90vh] overflow-y-auto">
            <DialogHeader>
                <DialogTitle>
                    {address ? 'Edit address' : 'New address'}
                </DialogTitle>
                <DialogDescription>
                    Saved addresses pre-fill your checkout.
                </DialogDescription>
            </DialogHeader>
            <form onSubmit={submit} className="flex flex-col gap-4">
                <div className="grid gap-2">
                    <Label htmlFor="address-label">
                        Label{' '}
                        <span className="text-muted-foreground">
                            (e.g. Home)
                        </span>
                    </Label>
                    <Input
                        id="address-label"
                        value={data.label}
                        onChange={(event) =>
                            setData('label', event.target.value)
                        }
                    />
                </div>
                <div className="grid gap-2">
                    <Label htmlFor="address-name">Full name</Label>
                    <Input
                        id="address-name"
                        value={data.name}
                        onChange={(event) =>
                            setData('name', event.target.value)
                        }
                        required
                    />
                    <InputError message={errors.name} />
                </div>
                <div className="grid gap-2">
                    <Label htmlFor="address-line1">Address line 1</Label>
                    <Input
                        id="address-line1"
                        value={data.line1}
                        onChange={(event) =>
                            setData('line1', event.target.value)
                        }
                        required
                    />
                    <InputError message={errors.line1} />
                </div>
                <div className="grid gap-2">
                    <Label htmlFor="address-line2">Address line 2</Label>
                    <Input
                        id="address-line2"
                        value={data.line2}
                        onChange={(event) =>
                            setData('line2', event.target.value)
                        }
                    />
                </div>
                <div className="grid grid-cols-2 gap-4">
                    <div className="grid gap-2">
                        <Label htmlFor="address-city">Town / city</Label>
                        <Input
                            id="address-city"
                            value={data.city}
                            onChange={(event) =>
                                setData('city', event.target.value)
                            }
                            required
                        />
                        <InputError message={errors.city} />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="address-postcode">Postcode</Label>
                        <Input
                            id="address-postcode"
                            value={data.postcode}
                            onChange={(event) =>
                                setData('postcode', event.target.value)
                            }
                            required
                        />
                        <InputError message={errors.postcode} />
                    </div>
                </div>
                <div className="grid grid-cols-2 gap-4">
                    <div className="grid gap-2">
                        <Label htmlFor="address-country">Country code</Label>
                        <Input
                            id="address-country"
                            value={data.country}
                            maxLength={2}
                            onChange={(event) =>
                                setData(
                                    'country',
                                    event.target.value.toUpperCase(),
                                )
                            }
                            required
                        />
                        <InputError message={errors.country} />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="address-phone">Phone</Label>
                        <Input
                            id="address-phone"
                            type="tel"
                            value={data.phone}
                            onChange={(event) =>
                                setData('phone', event.target.value)
                            }
                        />
                    </div>
                </div>
                <label className="flex items-center gap-2 text-sm">
                    <Checkbox
                        checked={data.is_default_shipping}
                        onCheckedChange={(checked) =>
                            setData('is_default_shipping', checked === true)
                        }
                    />
                    Default shipping address
                </label>
                <label className="flex items-center gap-2 text-sm">
                    <Checkbox
                        checked={data.is_default_billing}
                        onCheckedChange={(checked) =>
                            setData('is_default_billing', checked === true)
                        }
                    />
                    Default billing address
                </label>
                <DialogFooter>
                    <Button type="submit" disabled={processing}>
                        {processing ? 'Saving…' : 'Save address'}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    );
}

export default function AccountAddresses({
    addresses,
}: {
    addresses: AddressRow[];
}) {
    const [openDialog, setOpenDialog] = useState<number | 'new' | null>(null);
    const deleteForm = useForm();

    return (
        <>
            <Head title="Addresses" />
            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <div className="flex items-center justify-between">
                    <h1 className="text-xl font-semibold tracking-tight">
                        Addresses
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
                                New address
                            </Button>
                        </DialogTrigger>
                        {openDialog === 'new' && (
                            <AddressDialog
                                onClose={() => setOpenDialog(null)}
                            />
                        )}
                    </Dialog>
                </div>

                {addresses.length > 0 ? (
                    <ul className="grid gap-4 sm:grid-cols-2">
                        {addresses.map((address) => (
                            <li
                                key={address.id}
                                className="flex flex-col gap-2 rounded-xl border p-4 text-sm"
                            >
                                <div className="flex items-start justify-between gap-2">
                                    <p className="font-medium">
                                        {address.label || address.name}
                                    </p>
                                    <div className="flex gap-1">
                                        {address.is_default_shipping && (
                                            <Badge variant="secondary">
                                                shipping
                                            </Badge>
                                        )}
                                        {address.is_default_billing && (
                                            <Badge variant="secondary">
                                                billing
                                            </Badge>
                                        )}
                                    </div>
                                </div>
                                <p className="text-muted-foreground">
                                    {[
                                        address.name,
                                        address.line1,
                                        address.line2,
                                        address.city,
                                        address.postcode,
                                        address.country,
                                    ]
                                        .filter(Boolean)
                                        .join(', ')}
                                </p>
                                <div className="mt-auto flex gap-1">
                                    <Dialog
                                        open={openDialog === address.id}
                                        onOpenChange={(open) =>
                                            setOpenDialog(
                                                open ? address.id : null,
                                            )
                                        }
                                    >
                                        <DialogTrigger asChild>
                                            <Button variant="ghost" size="sm">
                                                Edit
                                            </Button>
                                        </DialogTrigger>
                                        {openDialog === address.id && (
                                            <AddressDialog
                                                address={address}
                                                onClose={() =>
                                                    setOpenDialog(null)
                                                }
                                            />
                                        )}
                                    </Dialog>
                                    <Button
                                        variant="ghost"
                                        size="icon"
                                        aria-label={`Delete address ${address.label || address.name}`}
                                        disabled={deleteForm.processing}
                                        onClick={() => {
                                            if (confirm('Delete this address?')) {
                                                deleteForm.delete(
                                                    destroy(address.id).url,
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
                                </div>
                            </li>
                        ))}
                    </ul>
                ) : (
                    <p className="py-12 text-center text-muted-foreground">
                        No saved addresses yet.
                    </p>
                )}
            </div>
        </>
    );
}
