<?php

declare(strict_types=1);

namespace App\Modules\Assistant\Support;

interface AssistantProviderInterface
{
    /**
     * @param array<int, array{role:string, content:string}> $messages
     */
    public function reply(array $messages): string;
}
