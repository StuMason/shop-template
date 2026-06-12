<?php

namespace App\Http\Controllers\Account;

use App\Enums\TicketStatus;
use App\Http\Controllers\Controller;
use App\Jobs\DraftTicketReply;
use App\Models\Ticket;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TicketController extends Controller
{
    /**
     * The customer's support tickets.
     */
    public function index(Request $request): Response
    {
        $tickets = $request->user()
            ->tickets()
            ->latest('last_message_at')
            ->get()
            ->map(fn (Ticket $ticket): array => [
                'id' => $ticket->id,
                'subject' => $ticket->subject,
                'status' => $ticket->status->value,
                'last_message_at' => $ticket->last_message_at->diffForHumans(),
            ]);

        return Inertia::render('account/support/index', [
            'tickets' => $tickets,
            'orderNumbers' => $request->user()->orders()->latest('placed_at')->pluck('number'),
        ]);
    }

    /**
     * Open a new ticket.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'subject' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:5000'],
            'order_number' => ['nullable', 'string', 'exists:orders,number'],
        ]);

        $order = $validated['order_number'] ?? null
            ? $request->user()->orders()->firstWhere('number', $validated['order_number'])
            : null;

        $ticket = $request->user()->tickets()->create([
            'subject' => $validated['subject'],
            'order_id' => $order?->id,
            'status' => TicketStatus::Open,
            'last_message_at' => now(),
        ]);

        $ticket->messages()->create([
            'user_id' => $request->user()->id,
            'is_staff_reply' => false,
            'body' => $validated['body'],
        ]);

        DraftTicketReply::dispatch($ticket);

        return to_route('account.tickets.show', $ticket)->with('success', 'Ticket opened — we\'ll get back to you.');
    }

    /**
     * A single ticket thread.
     */
    public function show(Request $request, Ticket $ticket): Response
    {
        abort_unless($ticket->user_id === $request->user()->id, 404);

        return Inertia::render('account/support/show', [
            'ticket' => $this->ticketPayload($ticket),
        ]);
    }

    /**
     * Reply to a ticket (reopens it if closed).
     */
    public function reply(Request $request, Ticket $ticket): RedirectResponse
    {
        abort_unless($ticket->user_id === $request->user()->id, 404);

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
        ]);

        $ticket->messages()->create([
            'user_id' => $request->user()->id,
            'is_staff_reply' => false,
            'body' => $validated['body'],
        ]);

        $ticket->update(['status' => TicketStatus::Open, 'last_message_at' => now(), 'draft_reply' => null]);

        DraftTicketReply::dispatch($ticket);

        return back();
    }

    /**
     * @return array<string, mixed>
     */
    protected function ticketPayload(Ticket $ticket): array
    {
        $ticket->loadMissing('messages.author', 'order');

        return [
            'id' => $ticket->id,
            'subject' => $ticket->subject,
            'status' => $ticket->status->value,
            'order_number' => $ticket->order?->number,
            'messages' => $ticket->messages->map(fn ($message): array => [
                'id' => $message->id,
                'body' => $message->body,
                'author' => $message->is_staff_reply ? 'Support team' : $message->author->name,
                'is_staff_reply' => $message->is_staff_reply,
                'created_at' => $message->created_at?->format('j M Y, H:i'),
            ])->all(),
        ];
    }
}
