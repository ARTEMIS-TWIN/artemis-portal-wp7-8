<?php

namespace App\Services\Artemisia;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class MistralClient
{
    public function __construct(
        private readonly ?string $apiKey = null,
        private readonly ?string $baseUrl = null,
        private readonly ?string $largeModel = null,
        private readonly ?string $mediumModel = null,
        private readonly ?int $timeoutSeconds = null,
    ) {
    }

    /**
     * @param  list<array<string, string>>  $messages
     * @return array<string, mixed>
     */
    public function chat(array $messages, ?string $model = null, float $temperature = 0.2): array
    {
        if (!$this->isConfigured()) {
            throw new RuntimeException($this->provider() === 'ollama'
                ? 'Ollama is not configured.'
                : 'Mistral API credentials are not configured.');
        }

        if ($this->provider() === 'ollama') {
            return $this->chatViaOllama($messages, $model, $temperature);
        }

        try {
            $response = Http::timeout($this->timeout())
                ->acceptJson()
                ->withToken($this->apiKey())
                ->post($this->endpoint('/chat/completions'), [
                    'model' => $model ?: $this->largeModel(),
                    'messages' => $messages,
                    'temperature' => $temperature,
                ]);
        } catch (ConnectionException $exception) {
            throw new RuntimeException('Mistral API is unreachable: '.$exception->getMessage(), previous: $exception);
        }

        if ($response->failed()) {
            throw new RuntimeException('Mistral API request failed: '.$response->body());
        }

        return $response->json() ?? [];
    }

    public function extractText(array $response): string
    {
        $content = Arr::get($response, 'choices.0.message.content');

        if (is_string($content)) {
            return trim($content);
        }

        if (is_array($content)) {
            $parts = [];

            foreach ($content as $item) {
                if (is_string($item)) {
                    $parts[] = $item;
                    continue;
                }

                $text = is_array($item) ? ($item['text'] ?? null) : null;

                if (is_string($text) && $text !== '') {
                    $parts[] = $text;
                }
            }

            return trim(implode("\n", $parts));
        }

        $ollamaContent = Arr::get($response, 'message.content');
        if (is_string($ollamaContent)) {
            return trim($ollamaContent);
        }

        $generateContent = Arr::get($response, 'response');
        if (is_string($generateContent)) {
            return trim($generateContent);
        }

        return '';
    }

    public function mediumModel(): string
    {
        if ($this->provider() === 'ollama') {
            return (string) config('services.artemisia.ollama.model_medium', 'mistral');
        }

        return (string) ($this->mediumModel ?: config('services.mistral.model_medium', 'mistral-medium-latest'));
    }

    public function largeModel(): string
    {
        if ($this->provider() === 'ollama') {
            return (string) config('services.artemisia.ollama.model_large', 'mistral');
        }

        return (string) ($this->largeModel ?: config('services.mistral.model_large', 'mistral-large-latest'));
    }

    public function isConfigured(): bool
    {
        if ($this->provider() === 'ollama') {
            return $this->ollamaBaseUrl() !== '';
        }

        return $this->apiKey() !== '';
    }

    private function apiKey(): string
    {
        return (string) ($this->apiKey ?: config('services.mistral.api_key', ''));
    }

    private function endpoint(string $path): string
    {
        $base = rtrim((string) ($this->baseUrl ?: config('services.mistral.base_url', 'https://api.mistral.ai/v1')), '/');

        return $base.$path;
    }

    private function ollamaEndpoint(string $path): string
    {
        return rtrim($this->ollamaBaseUrl(), '/').$path;
    }

    private function ollamaBaseUrl(): string
    {
        return (string) config('services.artemisia.ollama.base_url', 'http://127.0.0.1:11434');
    }

    private function timeout(): int
    {
        if ($this->provider() === 'ollama') {
            return (int) config('services.artemisia.ollama.timeout', 120);
        }

        return (int) ($this->timeoutSeconds ?: config('services.mistral.timeout', 30));
    }

    private function provider(): string
    {
        $provider = (string) config('services.artemisia.llm_provider', 'mistral');

        return $provider === 'ollama' ? 'ollama' : 'mistral';
    }

    /**
     * @param  list<array<string, string>>  $messages
     * @return array<string, mixed>
     */
    private function chatViaOllama(array $messages, ?string $model = null, float $temperature = 0.2): array
    {
        try {
            $response = Http::timeout($this->timeout())
                ->acceptJson()
                ->post($this->ollamaEndpoint('/api/chat'), [
                    'model' => $model ?: $this->largeModel(),
                    'messages' => $messages,
                    'stream' => false,
                    'options' => [
                        'temperature' => $temperature,
                    ],
                ]);
        } catch (ConnectionException $exception) {
            throw new RuntimeException('Ollama is unreachable: '.$exception->getMessage(), previous: $exception);
        }

        if ($response->failed()) {
            throw new RuntimeException('Ollama request failed: '.$response->body());
        }

        return $response->json() ?? [];
    }
}
