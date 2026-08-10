<?php

namespace App\Services\Cashless;

use Illuminate\Support\Facades\DB;

class CashlessCardService
{
    public function parseQrToTapId(string $raw): string
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
            $parts = explode('|', $raw);

            return trim($parts[0]);
        }

        return $raw;
    }

    public function fetchCardByTapId(string $tapId): ?object
    {
        $tapId = trim($tapId);
        if ($tapId === '') {
            return null;
        }

        return DB::connection('DATA_MYSQL')
            ->table('sm_pin')
            ->join('scctcust', 'sm_pin.CUSTID', '=', 'scctcust.CUSTID')
            ->where('sm_pin.PID', $tapId)
            ->first([
                'sm_pin.CUSTID as custid',
                'sm_pin.PID as pid',
                'sm_pin.PIN as pin',
                'sm_pin.BLOKIR as blokir',
                'scctcust.NMCUST as nama',
                'scctcust.NOCUST as nis',
            ]);
    }

    public function isBlocked(?object $card): bool
    {
        if (!$card) {
            return true;
        }

        return (int) ($card->blokir ?? 0) === 1;
    }

    public function validatePin(?object $card, string $pin): bool
    {
        if (!$card) {
            return false;
        }

        $storedPin = trim((string) ($card->pin ?? ''));

        return $storedPin !== '' && hash_equals($storedPin, trim($pin));
    }

    public function fetchBatasBelanjaHari(): int
    {
        $periode = now()->format('Ym');

        $row = DB::connection('DATA_MYSQL')
            ->table('sm_batasan')
            ->where('periode', $periode)
            ->where('aktif', 1)
            ->orderByDesc('urut')
            ->first(['batas_belanja_hari']);

        if ($row) {
            return (int) ($row->batas_belanja_hari ?? 0);
        }

        $fallback = DB::connection('DATA_MYSQL')
            ->table('sm_batasan')
            ->where('aktif', 1)
            ->orderByDesc('periode')
            ->first(['batas_belanja_hari']);

        return (int) ($fallback->batas_belanja_hari ?? 0);
    }
}
