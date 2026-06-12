<?php

use App\Models\Order;
use App\Models\Ticket;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Illuminate\Support\Facades\Http;

it('drafts a reply when a customer opens a ticket', function () {
    config(['services.support_drafter.driver' => 'fake']);

    $user = User::factory()->create(['name' => 'Casey']);

    $this->actingAs($user)->post(route('account.tickets.store'), [
        'subject' => 'Where is my mug?',
        'body' => 'Ordered last week, nothing yet.',
    ]);

    $ticket = Ticket::query()->sole();

    expect($ticket->draft_reply)->toContain('Casey')
        ->and($ticket->draft_reply)->toContain('Where is my mug?');
});

it('does nothing when the drafter is off', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('account.tickets.store'), [
        'subject' => 'Hello',
        'body' => 'Just testing.',
    ]);

    expect(Ticket::query()->sole()->draft_reply)->toBeNull();
});

it('grounds anthropic drafts in real order data and clears on staff reply', function () {
    config([
        'services.support_drafter.driver' => 'anthropic',
        'services.anthropic.api_key' => 'test-key',
        'services.anthropic.model' => 'claude-test',
    ]);

    Http::fake([
        'api.anthropic.com/*' => Http::response([
            'content' => [['type' => 'text', 'text' => 'Hi! Your order shipped yesterday — tracking AB1. The team']],
        ]),
    ]);

    $this->seed(RolesSeeder::class);
    $user = User::factory()->create();
    Order::factory()->paid()->create([
        'user_id' => $user->id,
        'tracking_number' => 'AB1',
        'carrier' => 'Royal Mail',
    ]);

    $this->actingAs($user)->post(route('account.tickets.store'), [
        'subject' => 'Tracking?',
        'body' => 'Where is it?',
    ]);

    $ticket = Ticket::query()->sole();
    expect($ticket->draft_reply)->toContain('tracking AB1');

    // The request body carried the order context.
    Http::assertSent(fn ($request) => str_contains($request->body(), 'AB1')
        && str_contains($request->body(), 'Royal Mail'));

    // Staff reply consumes the draft.
    $staff = User::factory()->create();
    $staff->assignRole('staff');

    $this->actingAs($staff)->post(route('admin.tickets.reply', $ticket), [
        'body' => 'On its way!',
    ]);

    expect($ticket->fresh()->draft_reply)->toBeNull();
});
