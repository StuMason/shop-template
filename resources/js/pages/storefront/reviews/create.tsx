import { useForm } from '@inertiajs/react';
import { Star } from 'lucide-react';
import InputError from '@/components/input-error';
import { Seo } from '@/components/seo';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { cn } from '@/lib/utils';

export default function ReviewCreate({
    submit_url,
    order_number,
    product,
    existing,
}: {
    submit_url: string;
    order_number: string;
    product: { id: number; name: string; slug: string };
    existing: { rating: number; body: string | null } | null;
}) {
    const { data, setData, post, processing, errors } = useForm({
        rating: existing?.rating ?? 0,
        body: existing?.body ?? '',
        name: '',
    });

    return (
        <>
            <Seo title={`Review ${product.name}`} noindex />

            <div className="mx-auto w-full max-w-xl px-4 py-10 sm:px-6">
                <h1 className="text-2xl font-semibold tracking-tight">
                    How was your {product.name}?
                </h1>
                <p className="mt-1 mb-8 text-sm text-muted-foreground">
                    From order {order_number}. Reviews are public and marked as
                    a verified purchase.
                </p>

                <form
                    onSubmit={(event) => {
                        event.preventDefault();
                        post(submit_url);
                    }}
                    className="grid gap-6"
                >
                    <fieldset className="grid gap-2">
                        <legend className="text-sm font-medium">
                            Your rating
                        </legend>
                        <div className="flex gap-1">
                            {[1, 2, 3, 4, 5].map((value) => (
                                <button
                                    key={value}
                                    type="button"
                                    aria-label={`${value} star${value > 1 ? 's' : ''}`}
                                    aria-pressed={data.rating >= value}
                                    onClick={() => setData('rating', value)}
                                    className="p-1"
                                >
                                    <Star
                                        className={cn(
                                            'size-7',
                                            data.rating >= value
                                                ? 'fill-amber-400 text-amber-400'
                                                : 'text-muted-foreground',
                                        )}
                                        aria-hidden="true"
                                    />
                                </button>
                            ))}
                        </div>
                        <InputError message={errors.rating} />
                    </fieldset>

                    <div className="grid gap-2">
                        <Label htmlFor="review-name">Display name</Label>
                        <Input
                            id="review-name"
                            value={data.name}
                            onChange={(event) =>
                                setData('name', event.target.value)
                            }
                            placeholder="First name is fine"
                            required
                        />
                        <InputError message={errors.name} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="review-body">
                            Your review{' '}
                            <span className="text-muted-foreground">
                                (optional)
                            </span>
                        </Label>
                        <textarea
                            id="review-body"
                            value={data.body}
                            onChange={(event) =>
                                setData('body', event.target.value)
                            }
                            rows={5}
                            className="rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs"
                        />
                        <InputError message={errors.body} />
                    </div>

                    <Button
                        type="submit"
                        disabled={processing || data.rating === 0}
                    >
                        {existing ? 'Update review' : 'Publish review'}
                    </Button>
                </form>
            </div>
        </>
    );
}
