<?php

// Customers have no "deactivated" state (unlike admins, see Admin/Auth/ForgotPasswordController),
// so every customer email may get a link; the answer is the same for unknown emails (no probing).

namespace App\Http\Controllers\Storefront\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;

class ForgotPasswordController extends Controller
{
    public function create(): View
    {
        return view('storefront.auth.forgot-password');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate(['email' => ['required', 'email', 'max:255']]);

        Password::broker('users')->sendResetLink($request->only('email'));

        // Same message whether or not the email exists, so accounts can't be discovered.
        return back()->with('status', __('store.auth.reset_link_sent'));
    }
}
