<?php

namespace Tests\Feature;

use App\Models\Lesson;
use App\Models\Quiz;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Production runs with APP_LOCALE=ar for a Saudi client: key student and admin pages must not
 * fall back to hard-coded English.
 */
class ArabicInterfaceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->withSession(['locale' => 'ar']);
    }

    public function test_student_pages_are_arabic(): void
    {
        $student = User::where('email', 'student@cyberlearn.test')->firstOrFail();
        $lesson = Lesson::has('quiz')->firstOrFail();

        app()->setLocale('ar');
        $page = $this->actingAs($student)->get(route('lessons.show', ['version' => 'multi', 'lesson' => $lesson]))->assertOk();
        $page->assertSee(__('messages.start_quiz'))->assertSee(__('messages.agent_guide'))->assertSee('locale: "ar"', false);
        $page->assertDontSee('Start quiz')->assertDontSee('Course sections')->assertDontSee('Switch mode');

        $this->get(route('lessons.index'))->assertSee(__('messages.course_library'))->assertDontSee('Single AI');
        $this->get(route('quizzes.show', Quiz::firstOrFail()))->assertSee(__('messages.submit_quiz'))->assertDontSee('Submit quiz');
    }

    public function test_admin_pages_are_arabic(): void
    {
        $admin = User::where('email', 'admin@cyberlearn.test')->firstOrFail();
        $this->actingAs($admin);

        app()->setLocale('ar');
        $pages = [
            'admin.dashboard' => ['platform_analytics', 'Platform analytics'],
            'admin.lessons' => ['add_lesson', 'Add lesson'],
            'admin.quizzes' => ['create_quiz', 'Create quiz'],
            'admin.videos' => ['add_video', 'Add video'],
            'admin.users' => ['joined', '>Joined<'],
        ];

        foreach ($pages as $route => [$key, $english]) {
            $this->get(route($route))->assertOk()->assertSee(__('messages.'.$key))->assertDontSee($english, false);
        }
    }

    public function test_marketing_and_auth_pages_are_arabic(): void
    {
        app()->setLocale('ar');
        foreach (['landing', 'login', 'register', 'password.request'] as $route) {
            $this->get(route($route))->assertOk()
                ->assertSee('<html lang="ar" dir="rtl">', false)
                ->assertSee('"locale":"ar"', false)
                ->assertSee(__('messages.noscript_notice'));
        }
        $this->get(route('login'))->assertSee('<title>'.__('messages.login'), false);
    }
}
