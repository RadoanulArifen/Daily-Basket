<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;

/**
 * Key/value store settings. Always read and write through
 * App\Services\Settings\SettingsService so the cache stays correct.
 */
class Setting extends Model
{
    use Auditable;

    protected $fillable = ['group', 'key', 'value'];

    protected function casts(): array
    {
        return [
            'value' => 'json',
        ];
    }

    /** API keys and passwords are never written to the activity log. */
    public function auditRedacted(): bool
    {
        return $this->group === 'credentials';
    }

    public function auditLabel(): ?string
    {
        return $this->group.'.'.$this->key;
    }
}
