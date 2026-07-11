import { Head, usePage } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import type { ShopInfo } from '@/types';

type Scope = {
    id: string;
    description: string;
};

type Props = {
    client: { name: string };
    scopes: Scope[];
    authToken: string;
    csrfToken: string;
};

export default function OAuthAuthorize({
    client,
    scopes,
    authToken,
    csrfToken,
}: Props) {
    const { shop } = usePage<{ shop: ShopInfo }>().props;

    return (
        <>
            <Head title="Authorize access" />

            <div className="flex flex-col gap-6">
                <p className="text-center text-sm text-muted-foreground">
                    <span className="font-medium text-foreground">
                        {client.name}
                    </span>{' '}
                    wants to connect to your {shop.name} account.
                </p>

                {scopes.length > 0 && (
                    <div className="rounded-lg border border-border bg-muted/40 p-4">
                        <p className="mb-2 text-sm font-medium">
                            It will be able to:
                        </p>
                        <ul className="space-y-1.5 text-sm text-muted-foreground">
                            {scopes.map((scope) => (
                                <li key={scope.id} className="flex gap-2">
                                    <span aria-hidden>&bull;</span>
                                    <span>{scope.description}</span>
                                </li>
                            ))}
                        </ul>
                    </div>
                )}

                {/*
                    Native form posts, not Inertia visits: approving redirects
                    the browser to the client's own callback URL, which an XHR
                    visit cannot follow.
                */}
                <div className="flex gap-3">
                    <form
                        method="post"
                        action="/oauth/authorize"
                        className="flex-1"
                    >
                        <input type="hidden" name="_token" value={csrfToken} />
                        <input
                            type="hidden"
                            name="auth_token"
                            value={authToken}
                        />
                        <Button type="submit" className="w-full">
                            Authorize
                        </Button>
                    </form>

                    <form
                        method="post"
                        action="/oauth/authorize"
                        className="flex-1"
                    >
                        <input type="hidden" name="_token" value={csrfToken} />
                        <input type="hidden" name="_method" value="DELETE" />
                        <input
                            type="hidden"
                            name="auth_token"
                            value={authToken}
                        />
                        <Button
                            type="submit"
                            variant="outline"
                            className="w-full"
                        >
                            Cancel
                        </Button>
                    </form>
                </div>
            </div>
        </>
    );
}

OAuthAuthorize.layout = {
    title: 'Authorize access',
    description: 'An application is requesting access to your account.',
};
