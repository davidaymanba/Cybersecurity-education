<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ErrorPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_missing_page_uses_the_new_error_page(): void
    {
        $this->get('/this-page-does-not-exist')->assertNotFound()
            ->assertSee(__('messages.error_404_title'))
            ->assertSee('data-scramble="404"', false);
    }

    public function test_forbidden_page_follows_the_session_locale(): void
    {
        $this->seed(DatabaseSeeder::class);
        $student = User::where('email', 'student@cyberlearn.test')->firstOrFail();

        app()->setLocale('ar');
        $this->actingAs($student)->withSession(['locale' => 'ar'])->get(route('admin.dashboard'))->assertForbidden()
            ->assertSee('<html lang="ar" dir="rtl">', false)
            ->assertSee(__('messages.error_403_title'));
    }

    public function test_every_error_view_renders_in_both_languages(): void
    {
        foreach (['ar', 'en'] as $locale) {
            app()->setLocale($locale);
            foreach ([403, 404, 419, 500, 503] as $code) {
                $html = view('errors.'.$code)->render();
                $this->assertStringContainsString(e(__('messages.error_'.$code.'_title')), $html);
                $this->assertStringContainsString('HTTP/1.1 '.$code, $html);
            }
        }
    }
}
