<?php

namespace App\Services\Cashless;

class CashlessQrParser
{
    public static function toPid(string $raw): string
    {
        $raw = trim($raw);
        if ($raw === '') {
            return '';
        }

        if (str_starts_with($raw, '{') || str_starts_with($raw, '[')) {
            $json = json_decode($raw, true);
            if (is_array($json)) {
                foreach (['tap_id', 'pid', 'PID', 'TAP_ID', 'id', 'card_id'] as $key) {
                    if (!empty($json[$key])) {
                        return trim((string) $json[$key]);
                    }
                }
            }
        }

        if (preg_match('/[?&](?:tap_id|pid|id)=([^&]+)/i', $raw, $matches)) {
            return trim(urldecode($matches[1]));
        }

        if (str_contains($raw, '|')) {
            return trim(explode('|', $raw)[0]);
        }

        return $raw;
    }
}
