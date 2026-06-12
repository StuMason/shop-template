import { PlaceholderPattern } from '@/components/ui/placeholder-pattern';
import { cn } from '@/lib/utils';
import type { ImagePayload } from '@/types';

type ProductImageProps = {
    image: ImagePayload | null;
    /** Above-the-fold images load eagerly with high fetch priority. */
    priority?: boolean;
    sizes?: string;
    className?: string;
};

/**
 * Product image with srcset, lazy loading and a placeholder fallback. The
 * wrapping element must define the aspect ratio (e.g. aspect-square) so the
 * layout never shifts while the image loads.
 */
export function ProductImage({
    image,
    priority = false,
    sizes = '(min-width: 1024px) 25vw, (min-width: 640px) 33vw, 50vw',
    className,
}: ProductImageProps) {
    if (!image) {
        return (
            <div
                className={cn(
                    'relative size-full overflow-hidden bg-muted',
                    className,
                )}
                aria-hidden="true"
            >
                <PlaceholderPattern className="absolute inset-0 size-full stroke-neutral-900/10 dark:stroke-neutral-100/10" />
            </div>
        );
    }

    return (
        <picture className="contents">
            {image.avif_srcset && (
                <source
                    type="image/avif"
                    srcSet={image.avif_srcset}
                    sizes={sizes}
                />
            )}
            <img
                src={image.src}
                srcSet={image.srcset || undefined}
                sizes={sizes}
                alt={image.alt}
                loading={priority ? 'eager' : 'lazy'}
                fetchPriority={priority ? 'high' : 'auto'}
                decoding="async"
                className={cn('size-full object-cover', className)}
            />
        </picture>
    );
}
