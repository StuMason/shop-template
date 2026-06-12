import { Head, router } from '@inertiajs/react';
import { Star, Trash2 } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    destroy as destroyReview,
    update as updateReview,
} from '@/routes/admin/reviews';

type ReviewRow = {
    id: number;
    product: string;
    name: string;
    email: string;
    rating: number;
    body: string | null;
    is_published: boolean;
    date: string;
};

export default function AdminReviewsIndex({
    reviews,
}: {
    reviews: { data: ReviewRow[] };
}) {
    return (
        <>
            <Head title="Reviews" />
            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <h1 className="text-xl font-semibold tracking-tight">
                    Reviews
                </h1>

                {reviews.data.length === 0 ? (
                    <p className="py-12 text-center text-muted-foreground">
                        No reviews yet — they arrive a few days after
                        deliveries.
                    </p>
                ) : (
                    <div className="grid gap-3">
                        {reviews.data.map((review) => (
                            <div
                                key={review.id}
                                className="flex items-start justify-between gap-4 rounded-xl border p-4"
                            >
                                <div className="grid gap-1 text-sm">
                                    <div className="flex items-center gap-2">
                                        <span
                                            className="flex items-center gap-0.5"
                                            aria-label={`${review.rating} out of 5`}
                                        >
                                            {[1, 2, 3, 4, 5].map((value) => (
                                                <Star
                                                    key={value}
                                                    className={
                                                        value <= review.rating
                                                            ? 'size-4 fill-amber-400 text-amber-400'
                                                            : 'size-4 text-muted-foreground'
                                                    }
                                                    aria-hidden="true"
                                                />
                                            ))}
                                        </span>
                                        <span className="font-medium">
                                            {review.product}
                                        </span>
                                        {!review.is_published && (
                                            <Badge variant="outline">
                                                hidden
                                            </Badge>
                                        )}
                                    </div>
                                    {review.body && <p>{review.body}</p>}
                                    <p className="text-muted-foreground">
                                        {review.name} · {review.email} ·{' '}
                                        {review.date}
                                    </p>
                                </div>
                                <div className="flex shrink-0 gap-2">
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        onClick={() =>
                                            router.put(
                                                updateReview(review.id).url,
                                                {
                                                    is_published:
                                                        !review.is_published,
                                                },
                                                { preserveScroll: true },
                                            )
                                        }
                                    >
                                        {review.is_published
                                            ? 'Hide'
                                            : 'Publish'}
                                    </Button>
                                    <Button
                                        variant="ghost"
                                        size="icon"
                                        aria-label="Delete review"
                                        onClick={() => {
                                            if (confirm('Delete review?')) {
                                                router.delete(
                                                    destroyReview(review.id)
                                                        .url,
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
                        ))}
                    </div>
                )}
            </div>
        </>
    );
}
