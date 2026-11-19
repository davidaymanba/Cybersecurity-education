<?php

namespace App\Services\Ai;

use GuzzleHttp\Client as GuzzleClient;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Talks to the LLM providers (Groq first, OpenAI as fallback), including Groq streaming.
 */
class AiProviderClient
{
    /**
     * @param  array<int, array{role: string, content: string}>  $messages
     * @return array{content: string, tokens: int}
     */
    public function resolveAiResponse(array $messages, string $fallbackContent, string $version): array
    {
        if ($groqApiKey = $this->groqApiKey($version)) {
            $groq = $this->callGroq($messages, $groqApiKey, $version);

            if ($groq['ok']) {
                return ['content' => $groq['content'], 'tokens' => $groq['tokens']];
            }

            $this->logProviderFailure('Groq failed; trying the next AI provider.', $groq);
        }

        if (config('services.openai.api_key')) {
            $openai = $this->callOpenAI($messages);

            if ($openai['ok']) {
                return ['content' => $openai['content'], 'tokens' => $openai['tokens']];
            }

            $this->logProviderFailure('OpenAI failed; using the local AI fallback response.', $openai);
        }

        return ['content' => $fallbackContent, 'tokens' => 0];
    }

    /**
     * @param  array<int, array{role: string, content: string}>  $messages
     * @return array{ok: bool, provider: string, content: string, tokens: int, status: int|null, error: string|null, body: string|null}
     */
    private function callGroq(array $messages, string $apiKey, string $version): array
    {
        try {
            $response = Http::withToken($apiKey)
                ->timeout(30)
                ->post('https://api.groq.com/openai/v1/chat/completions', [
                    'model' => config('services.groq.model'),
                    'messages' => $messages,
                    'temperature' => 0.7,
                    'max_tokens' => 1200,
                    'top_p' => 0.9,
                ]);
        } catch (\Throwable $exception) {
            return $this->failedProviderResult('groq-'.$version, null, $exception->getMessage());
        }

        return $this->providerResult('groq-'.$version, $response);
    }

    public function groqApiKey(string $version): ?string
    {
        $key = $version === 'multi'
            ? config('services.groq.multi_api_key')
            : config('services.groq.single_api_key');

        $key = trim((string) $key);

        if ($key === '') {
            $key = trim((string) config('services.groq.api_key'));
        }

        return $key !== '' ? $key : null;
    }

    /**
     * @param  array<int, array{role: string, content: string}>  $messages
     * @return array{ok: bool, provider: string, content: string, tokens: int, status: int|null, error: string|null, body: string|null}
     */
    public function callOpenAI(array $messages): array
    {
        try {
            $response = Http::withToken(config('services.openai.api_key'))
                ->timeout(25)
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => config('services.openai.model'),
                    'messages' => $messages,
                    'temperature' => 0.4,
                    'max_tokens' => 700,
                ]);
        } catch (\Throwable $exception) {
            return $this->failedProviderResult('openai', null, $exception->getMessage());
        }

        return $this->providerResult('openai', $response);
    }

    /**
     * @return array{ok: bool, provider: string, content: string, tokens: int, status: int|null, error: string|null, body: string|null}
     */
    private function providerResult(string $provider, Response $response): array
    {
        if (! $response->successful()) {
            return $this->failedProviderResult(
                $provider,
                $response->status(),
                'Provider returned a non-success status.',
                Str::limit($response->body(), 500),
            );
        }

        $payload = $response->json();
        $content = trim((string) data_get($payload, 'choices.0.message.content', ''));

        if ($content === '') {
            return $this->failedProviderResult($provider, $response->status(), 'Provider returned empty content.');
        }

        return [
            'ok' => true,
            'provider' => $provider,
            'content' => $content,
            'tokens' => (int) data_get($payload, 'usage.total_tokens', 0),
            'status' => $response->status(),
            'error' => null,
            'body' => null,
        ];
    }

    /**
     * @return array{ok: bool, provider: string, content: string, tokens: int, status: int|null, error: string|null, body: string|null}
     */
    private function failedProviderResult(string $provider, ?int $status, string $error, ?string $body = null): array
    {
        return [
            'ok' => false,
            'provider' => $provider,
            'content' => '',
            'tokens' => 0,
            'status' => $status,
            'error' => $error,
            'body' => $body,
        ];
    }

    /**
     * @param  array{provider: string, status: int|null, error: string|null, body: string|null}  $result
     */
    public function logProviderFailure(string $message, array $result): void
    {
        Log::warning($message, [
            'provider' => $result['provider'],
            'status' => $result['status'],
            'error' => $result['error'],
            'body' => $result['body'],
        ]);
    }

    /**
     * Connect to Groq's chat-completions endpoint with stream=true and call
     * $onToken for every text token received over the SSE connection.
     *
     * Returns ['ok' => bool, 'provider' => string, 'tokens' => int, 'error' => string|null].
     *
     * @param  array<int, array{role: string, content: string}>  $messages
     * @return array{ok: bool, provider: string, tokens: int, content: string, status: int|null, error: string|null, body: string|null}
     */
    public function streamFromGroq(array $messages, string $apiKey, callable $onToken): array
    {
        $fullContent = '';
        $totalTokens = 0;

        try {
            $client = new GuzzleClient(['timeout' => 60]);
            $response = $client->post('https://api.groq.com/openai/v1/chat/completions', [
                'stream' => true,
                'headers' => [
                    'Authorization' => 'Bearer '.$apiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => config('services.groq.model', 'llama-3.3-70b-versatile'),
                    'messages' => $messages,
                    'temperature' => 0.7,
                    'max_tokens' => 1200,
                    'top_p' => 0.9,
                    'stream' => true,
                ],
            ]);
        } catch (\Throwable $e) {
            return $this->failedProviderResult('groq-stream', null, $e->getMessage());
        }

        $body = $response->getBody();
        $buffer = '';

        while (! $body->eof()) {
            $chunk = $body->read(512);
            $buffer .= $chunk;

            // Process every complete SSE line.
            while (($pos = strpos($buffer, "\n")) !== false) {
                $line = trim(substr($buffer, 0, $pos));
                $buffer = substr($buffer, $pos + 1);

                if (! str_starts_with($line, 'data: ')) {
                    continue;
                }

                $json = substr($line, 6);

                if ($json === '[DONE]') {
                    break 2;
                }

                $parsed = json_decode($json, true);

                if (! is_array($parsed)) {
                    continue;
                }

                $token = (string) data_get($parsed, 'choices.0.delta.content', '');

                if ($token !== '') {
                    $fullContent .= $token;
                    $onToken($token);
                }

                if (isset($parsed['usage']['total_tokens'])) {
                    $totalTokens = (int) $parsed['usage']['total_tokens'];
                }
            }
        }

        if ($fullContent === '') {
            return $this->failedProviderResult('groq-stream', $response->getStatusCode(), 'Groq stream returned empty content.');
        }

        return [
            'ok' => true,
            'provider' => 'groq-stream',
            'content' => $fullContent,
            'tokens' => $totalTokens,
            'status' => $response->getStatusCode(),
            'error' => null,
            'body' => null,
        ];
    }
}
