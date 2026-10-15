<?php

namespace App\Support;

final class VaFormatter
{
    public const PREFIX = '06202';

    /**
     * Format No VA: prefix 06202 + NIS (digit only), tanpa padding nol.
     */
    public static function fromNis(?string $nis): string
    {
        $digits = preg_replace('/\D+/', '', (string) $nis);
        $digits = is_string($digits) ? $digits : '';
        if ($digits === '') {
            return '';
        }

        return self::PREFIX . $digits;
    }
}
