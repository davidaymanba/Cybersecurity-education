<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DesignSystemTest extends TestCase
{
    use RefreshDatabase;

    public function test_component_gallery_only_exists_on_local_machines(): void
    {
        $this->get('/_ui')->assertNotFound();
    }

    public function test_app_shell_renders_navigation_and_shared_dialogs(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::where('email', 'admin@cyberlearn.test')->firstOrFail();

        $page = $this->actingAs($admin)->withSession(['locale' => 'ar'])->get(route('dashboard'))->assertOk();

        $page->assertSee('dir="rtl"', false)
            ->assertSee('id="confirm-dialog"', false)
            ->assertSee('aria-controls="mobile-nav"', false)
            ->assertSee(route('admin.dashboard'))
            ->assertSee(route('account.edit'))
            ->assertSee(route('lang.switch', 'en'))
            ->assertDontSee('model-viewer', false)
            ->assertDontSee('scroll-smooth', false);
    }
}
