<?php

namespace App\Support;

final class VaFormatter
{
    /**
     * Format No VA: prefix 06202 + NIS (digit only), total 16 digit.
     */
    public static function fromNis(?string $nis): string
    {
        $digits = preg_replace('/\D+/', '', (string) $nis);
        $digits = is_string($digits) ? $digits : '';
        if ($digits === '') {
            return '';
        }
        if (strlen($digits) > 11) {
            $digits = substr($digits, -11);
        }

        return '06202' . str_pad($digits, 11, '0', STR_PAD_LEFT);
    }
}
