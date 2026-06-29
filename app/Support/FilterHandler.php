<?php

namespace App\Support;

use Illuminate\Http\Request;

class FilterHandler
{
    public static function resolveFilters($filter, $allowedFilters): array
    {
        $normalized = collect($allowedFilters)
            ->mapWithKeys(function ($value, $key) {
                return is_int($key)
                    ? [$value => $value]
                    : [$key => $value];
            })
            ->toArray();

        return collect($filter)
            ->only(array_keys($normalized))
            ->reject(fn($value) => $value === 'all' || $value === null || $value === '')
            ->mapWithKeys(fn($value, $key) => [
                $normalized[$key] => $value
            ])
            ->sortKeys()
            ->toArray();
    }
}
