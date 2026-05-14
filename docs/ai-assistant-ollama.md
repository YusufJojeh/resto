# AI Assistant with Ollama

## Active Components

- `App\Modules\Assistant\Support\AssistantServiceProvider`
- `App\Modules\Assistant\Support\OllamaAssistantProvider`
- `App\Modules\Assistant\Support\DeterministicAssistantProvider`
- `App\Modules\Assistant\Support\OllamaClient`
- `App\Modules\Assistant\Support\AssistantRuntime`

`AssistantRuntime` owns intent detection, safe context building, prompt guarding, fallback behavior, and response policy. `OllamaAssistantProvider` only passes prepared chat messages to `OllamaClient`.

## Real Configuration

Source of truth: [config/ollama.php](/c:/Users/Yusuf/OneDrive/Desktop/restcaf/config/ollama.php:1)

```php
return [
    'enabled' => env('OLLAMA_ENABLED', true),
    'base_url' => env('OLLAMA_BASE_URL', 'http://127.0.0.1:11434'),
    'model' => env('OLLAMA_MODEL', 'llama3.2:latest'),
    'timeout' => max(5, (int) env('OLLAMA_TIMEOUT', 30)),
];
```

Supported environment variables:

- `OLLAMA_ENABLED`
- `OLLAMA_BASE_URL`
- `OLLAMA_MODEL`
- `OLLAMA_TIMEOUT`

## Runtime Behavior

- If Ollama is disabled, misconfigured, unavailable, empty, or throws, the app falls back to `DeterministicAssistantProvider`.
- `DeterministicAssistantProvider` returns an empty provider response so `AssistantRuntime` can generate a safe user-facing fallback.
- The provider receives:
  - one system instruction
  - one system message containing serialized safe context
  - recent assistant conversation history limited by `assistant.history_limit`

## OllamaClient Surface

Source of truth: [app/Modules/Assistant/Support/OllamaClient.php](/c:/Users/Yusuf/OneDrive/Desktop/restcaf/app/Modules/Assistant/Support/OllamaClient.php:1)

Implemented methods:

- `isAvailable(): bool`
- `chat(array $messages): string`

`isAvailable()` checks `GET {OLLAMA_BASE_URL}/api/tags`.

`chat()` sends `POST {OLLAMA_BASE_URL}/api/chat` with:

```json
{
  "model": "configured model",
  "stream": false,
  "messages": [
    { "role": "system|user|assistant", "content": "..." }
  ]
}
```

## Setup

1. Install Ollama from `https://ollama.ai`.
2. Start the server:

```bash
ollama serve
```

3. Pull the configured model, for example:

```bash
ollama pull llama3.2:latest
```

4. Configure the app:

```env
OLLAMA_ENABLED=true
OLLAMA_BASE_URL=http://127.0.0.1:11434
OLLAMA_MODEL=llama3.2:latest
OLLAMA_TIMEOUT=30
```

## Verification

Minimal runtime check:

```php
$client = app(\App\Modules\Assistant\Support\OllamaClient::class);

if ($client->isAvailable()) {
    echo 'Ollama is reachable.';
}
```

Minimal chat check:

```php
$client = app(\App\Modules\Assistant\Support\OllamaClient::class);

$reply = $client->chat([
    ['role' => 'system', 'content' => 'Reply with one short sentence.'],
    ['role' => 'user', 'content' => 'Hello'],
]);
```

## Notes

- There are no multiple model slots in live config.
- There is no `OLLAMA_HOST` setting in live config.
- There are no supported client methods named `getAvailableModels()`, `generate()`, `generateStream()`, or `setModel()`.
- The assistant remains read-only; Ollama is used only for reply generation from safe context.
