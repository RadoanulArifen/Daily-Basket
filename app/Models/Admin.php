<?php

// SECURITY: passwords are hashed automatically — casts(): 'password' => 'hashed'; hidden from arrays.

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Notifications\Admin\ResetPasswordNotification;
use Database\Factories\AdminFactory;
use Illuminate\Contracts\Translation\HasLocalePreference;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * The store owner. The platform runs with a single Super Admin account;
 * the table still allows more rows if staff accounts are added later.
 */
class Admin extends Authenticatable implements HasLocalePreference
{
    use Auditable;

    /** @use HasFactory<AdminFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'locale',
        'role',
        'is_active',
    ];

    /** Mirrors the column defaults so new (unsaved or just-created) models behave like loaded ones. */
    protected $attributes = [
        'locale' => 'en',
        'role' => 'owner',
        'is_active' => true,
        'last_login_at' => null,
        'last_login_ip' => null,
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'role' => \App\Enums\AdminRole::class,
            'is_active' => 'boolean',
            'tour_state' => 'array',
        ];
    }

    public function preferredLocale(): string
    {
        return $this->locale ?: config('store.default_locale');
    }

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetPasswordNotification($token));
    }

    public function initials(): string
    {
        return collect(preg_split('/\s+/', trim($this->name)))
            ->filter()
            ->take(2)
            ->map(fn (string $part) => mb_strtoupper(mb_substr($part, 0, 1)))
            ->implode('');
    }

    /** Whether this admin's role includes an area (see App\Enums\AdminRole). */
    public function mayAccess(string $area): bool
    {
        return $this->is_active && $this->role->allows($area);
    }

    public function isOwner(): bool
    {
        return $this->role === \App\Enums\AdminRole::Owner;
    }
}
