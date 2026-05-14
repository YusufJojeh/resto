<?php

return [
    'messages' => [
        'enabled' => (bool) env('FEATURE_MESSAGES_ENABLED', true),
    ],
    'notifications' => [
        'enabled' => (bool) env('FEATURE_NOTIFICATIONS_ENABLED', true),
    ],
    'assistant' => [
        'enabled' => (bool) env('FEATURE_ASSISTANT_ENABLED', env('ASSISTANT_ENABLED', true)),
    ],
    'realtime' => [
        'enabled' => (bool) env('FEATURE_REALTIME_ENABLED', true),
    ],
];
