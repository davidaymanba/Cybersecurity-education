<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AccountTest extends TestCase
{
    use RefreshDatabase;

    private function userWithRole(string $role, string $password = 'Old-Pass-123'): User
    {
        return User::factory()->create([
            'role_id' => Role::firstOrCreate(['name' => $role], ['label' => ucfirst($role)])->id,
            'password' => $password,
        ]);
    }

    public function test_user_can_change_own_password_with_current_password(): void
    {
        $user = $this->userWithRole('student');

        $this->actingAs($user)->get(route('account.edit'))->assertOk()->assertSee($user->email);

        $this->put(route('account.password'), [
            'current_password' => 'Old-Pass-123',
            'password' => 'New-Pass-456',
            'password_confirmation' => 'New-Pass-456',
        ])->assertRedirect(route('account.edit'))->assertSessionHas('status');

        $this->assertTrue(Hash::check('New-Pass-456', $user->fresh()->password));
    }

    public function test_wrong_current_password_is_rejected(): void
    {
        $user = $this->userWithRole('student');

        $this->actingAs($user)->from(route('account.edit'))->put(route('account.password'), [
            'current_password' => 'wrong',
            'password' => 'New-Pass-456',
            'password_confirmation' => 'New-Pass-456',
        ])->assertSessionHasErrors('current_password');

        $this->assertTrue(Hash::check('Old-Pass-123', $user->fresh()->password));
    }

    public function test_admin_can_give_a_student_a_temporary_password(): void
    {
        $admin = $this->userWithRole('admin');
        $student = $this->userWithRole('student');

        $this->actingAs($admin)->get(route('admin.users'))->assertOk()->assertSee(route('admin.users.reset-password', $student));

        $response = $this->post(route('admin.users.reset-password', $student))->assertRedirect();
        preg_match('/: ([A-Za-z0-9]{12}) /', session('status'), $m);

        $this->assertNotEmpty($m[1] ?? null, 'temporary password is shown to the admin');
        $this->assertTrue(Hash::check($m[1], $student->fresh()->password));
    }

    public function test_students_cannot_reset_passwords_and_admin_cannot_reset_self(): void
    {
        $admin = $this->userWithRole('admin');
        $student = $this->userWithRole('student');

        $this->actingAs($student)->post(route('admin.users.reset-password', $admin))->assertForbidden();
        $this->actingAs($admin)->post(route('admin.users.reset-password', $admin))->assertStatus(422);
    }

    public function test_navigation_is_visible_on_small_screens(): void
    {
        $html = $this->actingAs($this->userWithRole('student'))->get(route('dashboard'))->getContent();

        $this->assertStringNotContainsString('<nav class="hidden', $html);
        $this->assertStringContainsString(route('account.edit'), $html);
    }
}
