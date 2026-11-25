<?php

namespace App\Http\Controllers\Storefront\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
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
        return view('storefront.auth.reset-password', [
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

        $status = $this->resetPasswordSafely($request, 'users', config('auth.passwords.users.table'));

        if ($status !== Password::PASSWORD_RESET) {
            return back()->withInput($request->only('email'))->withErrors(['email' => __($status)]);
        }

        return redirect(store_route('login'))->with('status', __('store.auth.password_updated'));
    }
}
