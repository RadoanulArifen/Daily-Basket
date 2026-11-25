<?php

namespace App\Support;

use Illuminate\Validation\Rules\Password;

/**
 * The one place that says what a new password must look like (customers, admins, the
 * store:admin command). The policy itself is Password::defaults(), set in AppServiceProvider:
 * production → 8+ characters with letters, numbers and symbols, not in known breaches.
 */
final class PasswordRules
{
    /** @return array<int, mixed> rules for a new password with a confirmation field */
    public static function confirmed(): array
    {
        return ['required', 'confirmed', Password::defaults()];
    }

    /**
     * @return array<int, mixed> rules for the store:admin command (8+ characters; the breach
     * check is skipped there because a server may be offline while it is installed)
     */
    public static function console(): array
    {
        return [Password::min(8)];
    }
}
