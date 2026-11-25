<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\AccountRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class AccountController extends AdminController
{
    public function edit(Request $request): View
    {
        return view('admin.account.edit', ['admin' => $request->user('admin')]);
    }

    public function update(AccountRequest $request): RedirectResponse
    {
        $request->user('admin')->update($request->validated());

        return back()->with('success', __('admin.account.saved'));
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $validated = $request->validateWithBag('password', [
            'current_password' => ['required', 'current_password:admin'],
            'password' => \App\Support\PasswordRules::confirmed(),
        ]);

        $request->user('admin')->update(['password' => $validated['password']]);

        // Anyone signed in as this admin elsewhere is signed out, and this session is
        // given a fresh id.
        \Illuminate\Support\Facades\Auth::guard('admin')->logoutOtherDevices($validated['password']);
        $request->session()->regenerate();

        return back()->with('success', __('admin.account.password_saved'));
    }
}
