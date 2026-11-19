<?php

namespace App\Services\Ai;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Talks to the LLM providers (Groq first, OpenAI as fallback), including Groq streaming.
 */
class AiProviderClient
{
    private const GROQ_URL = 'https://api.groq.com/openai/v1/chat/completions';

    /** On a 429 asking us to wait at most this long, wait once and retry instead of falling back. */
    private const MAX_RATE_LIMIT_WAIT_SECONDS = 6.0;

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
        $send = fn () => Http::withToken($apiKey)->timeout(30)->post(self::GROQ_URL, $this->groqPayload($messages));

        try {
            $response = $send();

            if ($response->status() === 429 && ($wait = $this->rateLimitWaitSeconds($response->header('retry-after'), $response->body())) !== null) {
                usleep((int) ($wait * 1_000_000));
                $response = $send();
            }
        } catch (\Throwable $exception) {
            return $this->failedProviderResult('groq-'.$version, null, $exception->getMessage());
        }

        return $this->providerResult('groq-'.$version, $response);
    }

    /**
     * Shared Groq request body. gpt-oss models are reasoning models: low effort keeps
     * hidden reasoning tokens (which count against the per-minute token limit) small.
     *
     * @param  array<int, array{role: string, content: string}>  $messages
     */
    private function groqPayload(array $messages, bool $stream = false): array
    {
        $model = (string) config('services.groq.model', 'openai/gpt-oss-120b');

        $payload = [
            'model' => $model,
            'messages' => $messages,
            'temperature' => 0.7,
            'max_tokens' => 1200,
            'top_p' => 0.9,
        ];

        if (str_starts_with($model, 'openai/gpt-oss')) {
            $payload['reasoning_effort'] = 'low';
        }

        if ($stream) {
            $payload['stream'] = true;
            $payload['stream_options'] = ['include_usage' => true];
        }

        return $payload;
    }

    /**
     * Seconds to wait before retrying a rate-limited request, or null when the wait is too long
     * (then we fall back immediately instead of keeping the student waiting).
     */
    private function rateLimitWaitSeconds(?string $retryAfter, string $body): ?float
    {
        $seconds = is_numeric($retryAfter) ? (float) $retryAfter : null;

        if ($seconds === null && preg_match('/try again in ([0-9.]+)(ms|s)/i', $body, $m)) {
            $seconds = strtolower($m[2]) === 'ms' ? (float) $m[1] / 1000 : (float) $m[1];
        }

        if ($seconds === null || $seconds > self::MAX_RATE_LIMIT_WAIT_SECONDS) {
            return null;
        }

        return max(0.2, $seconds + 0.2);
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

        $client = new GuzzleClient(['timeout' => 60]);
        $send = fn () => $client->post(self::GROQ_URL, [
            'stream' => true,
            'headers' => [
                'Authorization' => 'Bearer '.$apiKey,
                'Content-Type' => 'application/json',
            ],
            'json' => $this->groqPayload($messages, stream: true),
        ]);

        try {
            try {
                $response = $send();
            } catch (ClientException $e) {
                // Nothing has been streamed yet, so a short rate-limit wait + one retry is safe.
                $rateLimited = $e->getResponse()->getStatusCode() === 429;
                $wait = $rateLimited
                    ? $this->rateLimitWaitSeconds($e->getResponse()->getHeaderLine('retry-after'), (string) $e->getResponse()->getBody())
                    : null;

                if ($wait === null) {
                    throw $e;
                }

                usleep((int) ($wait * 1_000_000));
                $response = $send();
            }
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

                // OpenAI-style `usage` (stream_options.include_usage) or Groq's `x_groq.usage`.
                $usage = data_get($parsed, 'usage.total_tokens') ?? data_get($parsed, 'x_groq.usage.total_tokens');

                if ($usage !== null) {
                    $totalTokens = (int) $usage;
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
