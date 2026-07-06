<?php

namespace App\Http\Controllers\Smartcard;

use App\Http\Controllers\Controller;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class RekapTopupController extends Controller
{
    private const PER_PAGE = 10;

    private const CASH_FEE = 2000;

    private const TRAN_TABLE = 'sccttran_cashless';

    public function index(Request $request): View
    {
        $isSearch = $request->boolean('search');
        $filters = $this->filtersFromRequest($request);

        $thnAka = $this->fetchThnAka();
        $kelasOptions = $this->fetchKelasOptions();

        $rows = new LengthAwarePaginator([], 0, self::PER_PAGE, 1, [
            'path' => $request->url(),
            'query' => $request->query(),
        ]);
        $totals = ['topup' => 0, 'fee' => 0, 'grand' => 0];

        if ($isSearch) {
            $rows = $this->fetchRows($filters, $request);
            $totals = $this->sumTotals($filters);
        }

        return view('smartcard.rekap-topup.index', [
            'filters' => $filters,
            'isSearch' => $isSearch,
            'rows' => $rows,
            'totals' => $totals,
            'thnAka' => $thnAka,
            'kelasOptions' => $kelasOptions,
        ]);
    }

    public function printRekap(Request $request): Response|RedirectResponse
    {
        $filters = $this->filtersFromRequest($request);

        $allRows = $this->baseQuery($filters)
            ->select($this->selectColumns())
            ->orderByDesc('t.TRXDATE')
            ->orderByDesc('t.urut')
            ->get()
            ->map(fn ($row) => $this->mapRow($row));

        if ($allRows->isEmpty()) {
            return redirect()
                ->route('smartcard.rekap_topup', array_merge($filters, ['search' => 1]))
                ->with('smartcard_error', 'Tidak ada data rekap untuk dicetak.');
        }

        $totals = $this->sumTotals($filters);
        $sekolahNama = $this->fetchSekolahNama();

        $pdf = Pdf::loadView('smartcard.rekap-topup.rekap-pdf', [
            'sekolahNama' => $sekolahNama,
            'filters' => $filters,
            'rows' => $allRows,
            'totals' => $totals,
        ])->setPaper('a4', 'landscape');

        return $pdf->stream('rekap-topup-uang-saku-' . date('Ymd-His') . '.pdf');
    }

    private function filtersFromRequest(Request $request): array
    {
        return [
            'thn_angkatan' => trim((string) $request->query('thn_angkatan', '')),
            'kelas_id' => trim((string) $request->query('kelas_id', '')),
            'nis' => trim((string) $request->query('nis', '')),
            'nama' => trim((string) $request->query('nama', '')),
            'dari_tanggal' => trim((string) $request->query('dari_tanggal', '')),
            'sampai_tanggal' => trim((string) $request->query('sampai_tanggal', '')),
        ];
    }

    private function baseQuery(array $filters)
    {
        $query = DB::connection('sikeu')
            ->table(self::TRAN_TABLE . ' as t')
            ->join('scctcust', 't.CUSTID', '=', 'scctcust.CUSTID')
            ->leftJoin('mst_kelas', DB::raw('CAST(mst_kelas.id AS CHAR)'), '=', DB::raw('TRIM(scctcust.CODE03)'))
            ->whereRaw('UPPER(TRIM(t.FIDBANK)) = ?', ['TOPUP'])
            ->where('t.KREDIT', '>', 0);

        $this->applySchoolScope($query);
        $this->applyFilters($query, $filters);

        return $query;
    }

    private function selectColumns(): array
    {
        return [
            'scctcust.NOCUST as nis',
            'scctcust.NMCUST as nama',
            't.KREDIT as topup',
            't.TRXDATE as tgl_transaksi',
            DB::raw('COALESCE(NULLIF(TRIM(t.TRANSNO), \'\'), NULLIF(TRIM(t.NOREFF), \'\'), \'-\') as no_transaksi'),
            't.HELPDESK as helpdesk',
            't.METODE as metode',
            DB::raw('COALESCE(NULLIF(TRIM(mst_kelas.jenjang), \'\'), TRIM(scctcust.DESC02), \'-\') as kelas'),
            DB::raw('COALESCE(NULLIF(TRIM(mst_kelas.kelas), \'\'), TRIM(scctcust.DESC03), \'-\') as kelompok'),
            DB::raw('COALESCE(NULLIF(TRIM(scctcust.CODE04), \'\'), \'-\') as gender'),
        ];
    }

    private function fetchRows(array $filters, Request $request): LengthAwarePaginator
    {
        return $this->baseQuery($filters)
            ->select($this->selectColumns())
            ->orderByDesc('t.TRXDATE')
            ->orderByDesc('t.urut')
            ->paginate(self::PER_PAGE)
            ->withQueryString()
            ->through(fn ($row) => $this->mapRow($row));
    }

    private function mapRow(object $row): object
    {
        $topup = (int) ($row->topup ?? 0);
        $fee = $this->parseFee((string) ($row->helpdesk ?? ''), (string) ($row->metode ?? ''));
        $row->fee = $fee;
        $row->total = $topup + $fee;
        $row->user = $this->parseUser((string) ($row->helpdesk ?? ''));

        return $row;
    }

    /** @return array{topup: int, fee: int, grand: int} */
    private function sumTotals(array $filters): array
    {
        $raw = $this->baseQuery($filters)
            ->get(['t.KREDIT as topup', 't.HELPDESK as helpdesk', 't.METODE as metode']);

        $topup = 0;
        $fee = 0;
        foreach ($raw as $row) {
            $nom = (int) ($row->topup ?? 0);
            $topup += $nom;
            $fee += $this->parseFee((string) ($row->helpdesk ?? ''), (string) ($row->metode ?? ''));
        }

        return [
            'topup' => $topup,
            'fee' => $fee,
            'grand' => $topup + $fee,
        ];
    }

    private function parseFee(string $helpdesk, string $metode): int
    {
        if (preg_match('/Biaya:\s*(\d+)/i', $helpdesk, $m)) {
            return (int) $m[1];
        }

        return strcasecmp(trim($metode), 'Cash') === 0 ? self::CASH_FEE : 0;
    }

    private function parseUser(string $helpdesk): string
    {
        if (preg_match('/User:\s*([^\s|]+)/i', $helpdesk, $m)) {
            $user = trim($m[1]);
            if ($user !== '') {
                return $user;
            }
        }

        return '-';
    }

    private function applySchoolScope($query): void
    {
        if (session('auth_is_superadmin')) {
            return;
        }

        $code01 = trim((string) session('auth_sekolah_code01', session('auth_fid', '')));
        if ($code01 === '') {
            $query->whereRaw('1 = 0');

            return;
        }

        $query->whereRaw('TRIM(scctcust.CODE01) = ?', [$code01]);
    }

    private function applyFilters($query, array $filters): void
    {
        if ($filters['nis'] !== '') {
            $query->where('scctcust.NOCUST', 'like', '%' . $filters['nis'] . '%');
        }

        if ($filters['nama'] !== '') {
            $query->where('scctcust.NMCUST', 'like', '%' . $filters['nama'] . '%');
        }

        if ($filters['thn_angkatan'] !== '') {
            $this->applyAngkatanLike($query, $filters['thn_angkatan']);
        }

        if ($filters['dari_tanggal'] !== '') {
            $from = $this->parseDate($filters['dari_tanggal']);
            if ($from) {
                $query->where('t.TRXDATE', '>=', $from->startOfDay());
            }
        }

        if ($filters['sampai_tanggal'] !== '') {
            $to = $this->parseDate($filters['sampai_tanggal']);
            if ($to) {
                $query->where('t.TRXDATE', '<=', $to->endOfDay());
            }
        }

        if ($filters['kelas_id'] !== '') {
            $this->applyKelasFilter($query, $filters['kelas_id']);
        }
    }

    private function applyAngkatanLike($query, string $value): void
    {
        $full = trim($value);
        $base = trim((string) preg_replace('#\s*-\s*.*$#', '', $full));
        $query->where(function ($q) use ($full, $base) {
            $q->whereRaw('TRIM(scctcust.DESC04) = ?', [$full]);
            if ($base !== '' && $base !== $full) {
                $q->orWhereRaw('TRIM(scctcust.DESC04) = ?', [$base])
                    ->orWhereRaw('REPLACE(TRIM(scctcust.DESC04), \' \', \'\') LIKE ?', [str_replace(' ', '', $base) . '%']);
            }
        });
    }

    private function applyKelasFilter($query, string $kelasId): void
    {
        $kelas = DB::connection('sikeu')
            ->table('mst_kelas')
            ->where('id', (int) $kelasId)
            ->first(['id', 'unit', 'jenjang', 'kelas']);

        if (!$kelas) {
            $query->whereRaw('1 = 0');

            return;
        }

        $unit = trim((string) ($kelas->unit ?? ''));
        $jenjang = trim((string) ($kelas->jenjang ?? ''));
        $kelasNama = trim((string) ($kelas->kelas ?? ''));

        $query->where(function ($q) use ($kelasId, $unit, $jenjang, $kelasNama) {
            $q->whereRaw('TRIM(scctcust.CODE03) = ?', [(string) $kelasId]);
            if ($unit !== '' && $jenjang !== '' && $kelasNama !== '') {
                $q->orWhere(function ($q2) use ($unit, $jenjang, $kelasNama) {
                    $q2->whereRaw('TRIM(scctcust.CODE02) = ?', [$unit])
                        ->whereRaw('TRIM(scctcust.DESC02) = ?', [$jenjang])
                        ->whereRaw('TRIM(scctcust.DESC03) = ?', [$kelasNama]);
                });
            }
        });
    }

    private function parseDate(string $value): ?Carbon
    {
        $value = trim($value);
        if ($value === '' || $value === '0000-00-00') {
            return null;
        }

        foreach (['Y-m-d', 'd-m-Y', 'd/m/Y'] as $format) {
            try {
                return Carbon::createFromFormat($format, $value);
            } catch (\Throwable) {
                continue;
            }
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }

    /** @return list<object{thn_aka: string}> */
    private function fetchThnAka(): array
    {
        try {
            return DB::connection('sikeu')
                ->table('mst_thn_aka')
                ->whereNotNull('thn_aka')
                ->where('thn_aka', '!=', '')
                ->orderByDesc('thn_aka')
                ->get(['thn_aka'])
                ->all();
        } catch (\Throwable) {
            return [];
        }
    }

    /** @return list<object> */
    private function fetchKelasOptions(): array
    {
        try {
            return DB::connection('sikeu')
                ->table('mst_kelas')
                ->orderBy('unit')
                ->orderBy('jenjang')
                ->orderBy('kelas')
                ->get(['id', 'unit', 'jenjang', 'kelas'])
                ->all();
        } catch (\Throwable) {
            return [];
        }
    }

    private function fetchSekolahNama(): string
    {
        $code01 = trim((string) session('auth_sekolah_code01', session('auth_fid', '')));
        if ($code01 === '') {
            return trim((string) session('auth_name', 'Raudhatul Quran'));
        }

        try {
            $nama = DB::connection('sikeu')
                ->table('mst_sekolah')
                ->whereRaw('TRIM(CODE01) = ?', [$code01])
                ->value('DESC01');

            if ($nama !== null && trim((string) $nama) !== '') {
                return trim((string) $nama);
            }
        } catch (\Throwable) {
            // ignore
        }

        return trim((string) session('auth_name', 'Raudhatul Quran'));
    }
}
