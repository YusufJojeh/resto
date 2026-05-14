<?php

declare(strict_types=1);

namespace App\Modules\Assistant\Support;

class OllamaAssistantProvider implements AssistantProviderInterface
{
    public function __construct(
        private readonly OllamaClient $client,
    ) {}

    public function reply(array $messages): string
    {
        return trim($this->client->chat($messages));
    }
}
