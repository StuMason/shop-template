<?php

namespace App\Mcp\Tools;

use App\Models\Ticket;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('The open support ticket queue, oldest activity first, with the latest message from each thread.')]
class AdminOpenTickets extends Tool
{
    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $tickets = Ticket::query()
            ->where('status', 'open')
            ->with(['user:id,name,email', 'messages' => fn ($query) => $query->latest()->limit(1)])
            ->orderBy('last_message_at')
            ->take(50)
            ->get()
            ->map(fn (Ticket $ticket): array => [
                'id' => $ticket->id,
                'subject' => $ticket->subject,
                'customer' => $ticket->user->name,
                'email' => $ticket->user->email,
                'last_activity' => $ticket->last_message_at->toIso8601String(),
                'latest_message' => str($ticket->messages->first()->body ?? '')->limit(200)->toString(),
            ]);

        if ($tickets->isEmpty()) {
            return Response::text('The support queue is empty. 🎉');
        }

        return Response::json($tickets->all());
    }
}
