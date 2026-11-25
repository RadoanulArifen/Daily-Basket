<?php

// SECURITY: passwords are hashed automatically — casts(): 'password' => 'hashed'; hidden from arrays.

namespace App\Models;

use App\Models\Concerns\Auditable;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Translation\HasLocalePreference;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * A storefront customer. Store staff use App\Models\Admin instead.
 */
class User extends Authenticatable implements HasLocalePreference
{
    use Auditable;

    public const DEMO_EMAIL = 'demo.customer@example.com';

    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'locale',
        'password',
    ];

    /** Mirrors the column defaults so new (unsaved or just-created) models behave like loaded ones. */
    protected $attributes = [
        'phone' => null,
        'locale' => 'en',
        'is_active' => true,
        'last_login_at' => null,
        'admin_note' => null,
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'is_active' => 'boolean',
            'password' => 'hashed',
        ];
    }

    /**
     * Send password reset links in the customer's own language.
     */
    public function preferredLocale(): string
    {
        return $this->locale ?: config('store.default_locale');
    }

    public function cart(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Cart::class);
    }

    public function wishlistItems(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(WishlistItem::class)->latest('id');
    }

    public function orders(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Order::class)->latest('id');
    }

    public function addresses(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(CustomerAddress::class)->orderByDesc('is_default')->latest('id');
    }
}
