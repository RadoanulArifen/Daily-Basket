<?php

namespace App\Support;

use Illuminate\Validation\Rule;

/**
 * The shared address fields of checkout, the account address book and draft orders:
 * country (ISO code, from the allowed list), state / province / region (free text) and city.
 * Older forms that still send an `emirate` code are accepted: it becomes country AE with the
 * emirate's name as the state.
 */
final class AddressInput
{
    /** Normalise raw input before validation. */
    public static function normalize(array $input): array
    {
        if (blank($input['country'] ?? null) && filled($input['emirate'] ?? null)) {
            $input['country'] = 'AE';
            $input['state'] = $input['state'] ?? __('store.emirates.'.$input['emirate'], [], 'en');
        }
        if (isset($input['country'])) {
            $input['country'] = strtoupper(trim((string) $input['country']));
        }
        if (isset($input['state'])) {
            $input['state'] = trim((string) $input['state']);
        }

        return $input;
    }

    /** @return array<string, array<int, mixed>> */
    public static function rules(bool $required = true): array
    {
        return [
            'country' => [$required ? 'required' : 'nullable', 'string', 'size:2', Rule::in(Countries::allowedCodes())],
            'state' => ['nullable', 'string', 'max:100'],
            'city' => [$required ? 'required' : 'nullable', 'string', 'max:100'],
        ];
    }

    /** The address columns to store, with the emirate filled in for UAE addresses. */
    public static function columns(array $data): array
    {
        return [
            'country' => $data['country'],
            'state' => $data['state'] ?? null,
            'emirate' => Countries::emirate($data['country'], $data['state'] ?? null),
            'city' => $data['city'],
        ];
    }
}
