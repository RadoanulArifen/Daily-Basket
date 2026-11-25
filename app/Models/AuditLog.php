<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    use MassPrunable;

    public const UPDATED_AT = null;

    public const KEEP_DAYS = 365;

    protected $fillable = ['admin_id', 'action', 'subject_type', 'subject_id', 'subject_label', 'changes', 'ip'];

    protected function casts(): array
    {
        return ['changes' => 'array', 'created_at' => 'datetime'];
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class);
    }

    /** Removed by `php artisan model:prune` (scheduled daily). */
    public function prunable(): Builder
    {
        return static::query()->where('created_at', '<', now()->subDays(self::KEEP_DAYS));
    }

    /** Record something that isn't a simple model change (e.g. "exported orders"). */
    public static function record(string $action, string $subjectType, ?int $subjectId = null, ?string $label = null, ?array $changes = null): void
    {
        $admin = auth('admin')->user();

        static::create([
            'admin_id' => $admin?->id,
            'action' => $action,
            'subject_type' => $subjectType,
            'subject_id' => $subjectId,
            'subject_label' => $label ? mb_substr($label, 0, 250) : null,
            'changes' => $changes,
            'ip' => request()?->ip(),
        ]);
    }
}
