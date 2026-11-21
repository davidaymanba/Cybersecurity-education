<?php

namespace Tests\Feature;

use App\Models\Bookmark;
use App\Models\Lesson;
use App\Models\Quiz;
use App\Models\User;
use App\Models\VideoResource;
use App\Support\LocalizedText;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentPagesTest extends TestCase
{
    use RefreshDatabase;

    private User $student;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->student = User::where('email', 'student@cyberlearn.test')->firstOrFail();
    }

    public function test_bookmarks_page_is_html_for_browsers_and_json_for_api_callers(): void
    {
        $lesson = Lesson::firstOrFail();
        Bookmark::create(['user_id' => $this->student->id, 'lesson_id' => $lesson->id]);
        $this->actingAs($this->student);

        $this->get(route('bookmarks.index'))->assertOk()->assertSee($lesson->title)->assertSee(__('messages.bookmarks'));
        $this->getJson(route('bookmarks.index'))->assertOk()->assertJsonPath('0.id', $lesson->id)->assertJsonPath('0.slug', $lesson->slug);
    }

    public function test_lesson_page_shows_saved_state_and_only_approved_videos(): void
    {
        $lesson = Lesson::firstOrFail();
        VideoResource::create(['lesson_id' => $lesson->id, 'title' => 'Unreviewed upload', 'youtube_id' => 'zzzzzzzzzzz', 'channel_name' => 'X', 'approved' => false]);
        Bookmark::create(['user_id' => $this->student->id, 'lesson_id' => $lesson->id]);

        $this->actingAs($this->student)->get(route('lessons.show', ['version' => 'multi', 'lesson' => $lesson]))->assertOk()
            ->assertSee('aria-pressed="true"', false)
            ->assertSee('id="chat-log"', false)
            ->assertSee('data-lenis-prevent', false)
            ->assertSee('streamUrl', false)
            ->assertDontSee('Unreviewed upload')
            ->assertDontSee('zzzzzzzzzzz');
    }

    public function test_quiz_page_keeps_the_server_timer(): void
    {
        $quiz = Quiz::firstOrFail();

        $this->actingAs($this->student)->get(route('quizzes.show', $quiz))->assertOk()
            ->assertSee('window.quizSeconds = '.$quiz->timer_seconds.';', false)
            ->assertSee('data-quiz-ring', false)
            ->assertSee('id="quiz-form"', false);
    }

    public function test_dashboard_counts_every_attempt_not_just_the_recent_ones(): void
    {
        $quiz = Quiz::firstOrFail();
        foreach (range(1, 6) as $i) {
            $this->student->quizResults()->create(['quiz_id' => $quiz->id, 'score' => 10 * $i, 'correct_answers' => 1, 'total_questions' => 2, 'completed_at' => now()]);
        }

        $page = $this->actingAs($this->student)->get(route('dashboard'))->assertOk();
        $page->assertSee('data-counter="6"', false)->assertSee('data-counter="60"', false);
    }

    public function test_lesson_html_is_translated_by_whole_text_nodes(): void
    {
        app()->setLocale('ar');
        $html = '<h2>Learning goals</h2><p>Something we have no translation for.</p><p>Reflection</p>';

        $this->assertSame('<h2>أهداف التعلم</h2><p>Something we have no translation for.</p><p>تأمل</p>', LocalizedText::html($html));
    }
}
