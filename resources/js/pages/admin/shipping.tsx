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
import shipping from '@/routes/admin/shipping';

const zoneRoutes = shipping.zones;
const methodRoutes = shipping.methods;

type Method = {
    id: number;
    name: string;
    description: string | null;
    price: number;
    free_over: number | null;
    is_active: boolean;
};

type Zone = {
    id: number;
    name: string;
    countries: string[];
    is_active: boolean;
    methods: Method[];
};

function pounds(pence: number | null): string {
    return pence === null ? '' : (pence / 100).toFixed(2);
}

function ZoneDialog({ zone, onClose }: { zone?: Zone; onClose: () => void }) {
    const { data, setData, post, put, processing, errors, transform } =
        useForm<{
            name: string;
            countries: string;
            is_active: boolean;
        }>({
            name: zone?.name ?? '',
            countries: zone?.countries.join(', ') ?? 'GB',
            is_active: zone?.is_active ?? true,
        });

    function submit(event: React.FormEvent) {
        event.preventDefault();

        transform((current) => ({
            ...current,
            countries: current.countries
                .split(',')
                .map((country) => country.trim().toUpperCase())
                .filter(Boolean),
        }));

        const options = { onSuccess: onClose, preserveScroll: true };

        if (zone) {
            put(zoneRoutes.update(zone.id).url, options);
        } else {
            post(zoneRoutes.store().url, options);
        }
    }

    return (
        <DialogContent>
            <DialogHeader>
                <DialogTitle>
                    {zone ? `Edit ${zone.name}` : 'New shipping zone'}
                </DialogTitle>
                <DialogDescription>
                    A zone groups the countries that share delivery options.
                </DialogDescription>
            </DialogHeader>
            <form onSubmit={submit} className="flex flex-col gap-4">
                <div className="grid gap-2">
                    <Label htmlFor="zone-name">Name</Label>
                    <Input
                        id="zone-name"
                        value={data.name}
                        onChange={(event) =>
                            setData('name', event.target.value)
                        }
                        required
                    />
                    <InputError message={errors.name} />
                </div>
                <div className="grid gap-2">
                    <Label htmlFor="zone-countries">
                        Country codes (comma-separated, ISO 2-letter)
                    </Label>
                    <Input
                        id="zone-countries"
                        value={data.countries}
                        onChange={(event) =>
                            setData('countries', event.target.value)
                        }
                        placeholder="GB, IE"
                        required
                    />
                    <InputError message={errors.countries} />
                </div>
                <label className="flex items-center gap-2 text-sm">
                    <Checkbox
                        checked={data.is_active}
                        onCheckedChange={(checked) =>
                            setData('is_active', checked === true)
                        }
                    />
                    Active
                </label>
                <DialogFooter>
                    <Button type="submit" disabled={processing}>
                        {processing ? 'Saving…' : 'Save zone'}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    );
}

function MethodDialog({
    zoneId,
    method,
    onClose,
}: {
    zoneId: number;
    method?: Method;
    onClose: () => void;
}) {
    const { data, setData, post, put, processing, errors, transform } =
        useForm<{
            name: string;
            description: string;
            price: string;
            free_over: string;
            is_active: boolean;
        }>({
            name: method?.name ?? '',
            description: method?.description ?? '',
            price: pounds(method?.price ?? 0),
            free_over: pounds(method?.free_over ?? null),
            is_active: method?.is_active ?? true,
        });

    function submit(event: React.FormEvent) {
        event.preventDefault();

        transform((current) => ({
            ...current,
            price: Math.round(Number.parseFloat(current.price || '0') * 100),
            free_over:
                current.free_over.trim() === ''
                    ? null
                    : Math.round(Number.parseFloat(current.free_over) * 100),
        }));

        const options = { onSuccess: onClose, preserveScroll: true };

        if (method) {
            put(methodRoutes.update([zoneId, method.id]).url, options);
        } else {
            post(methodRoutes.store(zoneId).url, options);
        }
    }

    return (
        <DialogContent>
            <DialogHeader>
                <DialogTitle>
                    {method ? `Edit ${method.name}` : 'New shipping method'}
                </DialogTitle>
                <DialogDescription>
                    Flat-rate delivery option with an optional free-shipping
                    threshold.
                </DialogDescription>
            </DialogHeader>
            <form onSubmit={submit} className="flex flex-col gap-4">
                <div className="grid gap-2">
                    <Label htmlFor="method-name">Name</Label>
                    <Input
                        id="method-name"
                        value={data.name}
                        onChange={(event) =>
                            setData('name', event.target.value)
                        }
                        required
                    />
                    <InputError message={errors.name} />
                </div>
                <div className="grid gap-2">
                    <Label htmlFor="method-description">Description</Label>
                    <Input
                        id="method-description"
                        value={data.description}
                        onChange={(event) =>
                            setData('description', event.target.value)
                        }
                        placeholder="3–5 working days"
                    />
                    <InputError message={errors.description} />
                </div>
                <div className="grid grid-cols-2 gap-4">
                    <div className="grid gap-2">
                        <Label htmlFor="method-price">Price (£)</Label>
                        <Input
                            id="method-price"
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
                        <Label htmlFor="method-free-over">
                            Free over (£, optional)
                        </Label>
                        <Input
                            id="method-free-over"
                            type="number"
                            min="0"
                            step="0.01"
                            value={data.free_over}
                            onChange={(event) =>
                                setData('free_over', event.target.value)
                            }
                        />
                        <InputError message={errors.free_over} />
                    </div>
                </div>
                <label className="flex items-center gap-2 text-sm">
                    <Checkbox
                        checked={data.is_active}
                        onCheckedChange={(checked) =>
                            setData('is_active', checked === true)
                        }
                    />
                    Active
                </label>
                <DialogFooter>
                    <Button type="submit" disabled={processing}>
                        {processing ? 'Saving…' : 'Save method'}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    );
}

