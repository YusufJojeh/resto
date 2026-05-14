<?php

return [
    'enabled' => (bool) env('ASSISTANT_ENABLED', env('FEATURE_ASSISTANT_ENABLED', true)),

    'history_limit' => max(1, (int) env('ASSISTANT_HISTORY_LIMIT', 8)),

    'rate_limit' => max(1, (int) env('ASSISTANT_RATE_LIMIT', 30)),

    'max_prompt_length' => max(100, (int) env('ASSISTANT_MAX_PROMPT_LENGTH', 5000)),

    'guard' => [
        'enabled' => (bool) env('ASSISTANT_GUARD_ENABLED', true),
    ],
];
