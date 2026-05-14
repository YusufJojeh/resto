<?php

declare(strict_types=1);

namespace App\Modules\Assistant\Support;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class OllamaClient
{
    public function isAvailable(): bool
    {
        $baseUrl = $this->baseUrl();
        $model = $this->model();

        if ($baseUrl === '' || $model === '') {
            return false;
        }

        try {
            $response = Http::timeout(5)
                ->acceptJson()
                ->get($baseUrl . '/api/tags');

            if (! $response->successful()) {
                return false;
            }

            $models = collect($response->json('models', []))
                ->pluck('name')
                ->filter()
                ->values();

            if ($models->isEmpty()) {
                return true;
            }

            return $models->contains($model)
                || $models->contains($model . ':latest')
                || $models->contains($this->normalizeLatestModel($model));
        } catch (Throwable $e) {
            Log::warning('ollama.availability_failed', [
                'message' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * @param array<int, array{role:string, content:string}> $messages
     */
    public function chat(array $messages): string
    {
        $response = Http::timeout($this->timeout())
            ->acceptJson()
            ->post($this->baseUrl() . '/api/chat', [
                'model' => $this->model(),
                'stream' => false,
                'messages' => $messages,
            ]);

        if (! $response->successful()) {
            Log::error('ollama.chat_failed', [
                'status' => $response->status(),
                'body' => mb_substr($response->body(), 0, 1000),
            ]);

            throw new ConnectionException('Ollama request failed with status ' . $response->status());
        }

        $content = $response->json('message.content');

        if (! is_string($content) || trim($content) === '') {
            Log::error('ollama.empty_response', [
                'body' => mb_substr($response->body(), 0, 1000),
            ]);

            throw new ConnectionException('Ollama returned empty response.');
        }

        return trim($content);
    }

    private function baseUrl(): string
    {
        return rtrim((string) config('ollama.base_url', 'http://127.0.0.1:11434'), '/');
    }

    private function model(): string
    {
        return (string) config('ollama.model', 'llama3.2:latest');
    }

    private function timeout(): int
    {
        return max(5, (int) config('ollama.timeout', 120));
    }

    private function normalizeLatestModel(string $model): string
    {
        if (str_contains($model, ':')) {
            return $model;
        }

        return $model . ':latest';
    }
}
