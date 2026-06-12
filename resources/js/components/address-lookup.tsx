import { usePage } from '@inertiajs/react';
import { Loader2, Search } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { cn } from '@/lib/utils';
import {
    resolve as resolveRoute,
    suggest as suggestRoute,
} from '@/routes/address-lookup';
import type { ShopInfo } from '@/types';

export type LookupAddress = {
    line1: string;
    line2: string;
    city: string;
    county: string;
    postcode: string;
    country: string;
};

type SuggestionRow = { id: string; label: string };

function AddressLookupField({
    id,
    country,
    onSelect,
}: {
    id: string;
    country: string;
    onSelect: (address: LookupAddress) => void;
}) {
    const [query, setQuery] = useState('');
    const [suggestions, setSuggestions] = useState<SuggestionRow[]>([]);
    const [open, setOpen] = useState(false);
    const [active, setActive] = useState(-1);
    const [resolving, setResolving] = useState(false);
    // One opaque session per mount: providers (Google) bill the
    // suggest + resolve pair as a single session. Created lazily from the
    // effect/handlers, never during render.
    const sessionRef = useRef('');
    const session = () => {
        if (sessionRef.current === '') {
            sessionRef.current =
                typeof crypto !== 'undefined' && 'randomUUID' in crypto
                    ? crypto.randomUUID()
                    : `${Date.now()}-${Math.random().toString(36).slice(2)}`;
        }

        return sessionRef.current;
    };

    useEffect(() => {
        const term = query.trim();
        const controller = new AbortController();
        // All setState happens inside the timeout callback, not the effect
        // body; a zero delay clears the list when the term is too short.
        const timer = setTimeout(
            async () => {
                if (term.length < 3) {
                    setSuggestions([]);
                    setOpen(false);

                    return;
                }

                try {
                    const url = suggestRoute({
                        query: {
                            q: term,
                            country,
                            session: session(),
                        },
                    }).url;
                    const response = await fetch(url, {
                        signal: controller.signal,
                        headers: { Accept: 'application/json' },
                    });

                    if (!response.ok) {
                        return;
                    }

                    const payload = (await response.json()) as {
                        suggestions: SuggestionRow[];
                    };
                    setSuggestions(payload.suggestions);
                    setOpen(payload.suggestions.length > 0);
                    setActive(-1);
                } catch {
                    // Aborted or network error: manual entry still works.
                }
            },
            term.length < 3 ? 0 : 300,
        );

        return () => {
            controller.abort();
            clearTimeout(timer);
        };
    }, [query, country]);

    async function pick(suggestion: SuggestionRow) {
        setOpen(false);
        setResolving(true);

        try {
            const url = resolveRoute({
                query: { id: suggestion.id, session: session() },
            }).url;
            const response = await fetch(url, {
                headers: { Accept: 'application/json' },
            });

            if (response.ok) {
                const payload = (await response.json()) as {
                    address: LookupAddress;
                };
                onSelect(payload.address);
                setQuery('');
                setSuggestions([]);
            }
        } catch {
            // Leave whatever they typed; manual entry still works.
        } finally {
            setResolving(false);
        }
    }

    function onKeyDown(event: React.KeyboardEvent) {
        if (!open) {
            return;
        }

        if (event.key === 'ArrowDown') {
            event.preventDefault();
            setActive((current) =>
                Math.min(current + 1, suggestions.length - 1),
            );
        } else if (event.key === 'ArrowUp') {
            event.preventDefault();
            setActive((current) => Math.max(current - 1, 0));
        } else if (event.key === 'Enter' && active >= 0) {
            event.preventDefault();
            void pick(suggestions[active]);
        } else if (event.key === 'Escape') {
            setOpen(false);
        }
    }

    return (
        <div className="grid gap-2">
            <Label htmlFor={id}>Find your address</Label>
            <div className="relative">
                {resolving ? (
                    <Loader2
                        className="absolute top-1/2 left-3 size-4 -translate-y-1/2 animate-spin text-muted-foreground"
                        aria-hidden="true"
                    />
                ) : (
                    <Search
                        className="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
                        aria-hidden="true"
                    />
                )}
                <Input
                    id={id}
                    type="text"
                    role="combobox"
                    aria-expanded={open}
                    aria-controls={`${id}-listbox`}
                    aria-autocomplete="list"
                    autoComplete="off"
                    className="pl-9"
                    placeholder="Start typing your address…"
                    value={query}
                    disabled={resolving}
                    onChange={(event) => setQuery(event.target.value)}
                    onKeyDown={onKeyDown}
                    onBlur={() => setTimeout(() => setOpen(false), 150)}
                />
                {open && (
                    <ul
                        id={`${id}-listbox`}
                        role="listbox"
                        aria-label="Address suggestions"
                        className="absolute z-50 mt-1 max-h-64 w-full overflow-auto rounded-md border bg-popover p-1 text-popover-foreground shadow-md"
                    >
                        {suggestions.map((suggestion, index) => (
                            <li
                                key={suggestion.id}
                                role="option"
                                aria-selected={index === active}
                                className={cn(
                                    'cursor-pointer rounded-sm px-2 py-1.5 text-sm',
                                    index === active
                                        ? 'bg-accent text-accent-foreground'
                                        : 'hover:bg-accent hover:text-accent-foreground',
                                )}
                                onMouseEnter={() => setActive(index)}
                                onMouseDown={(event) => {
                                    event.preventDefault();
                                    void pick(suggestion);
                                }}
                            >
                                {suggestion.label}
                            </li>
                        ))}
                    </ul>
                )}
            </div>
            <p className="text-xs text-muted-foreground">
                Search, or fill in the fields below.
            </p>
        </div>
    );
}

/**
 * Type-ahead address search proxied through the server (the provider key
 * never reaches the browser). Renders nothing when ADDRESS_LOOKUP=none, so
 * the template works out of the box with manual entry only.
 */
export function AddressLookup(props: {
    id: string;
    country: string;
    onSelect: (address: LookupAddress) => void;
}) {
    const { shop } = usePage<{
        shop: ShopInfo;
        [key: string]: unknown;
    }>().props;

    if (!shop.address_lookup) {
        return null;
    }

    return <AddressLookupField {...props} />;
}
