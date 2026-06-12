import { Form, Head, router } from '@inertiajs/react';
import { Pagination } from '@/components/storefront/pagination';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { index as usersIndex, role as roleRoute } from '@/routes/admin/users';
import type { Paginated } from '@/types';

type AdminUserRow = {
    id: number;
    name: string;
    email: string;
    role: string;
    created_at: string;
};

export default function AdminUsersIndex({
    users,
    filters,
    roles,
}: {
    users: Paginated<AdminUserRow>;
    filters: { q: string };
    roles: string[];
}) {
    function changeRole(user: AdminUserRow, newRole: string) {
        router.patch(
            roleRoute(user.id).url,
            { role: newRole },
            { preserveScroll: true },
        );
    }

    return (
        <>
            <Head title="Users" />
            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <div className="flex flex-wrap items-center justify-between gap-4">
                    <h1 className="text-xl font-semibold tracking-tight">
                        Users
                    </h1>
                    <Form action={usersIndex()} className="flex gap-2">
                        <Input
                            type="search"
                            name="q"
                            defaultValue={filters.q}
                            placeholder="Name or email…"
                            aria-label="Search users"
                            className="w-64"
                        />
                        <Button type="submit" variant="secondary">
                            Search
                        </Button>
                    </Form>
                </div>

                <div className="flex flex-col gap-6">
                    <div className="overflow-hidden rounded-xl border">
                        <table className="w-full text-sm">
                            <thead className="bg-muted/50 text-left">
                                <tr>
                                    <th className="px-4 py-3 font-medium">
                                        Name
                                    </th>
                                    <th className="px-4 py-3 font-medium">
                                        Email
                                    </th>
                                    <th className="px-4 py-3 font-medium">
                                        Joined
                                    </th>
                                    <th className="px-4 py-3 font-medium">
                                        Role
                                    </th>
                                </tr>
                            </thead>
                            <tbody className="divide-y">
                                {users.data.map((user) => (
                                    <tr
                                        key={user.id}
                                        className="hover:bg-muted/30"
                                    >
                                        <td className="px-4 py-3 font-medium">
                                            {user.name}
                                        </td>
                                        <td className="px-4 py-3 text-muted-foreground">
                                            {user.email}
                                        </td>
                                        <td className="px-4 py-3 text-muted-foreground">
                                            {user.created_at}
                                        </td>
                                        <td className="px-4 py-3">
                                            <div className="flex items-center gap-2">
                                                <Badge
                                                    variant={
                                                        user.role === 'admin'
                                                            ? 'default'
                                                            : 'secondary'
                                                    }
                                                >
                                                    {user.role}
                                                </Badge>
                                                <select
                                                    value={user.role}
                                                    aria-label={`Change role for ${user.name}`}
                                                    onChange={(event) =>
                                                        changeRole(
                                                            user,
                                                            event.target.value,
                                                        )
                                                    }
                                                    className="h-8 rounded-md border border-input bg-transparent px-2 text-sm shadow-xs"
                                                >
                                                    {roles.map((role) => (
                                                        <option
                                                            key={role}
                                                            value={role}
                                                        >
                                                            {role}
                                                        </option>
                                                    ))}
                                                </select>
                                            </div>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                    <Pagination links={users.links} />
                </div>
            </div>
        </>
    );
}