export default function AdminShipping({ zones }: { zones: Zone[] }) {
    const [zoneDialog, setZoneDialog] = useState<number | 'new' | null>(null);
    const [methodDialog, setMethodDialog] = useState<string | null>(null);
    const deleteForm = useForm();

    return (
        <>
            <Head title="Shipping" />
            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <div className="flex items-center justify-between">
                    <h1 className="text-xl font-semibold tracking-tight">
                        Shipping
                    </h1>
                    <Dialog
                        open={zoneDialog === 'new'}
                        onOpenChange={(open) =>
                            setZoneDialog(open ? 'new' : null)
                        }
                    >
                        <DialogTrigger asChild>
                            <Button>
                                <Plus className="size-4" aria-hidden="true" />
                                New zone
                            </Button>
                        </DialogTrigger>
                        {zoneDialog === 'new' && (
                            <ZoneDialog onClose={() => setZoneDialog(null)} />
                        )}
                    </Dialog>
                </div>

                {zones.length === 0 && (
                    <p className="py-12 text-center text-muted-foreground">
                        No shipping zones yet — customers can't check out until
                        one exists.
                    </p>
                )}

                {zones.map((zone) => (
                    <section
                        key={zone.id}
                        aria-label={zone.name}
                        className="rounded-xl border"
                    >
                        <div className="flex flex-wrap items-center justify-between gap-3 border-b bg-muted/30 px-4 py-3">
                            <div className="flex items-center gap-3">
                                <h2 className="font-semibold">{zone.name}</h2>
                                <span className="text-sm text-muted-foreground">
                                    {zone.countries.join(', ')}
                                </span>
                                {!zone.is_active && (
                                    <Badge variant="outline">inactive</Badge>
                                )}
                            </div>
                            <div className="flex items-center gap-1">
                                <Dialog
                                    open={zoneDialog === zone.id}
                                    onOpenChange={(open) =>
                                        setZoneDialog(open ? zone.id : null)
                                    }
                                >
                                    <DialogTrigger asChild>
                                        <Button variant="ghost" size="sm">
                                            Edit
                                        </Button>
                                    </DialogTrigger>
                                    {zoneDialog === zone.id && (
                                        <ZoneDialog
                                            zone={zone}
                                            onClose={() => setZoneDialog(null)}
                                        />
                                    )}
                                </Dialog>
                                <Dialog
                                    open={methodDialog === `new-${zone.id}`}
                                    onOpenChange={(open) =>
                                        setMethodDialog(
                                            open ? `new-${zone.id}` : null,
                                        )
                                    }
                                >
                                    <DialogTrigger asChild>
                                        <Button variant="outline" size="sm">
                                            <Plus
                                                className="size-4"
                                                aria-hidden="true"
                                            />
                                            Method
                                        </Button>
                                    </DialogTrigger>
                                    {methodDialog === `new-${zone.id}` && (
                                        <MethodDialog
                                            zoneId={zone.id}
                                            onClose={() =>
                                                setMethodDialog(null)
                                            }
                                        />
                                    )}
                                </Dialog>
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    aria-label={`Delete ${zone.name}`}
                                    disabled={deleteForm.processing}
                                    onClick={() => {
                                        if (
                                            confirm(
                                                `Delete zone ${zone.name} and its methods?`,
                                            )
                                        ) {
                                            deleteForm.delete(
                                                zoneRoutes.destroy(zone.id).url,
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
                        </div>

                        {zone.methods.length > 0 ? (
                            <ul className="divide-y">
                                {zone.methods.map((method) => (
                                    <li
                                        key={method.id}
                                        className="flex flex-wrap items-center justify-between gap-3 px-4 py-3 text-sm"
                                    >
                                        <div>
                                            <p className="font-medium">
                                                {method.name}
                                                {!method.is_active && (
                                                    <Badge
                                                        variant="outline"
                                                        className="ml-2"
                                                    >
                                                        inactive
                                                    </Badge>
                                                )}
                                            </p>
                                            <p className="text-muted-foreground">
                                                {method.description}
                                            </p>
                                        </div>
                                        <div className="flex items-center gap-3">
                                            <span className="font-semibold">
                                                £{pounds(method.price)}
                                            </span>
                                            {method.free_over !== null && (
                                                <span className="text-muted-foreground">
                                                    free over £
                                                    {pounds(method.free_over)}
                                                </span>
                                            )}
                                            <Dialog
                                                open={
                                                    methodDialog ===
                                                    `edit-${method.id}`
                                                }
                                                onOpenChange={(open) =>
                                                    setMethodDialog(
                                                        open
                                                            ? `edit-${method.id}`
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
                                                {methodDialog ===
                                                    `edit-${method.id}` && (
                                                    <MethodDialog
                                                        zoneId={zone.id}
                                                        method={method}
                                                        onClose={() =>
                                                            setMethodDialog(
                                                                null,
                                                            )
                                                        }
                                                    />
                                                )}
                                            </Dialog>
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                aria-label={`Delete ${method.name}`}
                                                disabled={deleteForm.processing}
                                                onClick={() => {
                                                    if (
                                                        confirm(
                                                            `Delete ${method.name}?`,
                                                        )
                                                    ) {
                                                        deleteForm.delete(
                                                            methodRoutes.destroy(
                                                                [
                                                                    zone.id,
                                                                    method.id,
                                                                ],
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
                                        </div>
                                    </li>
                                ))}
                            </ul>
                        ) : (
                            <p className="px-4 py-6 text-sm text-muted-foreground">
                                No methods in this zone yet.
                            </p>
                        )}
                    </section>
                ))}
            </div>
        </>
    );
}
