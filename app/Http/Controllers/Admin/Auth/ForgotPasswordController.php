<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;

class ForgotPasswordController extends Controller
{
    public function create(): View
    {
        return view('admin.auth.forgot-password');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate(['email' => ['required', 'email', 'max:255']]);

        // Only active admins get a link. The answer is the same either way, so the page
        // does not reveal which emails belong to an admin.
        $active = \App\Models\Admin::query()->where('email', $request->string('email'))->where('is_active', true)->exists();
        if ($active) {
            Password::broker('admins')->sendResetLink($request->only('email'));
        }

        return back()->with('status', __('admin.auth.reset_link_sent'));
    }
}
