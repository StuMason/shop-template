<?php

namespace App\Http\Controllers\Admin;

use App\Enums\TicketStatus;
use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Notifications\TicketReplyNotification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TicketController extends Controller
{
    /**
     * Support queue.
     */
    public function index(Request $request): Response
    {
        $status = $request->string('status', 'open')->toString();

        $tickets = Ticket::query()
            ->with('user:id,name,email')
            ->when($status !== '', fn (Builder $query) => $query->where('status', $status))
            ->latest('last_message_at')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (Ticket $ticket): array => [
                'id' => $ticket->id,
                'subject' => $ticket->subject,
                'status' => $ticket->status->value,
                'customer' => $ticket->user->name,
                'email' => $ticket->user->email,
                'last_message_at' => $ticket->last_message_at->diffForHumans(),
            ]);

        return Inertia::render('admin/support/index', [
            'tickets' => $tickets,
            'filters' => ['status' => $status],
        ]);
    }

    /**
     * A ticket thread, staff view.
     */
    public function show(Ticket $ticket): Response
    {
        $ticket->loadMissing('messages.author', 'order', 'user');

        return Inertia::render('admin/support/show', [
            'ticket' => [
                'id' => $ticket->id,
                'subject' => $ticket->subject,
                'status' => $ticket->status->value,
                'customer' => $ticket->user->name,
                'email' => $ticket->user->email,
                'order_number' => $ticket->order?->number,
                'order_id' => $ticket->order?->id,
                'messages' => $ticket->messages->map(fn ($message): array => [
                    'id' => $message->id,
                    'body' => $message->body,
                    'author' => $message->author->name,
                    'is_staff_reply' => $message->is_staff_reply,
                    'created_at' => $message->created_at?->format('j M Y, H:i'),
                ])->all(),
            ],
        ]);
    }

    /**
     * Staff reply; notifies the customer.
     */
    public function reply(Request $request, Ticket $ticket): RedirectResponse
    {
        $validated = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
        ]);

        $ticket->messages()->create([
            'user_id' => $request->user()->id,
            'is_staff_reply' => true,
            'body' => $validated['body'],
        ]);

        $ticket->update(['status' => TicketStatus::Open, 'last_message_at' => now()]);

        $ticket->user->notify(new TicketReplyNotification($ticket));

        return back()->with('success', 'Reply sent.');
    }

    /**
     * Toggle a ticket open/closed.
     */
    public function updateStatus(Request $request, Ticket $ticket): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:open,closed'],
        ]);

        $ticket->update(['status' => TicketStatus::from($validated['status'])]);

        return back()->with('success', "Ticket {$validated['status']}.");
    }
}
