<?php

namespace Tests\Feature;

use App\Models\Lesson;
use App\Models\Section;
use App\Models\User;
use App\Models\VideoResource;
use App\Services\AiAgentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class AiAgentServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.groq.api_key' => null,
            'services.openai.api_key' => null,
        ]);
    }

    public function test_blocks_direct_harmful_intent_without_instructional_phrase(): void
    {
        config([
            'services.groq.api_key' => 'groq-test-key',
            'services.openai.api_key' => 'openai-test-key',
        ]);

        Http::fake();

        $user = User::factory()->create();
        RateLimiter::clear("ai:{$user->id}");

        $response = app(AiAgentService::class)->respond(
            $user,
            null,
            'phishing kit',
            'single_tutor',
            'single',
        );

        $this->assertStringContainsString('I cannot help', $response['message']);
        $this->assertDatabaseHas('ai_interactions', [
            'user_id' => $user->id,
            'prompt' => 'phishing kit',
            'tokens_used' => 0,
        ]);

        Http::assertNothingSent();
    }

    public function test_falls_back_to_openai_when_groq_fails(): void
    {
        config([
            'services.groq.api_key' => 'groq-test-key',
            'services.groq.model' => 'groq-test-model',
            'services.openai.api_key' => 'openai-test-key',
            'services.openai.model' => 'openai-test-model',
        ]);

        Http::fake([
            'api.groq.com/*' => Http::response(['error' => 'provider unavailable'], 500),
            'api.openai.com/*' => Http::response([
                'choices' => [
                    ['message' => ['content' => 'OpenAI fallback answer']],
                ],
                'usage' => ['total_tokens' => 42],
            ]),
        ]);

        $user = User::factory()->create();
        RateLimiter::clear("ai:{$user->id}");

        $response = app(AiAgentService::class)->respond(
            $user,
            null,
            'Explain DNS security basics.',
            'single_tutor',
            'single',
        );

        $this->assertSame('OpenAI fallback answer', $response['message']);
        $this->assertDatabaseHas('ai_interactions', [
            'user_id' => $user->id,
            'response' => 'OpenAI fallback answer',
            'tokens_used' => 42,
        ]);

        Http::assertSentCount(2);
        Http::assertSent(fn ($request) => str_contains($request->url(), 'api.groq.com'));
        Http::assertSent(fn ($request) => str_contains($request->url(), 'api.openai.com'));
    }

    public function test_safe_concept_question_about_attack_name_is_not_blocked(): void
    {
        Http::fake();

        $user = User::factory()->create();
        RateLimiter::clear("ai:{$user->id}");

        $response = app(AiAgentService::class)->respond(
            $user,
            null,
            'What is credential stuffing?',
            'single_tutor',
            'single',
        );

        $this->assertStringNotContainsString('I cannot help', $response['message']);
        $this->assertStringContainsString('study-safe explanation', $response['message']);

        Http::assertNothingSent();
    }

    public function test_navigation_agent_redirects_concept_questions_to_tutor(): void
    {
        config([
            'services.groq.api_key' => 'groq-test-key',
            'services.openai.api_key' => 'openai-test-key',
        ]);

        Http::fake();

        $user = User::factory()->create();
        RateLimiter::clear("ai:{$user->id}");

        $response = app(AiAgentService::class)->respond(
            $user,
            null,
            'Explain DNS in simple terms.',
            'navigation',
            'multi',
        );

        $this->assertStringContainsString('Guide agent', $response['message']);
        $this->assertStringContainsString('Tutor', $response['message']);
        Http::assertNothingSent();
    }

    public function test_tutor_agent_redirects_plan_requests_to_guide(): void
    {
        config([
            'services.groq.api_key' => 'groq-test-key',
            'services.openai.api_key' => 'openai-test-key',
        ]);

        Http::fake();

        $user = User::factory()->create();
        RateLimiter::clear("ai:{$user->id}");

        $response = app(AiAgentService::class)->respond(
            $user,
            null,
            'Build me a weekly cybersecurity roadmap.',
            'explanation',
            'multi',
        );

        $this->assertStringContainsString('Tutor agent', $response['message']);
        $this->assertStringContainsString('Guide', $response['message']);
        Http::assertNothingSent();
    }

    public function test_video_agent_fallback_recommends_only_approved_lesson_videos(): void
    {
        Http::fake();

        $section = Section::create([
            'title' => 'Foundations',
            'slug' => 'foundations',
        ]);

        $lesson = Lesson::create([
            'section_id' => $section->id,
            'title' => 'DNS Security',
            'slug' => 'dns-security',
            'summary' => 'Learn DNS security basics.',
            'content' => '<p>DNS turns names into IP addresses.</p>',
        ]);

        VideoResource::create([
            'lesson_id' => $lesson->id,
            'title' => 'Approved DNS Defense',
            'youtube_id' => 'approved123',
            'channel_name' => 'Cyber Academy',
            'approved' => true,
        ]);

        VideoResource::create([
            'lesson_id' => $lesson->id,
            'title' => 'Unapproved DNS Video',
            'youtube_id' => 'blocked123',
            'channel_name' => 'Random Channel',
            'approved' => false,
        ]);

        $user = User::factory()->create();
        RateLimiter::clear("ai:{$user->id}");

        $response = app(AiAgentService::class)->respond(
            $user,
            $lesson,
            'Recommend the approved videos for this lesson.',
            'video',
            'multi',
        );

        $this->assertStringContainsString('Approved DNS Defense', $response['message']);
        $this->assertStringContainsString('approved123', $response['message']);
        $this->assertStringNotContainsString('Unapproved DNS Video', $response['message']);
        Http::assertNothingSent();
    }

    public function test_service_rate_limit_blocks_provider_calls(): void
    {
        config([
            'services.groq.api_key' => 'groq-test-key',
            'services.openai.api_key' => 'openai-test-key',
        ]);

        Http::fake();

        $user = User::factory()->create();
        RateLimiter::clear("ai:{$user->id}");

        for ($i = 0; $i < 20; $i++) {
            RateLimiter::hit("ai:{$user->id}", 60);
        }

        $response = app(AiAgentService::class)->respond(
            $user,
            null,
            'Explain network segmentation.',
            'single_tutor',
            'single',
        );

        $this->assertStringContainsString('temporary AI message limit', $response['message']);
        $this->assertDatabaseHas('ai_interactions', [
            'user_id' => $user->id,
            'prompt' => 'Explain network segmentation.',
            'tokens_used' => 0,
        ]);

        Http::assertNothingSent();
    }
}
