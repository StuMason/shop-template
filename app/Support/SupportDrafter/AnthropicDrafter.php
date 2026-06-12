<?php

namespace App\Support\SupportDrafter;

use App\Models\Ticket;
use Illuminate\Support\Facades\Http;

class AnthropicDrafter implements SupportDrafter
{
    public function __construct(
        private readonly string $apiKey,
        private readonly string $model,
    ) {}

    public function draft(Ticket $ticket): ?string
    {
        $response = Http::timeout(60)->connectTimeout(5)
            ->withHeaders([
                'x-api-key' => $this->apiKey,
                'anthropic-version' => '2023-06-01',
            ])
            ->post('https://api.anthropic.com/v1/messages', [
                'model' => $this->model,
                'max_tokens' => 600,
                'system' => 'You draft support replies for a small online shop. '
                    .'Write a complete, warm, concise reply a staff member can send as-is. '
                    .'Only state facts present in the provided order data; if the answer is not in the data, '
                    .'say what you will check rather than inventing details. Sign off as "The team". '
                    .'Reply with the email body only.',
                'messages' => [[
                    'role' => 'user',
                    'content' => TicketContext::build($ticket),
                ]],
            ]);

        if ($response->failed()) {
            return null;
        }

        $text = $response->json('content.0.text');

        return is_string($text) && $text !== '' ? $text : null;
    }
}
