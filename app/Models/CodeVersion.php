<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CodeVersion extends Model
{
    public const UPDATED_AT = null;

    public const KEEP = 30;

    protected $fillable = ['css', 'head', 'body', 'domains', 'admin_id'];

    protected function casts(): array
    {
        return ['domains' => 'array', 'created_at' => 'datetime'];
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class);
    }
}
