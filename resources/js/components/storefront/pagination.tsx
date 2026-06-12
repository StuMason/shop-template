import { Link } from '@inertiajs/react';
import { cn } from '@/lib/utils';
import type { PaginationLink } from '@/types';

/**
 * Pagination from Laravel's paginator links array. Renders real anchors so
 * crawlers can walk paginated listings.
 */
export function Pagination({ links }: { links: PaginationLink[] }) {
    if (links.length <= 3) {
        return null;
    }

    return (
        <nav aria-label="Pagination" className="flex justify-center gap-1">
            {links.map((link, index) =>
                link.url ? (
                    <Link
                        key={index}
                        href={link.url}
                        preserveScroll
                        aria-current={link.active ? 'page' : undefined}
                        className={cn(
                            'inline-flex h-9 min-w-9 items-center justify-center rounded-md border px-3 text-sm',
                            link.active
                                ? 'border-primary bg-primary text-primary-foreground'
                                : 'hover:bg-accent',
                        )}
                    >
                        {decodeLabel(link.label)}
                    </Link>
                ) : (
                    <span
                        key={index}
                        className="inline-flex h-9 min-w-9 items-center justify-center px-3 text-sm text-muted-foreground"
                    >
                        {decodeLabel(link.label)}
                    </span>
                ),
            )}
        </nav>
    );
}

/** Laravel paginator labels contain a few known HTML entities. */
function decodeLabel(label: string): string {
    return label
        .replaceAll('&laquo;', '«')
        .replaceAll('&raquo;', '»')
        .replaceAll('&hellip;', '…');
}
