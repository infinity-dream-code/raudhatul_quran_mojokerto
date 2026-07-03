<?php

namespace App\Support;

final class BillacPeriode
{
    /**
     * @return array{0: string, 1: string}|null
     */
    public static function parseThnAkademikYearPair(string $thnAkademik): ?array
    {
        $thnAkademik = trim($thnAkademik);
        if ($thnAkademik === '') {
            return null;
        }

        if (preg_match('/(\d{4})\s*[/\-]\s*(\d{4})/', $thnAkademik, $m)) {
            return [$m[1], $m[2]];
        }

        if (preg_match_all('/\d{4}/', $thnAkademik, $all) && count($all[0]) >= 2) {
            return [$all[0][0], $all[0][1]];
        }

        return null;
    }

    public static function resolveByTagihan(string $tagihan, string $fallback = '', string $thnAkademik = ''): string
    {
        $monthMap = [
            'JANUARI' => '01', 'JANUARY' => '01',
            'FEBRUARI' => '02', 'FEBRUARY' => '02',
            'MARET' => '03', 'MARCH' => '03',
            'APRIL' => '04',
            'MEI' => '05', 'MAY' => '05',
            'JUNI' => '06', 'JUNE' => '06',
            'JULI' => '07', 'JULY' => '07',
            'AGUSTUS' => '08', 'AUGUST' => '08',
            'SEPTEMBER' => '09',
            'OKTOBER' => '10', 'OCTOBER' => '10',
            'NOVEMBER' => '11',
            'DESEMBER' => '12', 'DECEMBER' => '12',
        ];

        $periodeBulan = date('m');
        $name = function_exists('mb_strtoupper')
            ? mb_strtoupper(trim($tagihan))
            : strtoupper(trim($tagihan));
        foreach ($monthMap as $key => $mm) {
            if ($name !== '' && str_contains($name, $key)) {
                $periodeBulan = $mm;
                break;
            }
        }

        $yearPair = self::parseThnAkademikYearPair($thnAkademik);
        if ($yearPair !== null) {
            [$year1, $year2] = $yearPair;
            $year = ((int) $periodeBulan < 7) ? $year2 : $year1;

            return $year . $periodeBulan;
        }

        $fallbackDigits = preg_replace('/\D+/', '', $fallback);
        if ($fallbackDigits !== '' && strlen($fallbackDigits) >= 6) {
            return substr($fallbackDigits, 0, 6);
        }

        if ($name === '') {
            return $fallback !== '' ? $fallback : (date('Y') . date('m'));
        }

        return date('Y') . $periodeBulan;
    }
}
