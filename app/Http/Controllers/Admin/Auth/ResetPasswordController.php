<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\View\View;

class ResetPasswordController extends Controller
{
    use \App\Http\Controllers\Concerns\ResetsPasswordsSafely;

    public function create(Request $request, string $token): View
    {
        return view('admin.auth.reset-password', [
            'token' => $token,
            'email' => $request->query('email'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::defaults()],
        ]);

        // A deactivated admin cannot use a reset link to get back in.
        $target = Admin::query()->where('email', $request->string('email'))->first();
        if ($target && ! $target->is_active) {
            return back()->withInput($request->only('email'))->withErrors(['email' => __(Password::INVALID_TOKEN)]);   // same message as a bad link: reveals nothing
        }

        $status = $this->resetPasswordSafely($request, 'admins', config('auth.passwords.admins.table'));

        if ($status !== Password::PASSWORD_RESET) {
            return back()->withInput($request->only('email'))->withErrors(['email' => __($status)]);
        }

        return redirect()->route('admin.login')->with('status', __('admin.auth.password_updated'));
    }
}
