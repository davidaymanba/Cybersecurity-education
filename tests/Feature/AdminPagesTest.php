<?php

namespace Tests\Feature;

use App\Models\Lesson;
use App\Models\Section;
use App\Models\User;
use App\Models\VideoResource;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPagesTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->admin = User::where('email', 'admin@cyberlearn.test')->firstOrFail();
        $this->actingAs($this->admin);
    }

    public function test_an_unticked_publish_box_creates_a_draft(): void
    {
        // The redesigned checkbox sends a hidden 0 first, so boolean('is_published', true) sees false.
        $this->post(route('admin.lessons.store'), [
            'section_id' => Section::firstOrFail()->id, 'title' => 'Draft lesson', 'summary' => 'S', 'content' => '<p>C</p>',
            'difficulty' => 'Beginner', 'duration_minutes' => 10, 'is_published' => '0',
        ])->assertRedirect();

        $this->assertFalse(Lesson::where('title', 'Draft lesson')->firstOrFail()->is_published);
        $this->get(route('admin.lessons'))->assertSee('name="is_published" value="0"', false);
    }

    public function test_old_input_only_returns_to_the_form_that_was_submitted(): void
    {
        $lesson = Lesson::orderBy('sort_order')->firstOrFail();
        $other = Lesson::orderBy('sort_order')->skip(1)->firstOrFail();

        $this->from(route('admin.lessons'))->put(route('admin.lessons.update', $lesson), [
            '_form' => 'lesson-'.$lesson->id, 'title' => 'Typed but invalid', 'section_id' => $lesson->section_id,
            'summary' => 'x', 'content' => 'x', 'difficulty' => 'Beginner', 'duration_minutes' => 0,
        ])->assertSessionHasErrors('duration_minutes');

        $page = $this->get(route('admin.lessons'))->assertOk();
        $page->assertSee('data-open-on-load', false);
        // The typed title comes back once (its own dialog), the other rows keep their saved values.
        $this->assertSame(1, substr_count($page->getContent(), 'value="Typed but invalid"'));
        $page->assertSee('value="'.$other->title.'"', false);
    }

    public function test_temporary_password_is_shown_once_with_a_copy_button(): void
    {
        $student = User::where('email', 'student@cyberlearn.test')->firstOrFail();

        $this->from(route('admin.users'))->post(route('admin.users.reset-password', $student))->assertRedirect(route('admin.users'));
        $temporary = session('temporary_password')['password'];

        $this->get(route('admin.users'))->assertSee('data-copy="'.$temporary.'"', false);
        $this->get(route('admin.users'))->assertDontSee($temporary);
    }

    public function test_overview_shows_real_analytics_with_a_table_fallback(): void
    {
        $page = $this->get(route('admin.dashboard'))->assertOk();

        $page->assertSee(__('messages.activity_14_days'))
            ->assertSee(__('messages.cache_hit_rate'))
            ->assertSee('data-counter="'.User::whereHas('role', fn ($q) => $q->where('name', 'student'))->count().'"', false);
    }

    public function test_unapproved_videos_can_be_saved_as_hidden(): void
    {
        $this->post(route('admin.videos.store'), [
            'lesson_id' => Lesson::firstOrFail()->id, 'title' => 'Pending review', 'youtube_id' => 'abcdef123',
            'channel_name' => 'Channel', 'approved' => '0',
        ])->assertRedirect();

        $this->assertFalse(VideoResource::where('title', 'Pending review')->firstOrFail()->approved);
    }
}
