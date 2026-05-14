<?php

declare(strict_types=1);

namespace App\Modules\Assistant\Support;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;

class AssistantServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $configPath = $this->moduleConfigPath();

        if (is_file($configPath)) {
            $this->mergeConfigFrom($configPath, 'ollama');
        }

        $this->app->singleton(OllamaClient::class, function (): OllamaClient {
            return new OllamaClient();
        });

        /*
         * Use bind instead of singleton during development so the provider
         * does not stay stuck on the deterministic fallback after config changes.
         */
        $this->app->bind(
            AssistantProviderInterface::class,
            fn (Application $app): AssistantProviderInterface => $this->makeAssistantProvider($app),
        );
    }

    public function boot(): void
    {
        $configPath = $this->moduleConfigPath();

        if ($this->app->runningInConsole() && is_file($configPath)) {
            $this->publishes([
                $configPath => config_path('ollama.php'),
            ], 'assistant-config');
        }
    }

    private function makeAssistantProvider(Application $app): AssistantProviderInterface
    {
        $enabled = (bool) config('ollama.enabled', false);
        $baseUrl = (string) config('ollama.base_url', '');
        $model = (string) config('ollama.model', '');

        Log::info('Resolving assistant provider.', [
            'ollama_enabled' => $enabled,
            'ollama_base_url' => $baseUrl,
            'ollama_model' => $model,
        ]);

        if (! $enabled) {
            Log::warning('Assistant provider resolved as deterministic because Ollama is disabled.');

            return $app->make(DeterministicAssistantProvider::class);
        }

        if ($baseUrl === '' || $model === '') {
            Log::warning('Assistant provider resolved as deterministic because Ollama config is incomplete.', [
                'ollama_base_url' => $baseUrl,
                'ollama_model' => $model,
            ]);

            return $app->make(DeterministicAssistantProvider::class);
        }

        /** @var OllamaClient $client */
        $client = $app->make(OllamaClient::class);

        if (! $client->isAvailable()) {
            Log::warning('Assistant provider resolved as deterministic because Ollama is unavailable.', [
                'ollama_base_url' => $baseUrl,
                'ollama_model' => $model,
            ]);

            return $app->make(DeterministicAssistantProvider::class);
        }

        Log::info('Assistant provider resolved as OllamaAssistantProvider.', [
            'ollama_base_url' => $baseUrl,
            'ollama_model' => $model,
        ]);

        return new OllamaAssistantProvider($client);
    }

    private function moduleConfigPath(): string
    {
        return __DIR__ . '/../config/ollama.php';
    }
}
