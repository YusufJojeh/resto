<?php

namespace App\Modules\Assistant\Support;

class AssistantManager
{
    public function provider(): AssistantProviderInterface
    {
        return app(AssistantProviderInterface::class);
    }
}
