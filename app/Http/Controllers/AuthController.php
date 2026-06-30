<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function login(): View
    {
        return view('auth.login');
    }

    public function authenticate(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();

            return redirect()->intended(auth()->user()->isAdmin() ? route('admin.dashboard') : route('dashboard'));
        }

        return back()->withErrors(['email' => 'The provided credentials do not match our records.'])->onlyInput('email');
    }

    public function register(): View
    {
        return view('auth.register');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'unique:users,email'],
            'learning_level' => ['required', 'in:Beginner,Intermediate,Expert'],
            'password' => ['required', 'confirmed', 'min:8'],
        ]);

        $user = User::create([
            'role_id' => Role::firstOrCreate(['name' => 'student'], ['label' => 'Student'])->id,
            'name' => $data['name'],
            'email' => $data['email'],
            'learning_level' => $data['learning_level'],
            'password' => $data['password'],
        ]);

        Auth::login($user);

        return redirect()->route('dashboard');
    }

    public function forgot(): View
    {
        return view('auth.forgot');
    }

    public function sendReset(Request $request): RedirectResponse
    {
        $request->validate(['email' => ['required', 'email']]);

        try {
            Password::sendResetLink($request->only('email'));
        } catch (\Exception) {
            // Mail driver may not be configured — silently absorb to avoid leaking internals.
        }

        // Generic message to prevent email enumeration.
        return back()->with('status', 'If that email is registered, a reset link has been sent to your inbox.');
    }

    public function showReset(Request $request, string $token): View
    {
        return view('auth.reset', [
            'token' => $token,
            'email' => $request->string('email'),
        ]);
    }

    public function resetPassword(Request $request): RedirectResponse
    {
        $request->validate([
            'token'    => ['required'],
            'email'    => ['required', 'email'],
            'password' => ['required', 'confirmed', 'min:8'],
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill(['password' => $password])->save();
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return redirect()->route('login')->with('status', 'Password reset successfully. Please sign in.');
        }

        return back()->withInput($request->only('email'))
                     ->withErrors(['email' => __($status)]);
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('landing');
    }
}
