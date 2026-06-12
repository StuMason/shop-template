import { Head, useForm } from '@inertiajs/react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { update } from '@/routes/admin/settings';

type ShopSettingsForm = {
    name: string;
    tagline: string;
    description: string;
    contact_email: string;
    order_prefix: string;
    trading_details: string;
};

export default function AdminSettings({
    settings,
    currency,
}: {
    settings: ShopSettingsForm;
    currency: string;
}) {
    const { data, setData, put, processing, errors, recentlySuccessful } =
        useForm<ShopSettingsForm>(settings);

    return (
        <>
            <Head title="Shop settings" />
            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <h1 className="text-xl font-semibold tracking-tight">
                    Shop settings
                </h1>

                <form
                    onSubmit={(event) => {
                        event.preventDefault();
                        put(update().url, { preserveScroll: true });
                    }}
                    className="flex max-w-xl flex-col gap-6"
                >
                    <div className="grid gap-2">
                        <Label htmlFor="shop-name">Shop name</Label>
                        <Input
                            id="shop-name"
                            value={data.name}
                            onChange={(event) =>
                                setData('name', event.target.value)
                            }
                            required
                        />
                        <InputError message={errors.name} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="shop-tagline">Tagline</Label>
                        <Input
                            id="shop-tagline"
                            value={data.tagline}
                            onChange={(event) =>
                                setData('tagline', event.target.value)
                            }
                            required
                        />
                        <InputError message={errors.tagline} />
                        <p className="text-sm text-muted-foreground">
                            Shown in the storefront hero and meta descriptions.
                        </p>
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="shop-description">Description</Label>
                        <textarea
                            id="shop-description"
                            value={data.description}
                            onChange={(event) =>
                                setData('description', event.target.value)
                            }
                            rows={3}
                            required
                            className="rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs"
                        />
                        <InputError message={errors.description} />
                    </div>

                    <div className="grid gap-4 sm:grid-cols-2">
                        <div className="grid gap-2">
                            <Label htmlFor="shop-email">Contact email</Label>
                            <Input
                                id="shop-email"
                                type="email"
                                value={data.contact_email}
                                onChange={(event) =>
                                    setData('contact_email', event.target.value)
                                }
                                required
                            />
                            <InputError message={errors.contact_email} />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="shop-prefix">Order prefix</Label>
                            <Input
                                id="shop-prefix"
                                value={data.order_prefix}
                                maxLength={8}
                                onChange={(event) =>
                                    setData(
                                        'order_prefix',
                                        event.target.value.toUpperCase(),
                                    )
                                }
                                required
                            />
                            <InputError message={errors.order_prefix} />
                        </div>
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="shop-trading">
                            Trading details{' '}
                            <span className="text-muted-foreground">
                                (shown in the footer)
                            </span>
                        </Label>
                        <textarea
                            id="shop-trading"
                            value={data.trading_details}
                            onChange={(event) =>
                                setData('trading_details', event.target.value)
                            }
                            rows={2}
                            placeholder="Your Company Ltd · Company No. 12345678 · 1 High Street, Bristol, BS1 1AA"
                            className="rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs"
                        />
                        <InputError message={errors.trading_details} />
                        <p className="text-sm text-muted-foreground">
                            UK distance-selling rules require customers to see
                            who they're buying from.
                        </p>
                    </div>

                    <p className="text-sm text-muted-foreground">
                        Currency is {currency}, set via the{' '}
                        <code>SHOP_CURRENCY</code> environment variable —
                        changing it mid-flight would re-price existing baskets,
                        so it's deliberately not editable here.
                    </p>

                    <div className="flex items-center gap-3">
                        <Button type="submit" disabled={processing}>
                            {processing ? 'Saving…' : 'Save settings'}
                        </Button>
                        {recentlySuccessful && (
                            <span className="text-sm text-muted-foreground">
                                Saved.
                            </span>
                        )}
                    </div>
                </form>
            </div>
        </>
    );
}
