<?php

namespace App\Models\Concerns;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Writes an audit log entry when a signed-in admin creates, changes or deletes the model.
 * Changes made by customers or background jobs (e.g. stock sold) aren't logged here;
 * those have their own history (inventory movements, order timeline).
 */
trait Auditable
{
    /** Never written to the log. */
    protected static array $auditHidden = ['password', 'remember_token', 'access_token', 'created_at', 'updated_at', 'stock_quantity', 'last_login_at'];

    public static function bootAuditable(): void
    {
        static::created(fn (Model $m) => $m->writeAudit('created', $m->auditValues(null)));
        static::updated(fn (Model $m) => $m->writeAudit('updated', $m->auditValues($m->getRawOriginal())));
        static::deleted(fn (Model $m) => $m->writeAudit('deleted', null));
    }

    /** Readable name shown in the log (product name, order number…). */
    public function auditLabel(): ?string
    {
        foreach (['number', 'code', 'name', 'title', 'label', 'slug', 'original_name'] as $attribute) {
            if (array_key_exists($attribute, $this->getAttributes())) {
                $value = $this->getAttribute($attribute);

                return is_scalar($value) ? (string) $value : null;
            }
        }

        return null;
    }

    public function auditType(): string
    {
        return Str::snake(class_basename($this));
    }

    private function writeAudit(string $action, ?array $changes): void
    {
        if (! auth('admin')->check() || ($action === 'updated' && ! $changes)) {
            return;
        }

        // Secrets (e.g. API keys): record that they changed, never the value.
        if ($changes && method_exists($this, 'auditRedacted') && $this->auditRedacted()) {
            $changes = array_map(fn () => ['••••', '••••'], $changes);
        }

        AuditLog::record($action, $this->auditType(), $this->getKey(), $this->auditLabel(), $changes);
    }

    /** @return array<string, array{0: mixed, 1: mixed}>|null field => [old, new] */
    private function auditValues(?array $original): ?array
    {
        $changes = [];

        foreach ($original === null ? $this->getAttributes() : $this->getDirty() as $key => $new) {
            if (in_array($key, static::$auditHidden, true) || $key === $this->getKeyName()) {
                continue;
            }

            $changes[$key] = [$original === null ? null : $this->readable($original[$key] ?? null), $this->readable($new)];
        }

        return $changes ?: null;
    }

    private function readable(mixed $value): mixed
    {
        if (is_string($value) && str_starts_with($value, '{')) {
            $decoded = json_decode($value, true);

            return is_array($decoded) ? $decoded : $value;
        }

        return is_string($value) ? Str::limit($value, 300) : $value;
    }
}
