import { useForm } from '@inertiajs/react';
import { Plus, Trash2, X } from 'lucide-react';
import { useState } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
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
    destroy as destroyOption,
    store as storeOption,
    update as updateOption,
} from '@/routes/admin/products/options';

export type AdminOption = {
    id: number;
    name: string;
    values: { id: number; value: string }[];
};

function OptionDialog({
    productId,
    option,
    onClose,
}: {
    productId: number;
    option?: AdminOption;
    onClose: () => void;
}) {
    const { data, setData, post, put, processing, errors } = useForm<{
        name: string;
        values: string[];
    }>({
        name: option?.name ?? '',
        values: option?.values.map((value) => value.value) ?? [''],
    });

    function submit(event: React.FormEvent) {
        event.preventDefault();

        const requestOptions = { onSuccess: onClose };

        if (option) {
            put(updateOption([productId, option.id]).url, requestOptions);
        } else {
            post(storeOption(productId).url, requestOptions);
        }
    }

    return (
        <DialogContent>
            <DialogHeader>
                <DialogTitle>
                    {option ? `Edit ${option.name}` : 'Add option'}
                </DialogTitle>
                <DialogDescription>
                    An option is a choice axis like Size or Colour. Its values
                    combine into variants.
                </DialogDescription>
            </DialogHeader>
            <form onSubmit={submit} className="flex flex-col gap-4">
                <div className="grid gap-2">
                    <Label htmlFor="option-name">Name</Label>
                    <Input
                        id="option-name"
                        value={data.name}
                        onChange={(event) =>
                            setData('name', event.target.value)
                        }
                        placeholder="Size"
                        required
                    />
                    <InputError message={errors.name} />
                </div>

                <div className="grid gap-2">
                    <Label>Values</Label>
                    {data.values.map((value, index) => (
                        <div key={index} className="flex items-center gap-2">
                            <Input
                                value={value}
                                onChange={(event) =>
                                    setData(
                                        'values',
                                        data.values.map((existing, i) =>
                                            i === index
                                                ? event.target.value
                                                : existing,
                                        ),
                                    )
                                }
                                placeholder="Large"
                                aria-label={`Value ${index + 1}`}
                                required
                            />
                            <Button
                                type="button"
                                variant="ghost"
                                size="icon"
                                aria-label={`Remove value ${index + 1}`}
                                disabled={data.values.length <= 1}
                                onClick={() =>
                                    setData(
                                        'values',
                                        data.values.filter(
                                            (_, i) => i !== index,
                                        ),
                                    )
                                }
                            >
                                <X className="size-4" aria-hidden="true" />
                            </Button>
                        </div>
                    ))}
                    <InputError message={errors.values} />
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        onClick={() => setData('values', [...data.values, ''])}
                    >
                        <Plus className="size-4" aria-hidden="true" />
                        Add value
                    </Button>
                </div>

                <DialogFooter>
                    <Button type="submit" disabled={processing}>
                        {processing
                            ? 'Saving…'
                            : option
                              ? 'Save option'
                              : 'Add option'}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    );
}

export function OptionEditor({
    productId,
    options,
}: {
    productId: number;
    options: AdminOption[];
}) {
    const [openDialog, setOpenDialog] = useState<number | 'new' | null>(null);
    const { delete: destroy, processing } = useForm();

    return (
        <section
            aria-labelledby="options-heading"
            className="flex flex-col gap-3"
        >
            <div className="flex items-center justify-between">
                <h2 id="options-heading" className="text-base font-semibold">
                    Options
                </h2>
                <Dialog
                    open={openDialog === 'new'}
                    onOpenChange={(open) => setOpenDialog(open ? 'new' : null)}
                >
                    <DialogTrigger asChild>
                        <Button variant="outline" size="sm">
                            <Plus className="size-4" aria-hidden="true" />
                            Add option
                        </Button>
                    </DialogTrigger>
                    {openDialog === 'new' && (
                        <OptionDialog
                            productId={productId}
                            onClose={() => setOpenDialog(null)}
                        />
                    )}
                </Dialog>
            </div>

            {options.length === 0 ? (
                <p className="text-sm text-muted-foreground">
                    No options — this is a simple product with one variant. Add
                    an option like Size to sell multiple variants.
                </p>
            ) : (
                <ul className="flex flex-col gap-2">
                    {options.map((option) => (
                        <li
                            key={option.id}
                            className="flex items-center justify-between gap-4 rounded-lg border px-4 py-3"
                        >
                            <div>
                                <p className="text-sm font-medium">
                                    {option.name}
                                </p>
                                <p className="text-sm text-muted-foreground">
                                    {option.values
                                        .map((value) => value.value)
                                        .join(', ')}
                                </p>
                            </div>
                            <div className="flex items-center gap-1">
                                <Dialog
                                    open={openDialog === option.id}
                                    onOpenChange={(open) =>
                                        setOpenDialog(open ? option.id : null)
                                    }
                                >
                                    <DialogTrigger asChild>
                                        <Button variant="ghost" size="sm">
                                            Edit
                                        </Button>
                                    </DialogTrigger>
                                    {openDialog === option.id && (
                                        <OptionDialog
                                            productId={productId}
                                            option={option}
                                            onClose={() => setOpenDialog(null)}
                                        />
                                    )}
                                </Dialog>
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    aria-label={`Delete ${option.name}`}
                                    disabled={processing}
                                    onClick={() => {
                                        if (
                                            confirm(
                                                `Delete the ${option.name} option? Its values will be detached from variants.`,
                                            )
                                        ) {
                                            destroy(
                                                destroyOption([
                                                    productId,
                                                    option.id,
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
                            </div>
                        </li>
                    ))}
                </ul>
            )}
        </section>
    );
}
