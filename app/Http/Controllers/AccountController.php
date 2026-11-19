<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AccountController extends Controller
{
    public function edit(): View
    {
        return view('account.password');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', 'min:8', 'different:current_password'],
        ]);

        $request->user()->forceFill(['password' => $data['password']])->save();
        $request->session()->regenerate();

        return redirect()->route('account.edit')->with('status', __('messages.password_changed'));
    }
}
