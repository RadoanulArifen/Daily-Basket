<?php

// SECURITY: reset tokens are single use — after a successful reset the token is deleted with
// Password::broker($broker)->deleteToken($account) and every token row for the email is removed.

namespace App\Http\Controllers\Concerns;

use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Contracts\Auth\CanResetPassword;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

/**
 * The one password-reset routine used by customers (broker "users") and admins ("admins").
 *
 * Single use and atomic: inside one database transaction the new password is saved first, and
 * only then is the token deleted (by the broker, and again explicitly below). If saving fails,
 * the transaction rolls back and the link stays valid so the customer can simply retry; once it
 * succeeds, the token is gone and the link can never be replayed.
 */
trait ResetsPasswordsSafely
{
    /** @return string a Password::* status constant */
    protected function resetPasswordSafely(Request $request, string $broker, string $table): string
    {
        return DB::transaction(function () use ($request, $broker, $table) {
            $status = Password::broker($broker)->reset(
                $request->only('email', 'password', 'password_confirmation', 'token'),
                function (CanResetPassword $account, string $password) use ($broker) {
                    $account->forceFill([
                        'password' => $password,
                        'remember_token' => Str::random(60),
                    ])->save();

                    event(new PasswordReset($account));
                },
            );

            if ($status === Password::PASSWORD_RESET) {
                // After the password change succeeded the token is single use: delete it through the
                // broker, and remove any other token rows for this email.
                if ($account = Password::broker($broker)->getUser(['email' => (string) $request->string('email')])) {
                    Password::broker($broker)->deleteToken($account);
                }
                DB::table($table)->where('email', $request->string('email'))->delete();
            }

            return $status;
        });
    }
}
