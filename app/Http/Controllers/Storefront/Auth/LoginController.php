<?php

namespace App\Http\Controllers\Storefront\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Storefront\Auth\LoginRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function create(Request $request): View
    {
        return view('storefront.auth.login', ['demoAvailable' => $this->demoCustomer($request) !== null]);
    }

    public function demo(Request $request): RedirectResponse
    {
        $customer = $this->demoCustomer($request);
        abort_if($customer === null, 404);

        Auth::guard('web')->login($customer);
        $request->session()->regenerate();
        $request->session()->put('storefront.demo_preview', true);
        $request->session()->forget('url.intended');

        return redirect(store_route('account.dashboard'));
    }

    private function demoCustomer(Request $request): ?User
    {
        if (! app()->isLocal() || ! config('store.demo_role_login')
            || ! in_array($request->getHost(), ['localhost', '127.0.0.1', '::1', '[::1]'], true)) {
            return null;
        }

        return User::query()->where('email', User::DEMO_EMAIL)->where('is_active', true)->first();
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();
        $request->session()->regenerate();

        $user = $request->user();
        if ($user->email === User::DEMO_EMAIL) {
            $request->session()->put('storefront.demo_preview', true);
        }

        $user->forceFill(['last_login_at' => now(), 'locale' => app()->getLocale()])->save();

        return redirect()->intended(store_route('account.dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect(store_route('home'));
    }
}
