<?php

namespace App\Enums;

enum ProductStatus: string
{
    case Draft = 'draft';
    case Active = 'active';
    case Archived = 'archived';

    public function label(): string
    {
        return __("admin.products.status.{$this->value}");
    }

    /** Badge colour in the admin. */
    public function tone(): string
    {
        return match ($this) {
            self::Active => 'success',
            self::Draft => 'info',
            self::Archived => 'neutral',
        };
    }

    /** @return array<string, string> value => label, for selects */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $s) => [$s->value => $s->label()])->all();
    }
}
