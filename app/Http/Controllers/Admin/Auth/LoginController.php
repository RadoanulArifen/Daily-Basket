<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Enums\AdminRole;
use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Http\Requests\Admin\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function create(Request $request): View
    {
        $demoRoles = $this->canPreviewRoles($request)
            ? array_filter(AdminRole::cases(), fn (AdminRole $role) => $this->demoAccount($role) !== null)
            : [];

        return view('admin.auth.login', compact('demoRoles'));
    }

    public function demo(Request $request, AdminRole $role): RedirectResponse
    {
        abort_unless($this->canPreviewRoles($request), 404);

        $admin = $this->demoAccount($role);
        abort_if($admin === null, 404);

        Auth::guard('admin')->login($admin);
        $request->session()->regenerate();
        $request->session()->put('admin.demo_preview', true);
        $request->session()->forget('url.intended');

        return redirect()->route('admin.dashboard');
    }

    private function canPreviewRoles(Request $request): bool
    {
        return config('store.demo_role_login') && (
            config('store.demo')
            || (app()->isLocal() && in_array($request->getHost(), ['localhost', '127.0.0.1', '::1', '[::1]'], true))
        );
    }

    private function demoAccount(AdminRole $role): ?Admin
    {
        return Admin::query()
            ->where('email', "demo.{$role->value}@example.com")
            ->where('role', $role->value)
            ->where('is_active', true)
            ->first();
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();
        $request->session()->regenerate();

        $request->user('admin')->forceFill([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
        ])->save();

        return redirect()->intended(route('admin.dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('admin')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}
