<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerAddress extends Model
{
    protected $fillable = ['user_id', 'label', 'name', 'phone', 'country', 'state', 'emirate', 'city', 'address_line', 'building', 'is_default'];

    protected $attributes = [
        'label' => null,
        'building' => null,
        'is_default' => true,
    ];

    protected function casts(): array
    {
        return ['is_default' => 'boolean'];
    }

    public const MAX_PER_CUSTOMER = 10;

    /** Same place, ignoring spacing and capitals — used to avoid saving duplicates at checkout. */
    public function matches(array $data): bool
    {
        $norm = fn ($v) => mb_strtolower(preg_replace('/\s+/', ' ', trim((string) $v)));

        return strtoupper((string) $this->country) === strtoupper((string) ($data['country'] ?? ''))
            && $norm($this->state) === $norm($data['state'] ?? '')
            && $norm($this->city) === $norm($data['city'] ?? '')
            && $norm($this->address_line) === $norm($data['address_line'] ?? '')
            && $norm($this->building) === $norm($data['building'] ?? '');
    }

    /** Make this the only default address of its customer. */
    public function makeDefault(): void
    {
        static::query()->where('user_id', $this->user_id)->whereKeyNot($this->id)->update(['is_default' => false]);
        $this->forceFill(['is_default' => true])->save();
    }

    /** Data the checkout form's address picker needs. */
    public function toCheckoutArray(): array
    {
        return $this->only(['id', 'label', 'name', 'phone', 'country', 'state', 'emirate', 'city', 'address_line', 'building']);
    }

    /** "City, State, Country" for showing the address. */
    public function regionLine(): string
    {
        return \App\Support\Countries::line($this->city, $this->state, $this->country, $this->emirate);
    }
}
