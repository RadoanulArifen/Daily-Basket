<?php

namespace App\Notifications\Admin;

use Illuminate\Auth\Notifications\ResetPassword;

/**
 * Same email as the customer version, but the link points to the admin reset page.
 */
class ResetPasswordNotification extends ResetPassword
{
    protected function resetUrl($notifiable): string
    {
        return route('admin.password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ]);
    }
}
