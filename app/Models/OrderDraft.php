<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** An order started in the admin and saved to finish later. */
class OrderDraft extends Model
{
    protected $fillable = ['admin_id', 'customer_id', 'name', 'email', 'items_count', 'subtotal', 'payload'];

    protected function casts(): array
    {
        return ['payload' => 'array', 'items_count' => 'integer', 'subtotal' => 'integer'];
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    /** What to call this draft in a list. */
    public function label(): string
    {
        return $this->name ?: ($this->email ?: __('admin.draft.untitled'));
    }
}
