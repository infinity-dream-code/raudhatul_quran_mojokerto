<?php

namespace App\Http\Controllers\Smartcard;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class SmartcardPlaceholderController extends Controller
{
    /** @var array<string, array{title: string, subtitle: string}> */
    private const PAGES = [
        'transaksi-belanja' => [
            'title' => 'Transaksi Belanja',
            'subtitle' => 'Daftar transaksi belanja cashless',
        ],
        'rekap-pencairan-kantin' => [
            'title' => 'Rekap Pencairan Kantin',
            'subtitle' => 'Rekap pencairan merchant kantin',
        ],
        'rekap-topup' => [
            'title' => 'Rekap TOPUP',
            'subtitle' => 'Rekap topup saldo kartu',
        ],
        'topup-cash' => [
            'title' => 'TOPUP Cash',
            'subtitle' => 'Input topup cash kartu siswa',
        ],
        'rekap-keluar-uang-saku' => [
            'title' => 'Rekap Keluar Uang Saku',
            'subtitle' => 'Rekap pengambilan uang saku',
        ],
        'keluar-uang-saku' => [
            'title' => 'Keluar Uang Saku',
            'subtitle' => 'Transaksi keluar uang saku',
        ],
        'tap-ambil-rutin' => [
            'title' => 'TAP Ambil Rutin',
            'subtitle' => 'Tap kartu ambil uang saku rutin',
        ],
    ];

    public function show(string $page): View
    {
        $meta = self::PAGES[$page] ?? null;
        if ($meta === null) {
            abort(404);
        }

        return view('smartcard.placeholder', $meta);
    }
}
