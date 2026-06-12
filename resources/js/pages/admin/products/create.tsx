import { Head, useForm } from '@inertiajs/react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { store } from '@/routes/admin/products';

type AdminProductsCreateProps = {
    categories: { id: number; name: string }[];
};

export default function AdminProductsCreate({
    categories,
}: AdminProductsCreateProps) {
    const { data, setData, post, processing, errors } = useForm<{
        name: string;
        description: string;
        status: string;
        category_ids: number[];
    }>({
        name: '',
        description: '',
        status: 'draft',
        category_ids: [],
    });

    function toggleCategory(id: number, checked: boolean) {
        setData(
            'category_ids',
            checked
                ? [...data.category_ids, id]
                : data.category_ids.filter((categoryId) => categoryId !== id),
        );
    }

    return (
        <>
            <Head title="New product" />
            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <h1 className="text-xl font-semibold tracking-tight">
                    New product
                </h1>

                <form
                    onSubmit={(event) => {
                        event.preventDefault();
                        post(store().url);
                    }}
                    className="flex max-w-xl flex-col gap-6"
                >
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
                                            toggleCategory(
                                                category.id,
                                                checked === true,
                                            )
                                        }
                                    />
                                    {category.name}
                                </label>
                            ))}
                        </div>
                        <InputError message={errors.category_ids} />
                    </fieldset>

                    <div className="flex items-center gap-3">
                        <Button type="submit" disabled={processing}>
                            {processing ? 'Creating…' : 'Create product'}
                        </Button>
                        <p className="text-sm text-muted-foreground">
                            Pricing, variants and images come next.
                        </p>
                    </div>
                </form>
            </div>
        </>
    );
}
