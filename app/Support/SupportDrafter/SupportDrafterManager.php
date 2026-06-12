<?php

namespace App\Support\SupportDrafter;

use Illuminate\Support\Manager;

/**
 * Same swappable shape as payments and address lookup. SUPPORT_DRAFTER:
 * none (default) | anthropic | fake.
 */
class SupportDrafterManager extends Manager
{
    public function enabled(): bool
    {
        return $this->getDefaultDriver() !== 'none';
    }

    public function getDefaultDriver(): string
    {
        return (string) ($this->config->get('services.support_drafter.driver') ?? 'none');
    }

    protected function createAnthropicDriver(): SupportDrafter
    {
        return new AnthropicDrafter(
            apiKey: (string) $this->config->get('services.anthropic.api_key'),
            model: (string) $this->config->get('services.anthropic.model'),
        );
    }

    protected function createFakeDriver(): SupportDrafter
    {
        return new FakeDrafter;
    }
}
