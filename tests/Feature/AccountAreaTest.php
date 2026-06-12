<?php

use App\Models\Address;
use App\Models\Order;
use App\Models\Ticket;
use App\Models\User;
use App\Notifications\OrderPaidNotification;
use App\Notifications\TicketReplyNotification;
use Database\Seeders\RolesSeeder;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    $this->seed(RolesSeeder::class);
    $this->user = User::factory()->create();
});

it('manages the address book with single defaults', function () {
    $payload = [
        'name' => 'Test User',
        'line1' => '1 High Street',
        'city' => 'Bristol',
        'postcode' => 'BS1 1AA',
        'country' => 'GB',
        'is_default_shipping' => true,
    ];

    $this->actingAs($this->user)->post(route('account.addresses.store'), $payload)->assertRedirect();
    $first = $this->user->addresses()->sole();

    $this->actingAs($this->user)->post(route('account.addresses.store'), [
        ...$payload,
        'line1' => '2 Low Street',
    ])->assertRedirect();

    expect($first->fresh()->is_default_shipping)->toBeFalse()
        ->and($this->user->addresses()->where('is_default_shipping', true)->count())->toBe(1);
});

it('stops users editing addresses they do not own', function () {
    $foreign = Address::factory()->create();

    $this->actingAs($this->user)
        ->put(route('account.addresses.update', $foreign), [
            'name' => 'Hacker',
            'line1' => 'x',
            'city' => 'x',
            'postcode' => 'x',
            'country' => 'GB',
        ])
        ->assertNotFound();
});

it('lists notifications and marks them read', function () {
    $order = Order::factory()->paid()->create(['user_id' => $this->user->id]);
    $this->user->notify(new OrderPaidNotification($order));

    $this->actingAs($this->user)
        ->get(route('account.notifications.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('unreadCount', 1));

    $this->actingAs($this->user)
        ->post(route('account.notifications.read-all'))
        ->assertRedirect();

    expect($this->user->unreadNotifications()->count())->toBe(0);
});

it('opens a ticket with a first message', function () {
    $this->actingAs($this->user)
        ->post(route('account.tickets.store'), [
            'subject' => 'Where is my mug?',
            'body' => 'It has been a week.',
        ])
        ->assertRedirect();

    $ticket = Ticket::query()->sole();

    expect($ticket->subject)->toBe('Where is my mug?')
        ->and($ticket->messages)->toHaveCount(1)
        ->and($ticket->isOpen())->toBeTrue();
});

it('keeps other people out of a ticket thread', function () {
    $ticket = Ticket::factory()->create();

    $this->actingAs($this->user)
        ->get(route('account.tickets.show', $ticket))
        ->assertNotFound();
});

it('lets staff reply, notifying the customer, and close tickets', function () {
    Notification::fake();

    $staff = User::factory()->create();
    $staff->assignRole('staff');

    $ticket = Ticket::factory()->create(['user_id' => $this->user->id]);

    $this->actingAs($staff)
        ->post(route('admin.tickets.reply', $ticket), ['body' => 'On its way!'])
        ->assertRedirect();

    expect($ticket->messages()->where('is_staff_reply', true)->count())->toBe(1);

    Notification::assertSentTo($this->user, TicketReplyNotification::class);

    $this->actingAs($staff)
        ->patch(route('admin.tickets.status', $ticket), ['status' => 'closed'])
        ->assertRedirect();

    expect($ticket->fresh()->isOpen())->toBeFalse();
});

it('reopens a closed ticket when the customer replies', function () {
    $ticket = Ticket::factory()->closed()->create(['user_id' => $this->user->id]);

    $this->actingAs($this->user)
        ->post(route('account.tickets.reply', $ticket), ['body' => 'Still broken!'])
        ->assertRedirect();

    expect($ticket->fresh()->isOpen())->toBeTrue();
});

it('keeps customers out of the admin support queue', function () {
    $this->actingAs($this->user)
        ->get(route('admin.tickets.index'))
        ->assertForbidden();
});
