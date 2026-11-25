<?php

namespace App\Services\Shipping;

use App\Support\Money;

final class ShippingRate
{
    /** @param  array<string, string>  $name  {"en": ..., "ar": ...} */
    public function __construct(
        public readonly string $code,
        public readonly array $name,
        public readonly Money $price,
        public readonly ?array $description = null,
    ) {}

    public function label(?string $locale = null): string
    {
        $locale ??= app()->getLocale();

        return $this->name[$locale] ?? $this->name['en'] ?? $this->code;
    }

    public function note(?string $locale = null): ?string
    {
        $locale ??= app()->getLocale();

        return $this->description[$locale] ?? $this->description['en'] ?? null;
    }
}
