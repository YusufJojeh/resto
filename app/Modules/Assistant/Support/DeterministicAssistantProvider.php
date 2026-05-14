<?php

declare(strict_types=1);

namespace App\Modules\Assistant\Support;

class DeterministicAssistantProvider implements AssistantProviderInterface
{
    public function reply(array $messages): string
    {
        return '';
    }
}
