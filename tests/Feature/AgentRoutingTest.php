<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Ai\IntentDetector;
use App\Services\AiAgentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Regressions found by the live end-to-end agent run (2026-11-19).
 */
class AgentRoutingTest extends TestCase
{
    use RefreshDatabase;

    private function ask(string $message, string $agent = 'single_tutor', string $version = 'single'): array
    {
        config(['services.groq.api_key' => null, 'services.groq.single_api_key' => null,
            'services.groq.multi_api_key' => null, 'services.openai.api_key' => null]);
        Http::fake();

        return app(AiAgentService::class)->respond(User::factory()->create(), null, $message, $agent, $version);
    }

    public function test_requests_to_break_into_someone_elses_wifi_are_blocked(): void
    {
        $detector = app(IntentDetector::class);

        $this->assertTrue($detector->isUnsafeRequest("How do I hack into my neighbor's wifi password?"));
        $this->assertTrue($detector->isUnsafeRequest('كيف أخترق واي فاي الجيران؟'));
        $this->assertFalse($detector->isUnsafeRequest('How to protect my wifi from people who hack into it?'));
        $this->assertFalse($detector->isUnsafeRequest('What is a firewall?'));
    }

    public function test_guide_agent_accepts_what_to_study_next(): void
    {
        $response = $this->ask('What should I study next after this lesson?', 'navigation', 'multi');

        $this->assertStringNotContainsString('Switch to', $response['message']);
    }

    public function test_arabic_concepts_match_with_definite_article(): void
    {
        $this->assertStringContainsString('مبادئ كلمة المرور القوية', $this->ask('ما هي كلمة المرور القوية؟')['message']);
        $this->assertStringContainsString('الفرق بين التشفير والهاش', $this->ask('وش الفرق بين التشفير والهاش؟', 'explanation', 'multi')['message']);
    }

    public function test_every_concept_advertised_in_the_fallback_has_an_explanation(): void
    {
        $this->assertStringContainsString('Parameterized', $this->ask('Explain SQL injection and how developers prevent it')['message']);
        $this->assertStringContainsString('TLS', $this->ask('What is HTTPS?')['message']);
        $this->assertStringContainsString('encrypted tunnel', $this->ask('Explain what a VPN does')['message']);
        $this->assertStringContainsString('Symmetric', $this->ask('What is the difference between symmetric and asymmetric encryption?')['message']);
        $this->assertStringContainsString('backups', $this->ask('What is ransomware and how do companies defend against it?')['message']);
    }

    public function test_short_latin_terms_match_whole_words_only(): void
    {
        $detector = app(IntentDetector::class);

        $this->assertFalse($detector->containsAnyNormalized('this is especially useful', ['cia']));
        $this->assertTrue($detector->containsAnyNormalized('Explain the CIA triad', ['cia']));
    }

    public function test_short_groq_rate_limit_is_retried_once(): void
    {
        config(['services.groq.api_key' => 'k', 'services.groq.single_api_key' => null,
            'services.groq.model' => 'openai/gpt-oss-120b', 'services.openai.api_key' => null]);
        Http::fakeSequence('api.groq.com/*')
            ->push(['error' => ['message' => 'Rate limit reached. Please try again in 50ms.']], 429)
            ->push(['choices' => [['message' => ['content' => 'Answer after retry']]], 'usage' => ['total_tokens' => 9]]);

        $response = app(AiAgentService::class)->respond(User::factory()->create(), null, 'Explain DNS security basics.', 'single_tutor', 'single');

        $this->assertStringContainsString('Answer after retry', $response['message']);
        Http::assertSentCount(2);
        Http::assertSent(fn ($request) => ($request->data()['reasoning_effort'] ?? null) === 'low');
    }

    public function test_long_groq_rate_limit_falls_back_without_waiting(): void
    {
        config(['services.groq.api_key' => 'k', 'services.groq.single_api_key' => null, 'services.openai.api_key' => null]);
        Http::fake(['api.groq.com/*' => Http::response(['error' => ['message' => 'Please try again in 30s.']], 429)]);

        $started = microtime(true);
        app(AiAgentService::class)->respond(User::factory()->create(), null, 'Explain DNS security basics.', 'single_tutor', 'single');

        Http::assertSentCount(1);
        $this->assertLessThan(3, microtime(true) - $started);
    }

    public function test_reply_language_follows_the_latest_message(): void
    {
        config(['services.groq.api_key' => 'k', 'services.groq.single_api_key' => null, 'services.openai.api_key' => null]);
        Http::fake(['api.groq.com/*' => Http::response(['choices' => [['message' => ['content' => 'ok']]], 'usage' => ['total_tokens' => 5]])]);
        $service = app(AiAgentService::class);
        $user = User::factory()->create();

        $service->respond($user, null, 'Explain DNS security basics.', 'single_tutor', 'single', [
            ['role' => 'user', 'content' => 'السلام عليكم'],
        ]);
        $service->respond($user, null, 'اشرح لي أمن الشبكات اللاسلكية', 'single_tutor', 'single');
        $service->respond($user, null, 'A', 'single_tutor', 'single', [
            ['role' => 'user', 'content' => 'اسألني سؤال عن التشفير'],
            ['role' => 'assistant', 'content' => 'ok'],
        ]);

        $languages = collect(Http::recorded())
            ->map(fn ($pair) => preg_match('/Reply language: (\w+)/', $pair[0]->data()['messages'][0]['content'], $m) ? $m[1] : null)
            ->all();

        $this->assertSame(['English', 'Arabic', 'Arabic'], $languages);
    }

    public function test_name_is_not_prefixed_when_the_reply_already_greets_the_student(): void
    {
        config(['services.groq.api_key' => 'k', 'services.groq.single_api_key' => null, 'services.openai.api_key' => null]);
        Http::fake(['api.groq.com/*' => Http::sequence()
            ->push(['choices' => [['message' => ['content' => "**هلا يا سارة!**\nالـ VPN يسوي نفق مشفّر."]]], 'usage' => ['total_tokens' => 5]])
            ->push(['choices' => [['message' => ['content' => 'A VPN creates an encrypted tunnel.']]], 'usage' => ['total_tokens' => 5]]),
        ]);
        $user = User::factory()->create(['name' => 'Sara']);
        $service = app(AiAgentService::class);

        $greeted = $service->respond($user, null, 'وش الفرق بين الـ VPN والـ HTTPS؟', 'single_tutor', 'single');
        $plain = $service->respond($user, null, 'Explain what a VPN does', 'single_tutor', 'single');

        $this->assertStringStartsWith('**هلا يا سارة!**', $greeted['message']);
        $this->assertStringStartsWith("Sara,\n\nA VPN", $plain['message']);
    }
}
