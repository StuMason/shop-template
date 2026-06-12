<?php

namespace App\Mcp\Tools;

use App\Models\Order;
use App\Models\User;
use App\Support\Money;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Search customers by name or email. Returns account holders with order counts and lifetime value; also matches guest order emails.')]
class AdminSearchCustomers extends Tool
{
    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $query = trim((string) $request->get('query'));

        if ($query === '') {
            return Response::error('Provide a name or email fragment to search for.');
        }

        $accounts = User::query()
            ->where(fn (Builder $builder) => $builder
                ->whereLike('name', "%{$query}%", caseSensitive: false)
                ->orWhereLike('email', "%{$query}%", caseSensitive: false))
            ->withCount('orders')
            ->take(25)
            ->get()
            ->map(fn (User $user): array => [
                'type' => 'account',
                'name' => $user->name,
                'email' => $user->email,
                'orders' => $user->orders_count,
                'lifetime_value' => Money::format(
                    (int) Order::query()->where('user_id', $user->id)->sum('total'),
                ),
            ]);

        $guestEmails = DB::table('orders')
            ->whereNull('user_id')
            ->whereLike('email', "%{$query}%", caseSensitive: false)
            ->selectRaw('email, count(*) as orders, sum(total) as spent')
            ->groupBy('email')
            ->take(25)
            ->get()
            ->map(fn ($row): array => [
                'type' => 'guest',
                'email' => $row->email,
                'orders' => (int) $row->orders,
                'lifetime_value' => Money::format((int) $row->spent),
            ]);

        $results = $accounts->concat($guestEmails);

        if ($results->isEmpty()) {
            return Response::text('No customers match.');
        }

        return Response::json($results->values()->all());
    }

    /**
     * Get the tool's input schema.
     *
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'query' => $schema->string()->description('Name or email fragment.')->required(),
        ];
    }
}
