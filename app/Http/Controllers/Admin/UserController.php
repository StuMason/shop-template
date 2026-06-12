<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    /**
     * User management list (admin only).
     */
    public function index(Request $request): Response
    {
        $search = $request->string('q')->trim()->toString();

        $users = User::query()
            ->with('roles:id,name')
            ->when($search !== '', fn (Builder $query) => $query
                ->whereLike('name', "%{$search}%", caseSensitive: false)
                ->orWhereLike('email', "%{$search}%", caseSensitive: false))
            ->latest()
            ->paginate(20)
            ->withQueryString()
            ->through(fn (User $user): array => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->roles->pluck('name')->first() ?? 'customer',
                'created_at' => $user->created_at?->format('j M Y'),
            ]);

        return Inertia::render('admin/users/index', [
            'users' => $users,
            'filters' => ['q' => $search],
            'roles' => ['admin', 'staff', 'customer'],
        ]);
    }

    /**
     * Change a user's role.
     */
    public function updateRole(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'role' => ['required', Rule::in(['admin', 'staff', 'customer'])],
        ]);

        if ($user->is($request->user()) && $validated['role'] !== 'admin') {
            throw ValidationException::withMessages([
                'role' => "You can't remove your own admin access.",
            ]);
        }

        $user->syncRoles([$validated['role']]);

        return back()->with('success', "{$user->name} is now {$validated['role']}.");
    }
}
