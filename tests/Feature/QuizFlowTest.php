<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\AiAgentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * In-chat quiz regressions found in the live run (2026-11-19): natural answers were not
 * understood and a student could get stuck re-prompted forever.
 */
class QuizFlowTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private array $history = [];

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.groq.api_key' => null, 'services.groq.single_api_key' => null, 'services.openai.api_key' => null]);
        Http::fake();
        $this->user = User::factory()->create(['name' => 'Sara']);
    }

    /** Sends a message the way the browser does: the history holds the exact replies we received. */
    private function say(string $message): string
    {
        $reply = app(AiAgentService::class)->respond($this->user, null, $message, 'single_tutor', 'single', $this->history)['message'];
        $this->history = array_slice([...$this->history, ['role' => 'user', 'content' => $message], ['role' => 'assistant', 'content' => $reply]], -20);

        return $reply;
    }

    public function test_natural_english_answers_are_graded(): void
    {
        $this->assertStringContainsString('What does DNS primarily do?', $this->say('Quiz me'));
        $this->assertStringContainsString('Correct. The answer is B', $this->say("I think it's B"));

        $this->assertStringContainsString('Which three properties make up the CIA triad?', $this->say('yes'));
        $this->assertStringContainsString('Not quite. The correct answer is B', $this->say('the answer is a'));

        $this->say('yes');
        $this->assertStringContainsString('Not quite', $this->say('Make passwords public'));
    }

    public function test_natural_arabic_answers_are_graded(): void
    {
        $this->assertStringContainsString('ما الوظيفة الأساسية لـ DNS؟', $this->say('اختبرني'));
        $this->assertStringContainsString('صحيح. الإجابة ب', $this->say('الإجابة ب'));

        $this->say('نعم');
        $this->assertStringContainsString('ليست صحيحة', $this->say('أظن أ'));
    }

    public function test_student_is_never_stuck_in_the_quiz(): void
    {
        $this->say('Quiz me');

        $this->assertStringContainsString('Please answer with A, B, C, or D', $this->say('hmm'));
        // A second non-answer leaves the quiz instead of repeating the re-prompt forever.
        $this->assertStringNotContainsString('Please answer with A, B, C, or D', $this->say('yes'));
        // A real question is answered even while a quiz is open.
        $this->assertStringContainsString('Firewall', $this->say('What is a firewall?'));
    }

    public function test_short_arabic_yes_no_words_do_not_match_inside_other_words(): void
    {
        $this->say('اختبرني');
        $this->say('ب');

        // "الامان" contains "لا" and "المفاهيم" contains "اه" — neither is a yes/no answer.
        $reply = $this->say('اشرح لي الامان في الشبكات');
        $this->assertStringNotContainsString('خلصنا الاختبار', $reply);
        $this->assertStringNotContainsString('اختبار سريع', $reply);
    }

    public function test_latin_letter_answer_in_an_arabic_quiz_is_graded_in_arabic(): void
    {
        $this->say('اختبرني');

        $this->assertStringContainsString('صحيح. الإجابة ب', $this->say('B'));
    }
}
