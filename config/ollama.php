<?php

return [
    'enabled' => env('OLLAMA_ENABLED', true),

    'base_url' => env('OLLAMA_BASE_URL', 'http://127.0.0.1:11434'),

    'model' => env('OLLAMA_MODEL', 'llama3.2:latest'),

    'timeout' => max(5, (int) env('OLLAMA_TIMEOUT', 30)),
];
