<?php

namespace App\Http\Controllers\Storefront\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Storefront\Auth\RegisterRequest;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class RegisterController extends Controller
{
    public function create(): View
    {
        return view('storefront.auth.register');
    }

    public function store(RegisterRequest $request): RedirectResponse
    {
        $user = User::create([
            ...$request->safe()->only(['name', 'email', 'phone', 'password']),
            'locale' => app()->getLocale(),
        ]);

        event(new Registered($user));

        // Welcome email (wording editable in Admin → Settings → Notifications).
        \Illuminate\Support\Facades\Mail::to($user->email)->send(new \App\Mail\CustomerMessage(
            event: 'welcome',
            vars: ['{name}' => \Illuminate\Support\Str::before($user->name, ' ')],
            actionUrl: store_route('account.dashboard'),
            actionText: __('store.account.title'),
            lang: app()->getLocale(),
        ));

        Auth::guard('web')->login($user);
        $request->session()->regenerate();

        return redirect(store_route('account.dashboard'))
            ->with('status', __('store.auth.welcome', ['name' => $user->name]));
    }
}
