<?php

namespace Tests\Feature;

use App\Models\Lesson;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarketingPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_landing_is_the_react_island_with_real_counts(): void
    {
        $this->seed(DatabaseSeeder::class);
        $published = Lesson::where('is_published', true)->count();

        $page = $this->withSession(['locale' => 'ar'])->get('/')->assertOk();

        $page->assertSee('id="marketing-root"', false)
            ->assertSee('build/assets/marketing-', false)
            ->assertSee('"page":"landing"', false)
            ->assertSee('"lessons":'.$published, false)
            ->assertSee('"locale":"ar"', false)
            ->assertDontSee('build/assets/app-', false);
    }

    public function test_preview_urls_redirect_permanently(): void
    {
        $this->get('/preview')->assertStatus(301)->assertRedirect('/');
        $this->get('/preview/login')->assertStatus(301)->assertRedirect(route('login'));
        $this->get('/preview/register')->assertStatus(301)->assertRedirect(route('register'));
    }

    public function test_auth_pages_post_to_the_real_routes_with_csrf(): void
    {
        foreach (['login' => 'login.store', 'register' => 'register.store', 'password.request' => 'password.email'] as $page => $store) {
            $this->get(route($page))->assertOk()
                ->assertSee('"csrf":', false)
                ->assertSee(json_encode(route($store)), false);
        }

        $this->get(route('password.reset', ['token' => 'abc123', 'email' => 'a@b.test']))->assertOk()
            ->assertSee('"page":"reset"', false)
            ->assertSee('"token":"abc123"', false)
            ->assertSee('"email":"a@b.test"', false);
    }

    public function test_failed_login_returns_errors_and_old_email_but_never_the_password(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->from(route('login'))->post(route('login.store'), ['email' => 'student@cyberlearn.test', 'password' => 'wrong-secret-123'])
            ->assertRedirect(route('login'));

        $this->get(route('login'))->assertOk()
            ->assertSee('student@cyberlearn.test')
            ->assertSee('"errors":{"email"', false)
            ->assertDontSee('wrong-secret-123');
    }

    public function test_logged_in_users_are_sent_away_from_auth_pages(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->actingAs(User::where('email', 'student@cyberlearn.test')->firstOrFail());

        $this->get(route('login'))->assertRedirect();
        $this->get('/')->assertOk()->assertSee('"auth":true', false);
    }
}
