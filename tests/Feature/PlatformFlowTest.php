<?php

namespace Tests\Feature;

use App\Models\Lesson;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlatformFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_can_open_single_and_multi_agent_lesson_pages(): void
    {
        $this->seed(DatabaseSeeder::class);

        $student = User::where('email', 'student@cyberlearn.test')->firstOrFail();
        $lesson = Lesson::firstOrFail();

        $this->actingAs($student)
            ->get(route('lessons.show', ['version' => 'single', 'lesson' => $lesson]))
            ->assertOk()
            ->assertSee('AI Tutor')
            ->assertSee($lesson->title);

        $this->actingAs($student)
            ->get(route('lessons.show', ['version' => 'multi', 'lesson' => $lesson]))
            ->assertOk()
            ->assertSee('AI Agent Team')
            ->assertSee('Guide');
    }

    public function test_admin_can_view_research_dashboard(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = User::where('email', 'admin@cyberlearn.test')->firstOrFail();

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Platform analytics')
            ->assertSee('AI agent usage');
    }
}
