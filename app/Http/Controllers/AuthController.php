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
        Password::sendResetLink($request->only('email'));

        return back()->with('status', 'If the account exists, a reset link has been sent.');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('landing');
    }
}
