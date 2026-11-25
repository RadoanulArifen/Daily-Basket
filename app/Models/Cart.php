<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cart extends Model
{
    use MassPrunable;

    protected $fillable = ['token', 'user_id', 'discount_code', 'email', 'reminder_sent_at'];

    protected $attributes = [
        'user_id' => null,
        'discount_code' => null,
        'email' => null,
        'reminder_sent_at' => null,
    ];

    protected function casts(): array
    {
        return ['reminder_sent_at' => 'datetime'];
    }

    public function items(): HasMany
    {
        return $this->hasMany(CartItem::class)->orderBy('id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Removed by `php artisan model:prune` (daily): guest carts untouched for as long as
     * their cookie lasts, and customers' carts left untouched for 6 months.
     */
    public function prunable(): Builder
    {
        $guestDays = (int) ceil(\App\Services\Cart\CartService::COOKIE_MINUTES / 1440);

        return static::query()->where(fn ($q) => $q
            ->where(fn ($g) => $g->whereNull('user_id')->where('updated_at', '<', now()->subDays($guestDays)))
            ->orWhere(fn ($u) => $u->whereNotNull('user_id')->where('updated_at', '<', now()->subMonths(6))));
    }
}
