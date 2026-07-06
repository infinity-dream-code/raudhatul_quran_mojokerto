<?php

namespace App\Http\Controllers\Smartcard;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PindahSaldoController extends Controller
{
    private const PER_PAGE_DEFAULT = 10;

    private const ADMIN_FEE = 1000;

    private const TRAN_SPP = 'sccttran';

    private const TRAN_CASHLESS = 'sccttran_cashless';

    private const NOREFF_CHANNEL = 'WEB';

    private const FIDBANK = '1140002';

    private const METODE_SPP = 'PINDAH SALDO';

    private const METODE_CASHLESS_CREDIT = 'FROM SALDO';

    private const METODE_FEE = 'ADMIN FEE';

    private const TRANSNO_SEQ_LEN = 5;

    public function index(Request $request): View
    {
        $isSearch = $request->boolean('search');
        $custid = (int) $request->query('custid', 0);
        $nisFilter = trim((string) $request->query('nis', $request->query('siswa_search', '')));
        $tanggalManual = trim((string) $request->query('tanggal_manual', ''));
        $note = trim((string) $request->query('note', ''));

        $perPage = self::PER_PAGE_DEFAULT;
        $page = max(1, (int) $request->query('page', 1));

        $nama = '';
        $nis = '';
        $saldoSpp = 0;
        $saldoCashless = 0;

        $siswaPaginator = new LengthAwarePaginator([], 0, $perPage, $page, [
            'path' => $request->url(),
            'query' => $request->query(),
        ]);

        if ($isSearch) {
            $result = $this->fetchSiswaRowsPaginated(
                $nisFilter !== '' ? $nisFilter : null,
                $perPage,
                $page
            );
            $siswaPaginator = new Paginator(
                $result['rows'],
                $perPage,
                $page,
                ['path' => $request->url(), 'query' => $request->query()]
            );
        }

        if ($custid > 0) {
            $fromList = ($isSearch ?? false)
                ? collect($siswaPaginator->items())->first(static fn ($r) => (int) ($r->custid ?? 0) === $custid)
                : null;

            $siswa = $fromList ?: $this->fetchSiswaByCustidInScope($custid);
            if ($siswa) {
                $nama = trim((string) ($siswa->nama ?? ''));
                $nis = trim((string) ($siswa->nis ?? ''));
                $saldos = $this->fetchSaldos($custid);
                $saldoSpp = $saldos['saldo_spp'];
                $saldoCashless = $saldos['saldo_cashless'];
            } else {
                $custid = 0;
            }
        }

        return view('smartcard.pindah-saldo.index', [
            'isSearch' => $isSearch,
            'custid' => $custid,
            'nis' => $nis,
            'nama' => $nama,
            'saldoSpp' => $saldoSpp,
            'saldoCashless' => $saldoCashless,
            'tanggalManual' => $tanggalManual,
            'note' => $note,
            'siswaPaginator' => $siswaPaginator,
            'adminFee' => self::ADMIN_FEE,
        ]);
    }

    public function saldo(Request $request): JsonResponse
    {
        $custid = (int) $request->query('custid', 0);
        if ($custid <= 0) {
            return response()->json(['ok' => false, 'message' => 'Siswa tidak valid.'], 422);
        }

        $siswa = $this->fetchSiswaByCustidInScope($custid);
        if (!$siswa) {
            return response()->json(['ok' => false, 'message' => 'Siswa tidak ditemukan.'], 404);
        }

        $saldos = $this->fetchSaldos($custid);

        return response()->json([
            'ok' => true,
            'data' => [
                'custid' => $custid,
                'nis' => trim((string) ($siswa->nis ?? '')),
                'nama' => trim((string) ($siswa->nama ?? '')),
                'saldo_spp' => $saldos['saldo_spp'],
                'saldo_cashless' => $saldos['saldo_cashless'],
            ],
        ]);
    }

    public function batchSaldo(Request $request): JsonResponse
    {
        $raw = trim((string) $request->query('custids', ''));
        if ($raw === '') {
            return response()->json(['ok' => true, 'saldo_spp' => []]);
        }

        $custids = array_values(array_unique(array_filter(array_map(
            static fn ($id) => (int) $id,
            preg_split('/\s*,\s*/', $raw) ?: []
        ), static fn ($id) => $id > 0)));

        $custids = array_slice($custids, 0, 20);
        if ($custids === []) {
            return response()->json(['ok' => true, 'saldo_spp' => []]);
        }

        $allowed = $this->filterCustidsInScope($custids);
        if ($allowed === []) {
            return response()->json(['ok' => true, 'saldo_spp' => []]);
        }

        return response()->json([
            'ok' => true,
            'saldo_spp' => $this->fetchSaldoMap(self::TRAN_SPP, $allowed),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'custid' => ['required', 'integer', 'min:1'],
            'nominal' => ['required', 'integer', 'min:1'],
            'tanggal_manual' => ['nullable', 'date'],
            'note' => ['nullable', 'string', 'max:255'],
        ], [
            'custid.required' => 'Pilih siswa (NIS) terlebih dahulu.',
            'nominal.required' => 'Nominal pindah wajib diisi.',
            'nominal.min' => 'Nominal pindah harus lebih dari 0.',
        ]);

        $custid = (int) $validated['custid'];
        $nominal = (int) $validated['nominal'];
        $note = trim((string) ($validated['note'] ?? ''));
        $tanggalManual = trim((string) ($validated['tanggal_manual'] ?? ''));
        $adminFee = self::ADMIN_FEE;
        $totalPotong = $nominal + $adminFee;

        $siswa = $this->fetchSiswaByCustid($custid);
        if (!$siswa) {
            return redirect()->back()->withInput()->with('smartcard_error', 'Siswa tidak ditemukan.');
        }

        if (!$this->siswaInScope($custid)) {
            return redirect()->back()->withInput()->with('smartcard_error', 'Siswa tidak termasuk unit sekolah Anda.');
        }

        $saldoSpp = $this->fetchSaldoSpp($custid);
        if ($totalPotong > $saldoSpp) {
            return redirect()->back()->withInput()->with(
                'smartcard_error',
                'Saldo SPP tidak mencukupi. Saldo: Rp ' . number_format($saldoSpp, 0, ',', '.')
                . ', dibutuhkan: Rp ' . number_format($totalPotong, 0, ',', '.')
                . ' (pindah Rp ' . number_format($nominal, 0, ',', '.') . ' + admin Rp ' . number_format($adminFee, 0, ',', '.') . ').'
            );
        }

        $trxDate = $this->resolveTrxDate($tanggalManual);
        $transNo = $this->generateTransNo($trxDate);

        try {
            DB::connection('sikeu')->transaction(function () use (
                $custid,
                $trxDate,
                $nominal,
                $totalPotong,
                $adminFee,
                $transNo
            ) {
                DB::connection('sikeu')->table(self::TRAN_SPP)->insert([
                    'CUSTID' => $custid,
                    'METODE' => self::METODE_SPP,
                    'TRXDATE' => $trxDate->format('Y-m-d H:i:s'),
                    'NOREFF' => self::NOREFF_CHANNEL,
                    'FIDBANK' => self::FIDBANK,
                    'KDCHANNEL' => 0,
                    'DEBET' => $totalPotong,
                    'KREDIT' => 0,
                    'REFFBANK' => '',
                    'TRANSNO' => $transNo,
                    'HELPDESK' => null,
                ]);

                $this->insertCashlessRows($custid, $trxDate, $nominal, $adminFee, $transNo);
            });
        } catch (\Throwable $e) {
            return redirect()->back()->withInput()->with('smartcard_error', 'Gagal pindah saldo: ' . $e->getMessage());
        }

        $saldoSppBaru = $saldoSpp - $totalPotong;
        $saldoCashlessBaru = $this->fetchSaldoCashless($custid);

        return redirect()
            ->route('smartcard.pindah_saldo', [
                'custid' => $custid,
                'nis' => trim((string) ($siswa->nis ?? '')),
                'note' => $note,
            ])
            ->with(
                'smartcard_success',
                'Pindah saldo berhasil. No: ' . $transNo
                . '. Saldo SPP: Rp ' . number_format($saldoSppBaru, 0, ',', '.')
                . ' | Uang saku: Rp ' . number_format($saldoCashlessBaru, 0, ',', '.')
            );
    }

    private function insertCashlessRows(
        int $custid,
        Carbon $trxDate,
        int $nominal,
        int $adminFee,
        string $transNo
    ): void {
        $common = [
            'CUSTID' => $custid,
            'TRXDATE' => $trxDate->format('Y-m-d H:i:s'),
            'NOREFF' => self::NOREFF_CHANNEL,
            'FIDBANK' => self::FIDBANK,
            'KDCHANNEL' => 0,
            'REFFBANK' => '',
            'TRANSNO' => $transNo,
        ];

        DB::connection('sikeu')->table(self::TRAN_CASHLESS)->insert(array_merge($common, [
            'METODE' => self::METODE_CASHLESS_CREDIT,
            'KREDIT' => $nominal,
            'DEBET' => 0,
            'HELPDESK' => null,
        ]));

        if ($adminFee > 0) {
            DB::connection('sikeu')->table(self::TRAN_CASHLESS)->insert(array_merge($common, [
                'METODE' => self::METODE_FEE,
                'KREDIT' => 0,
                'DEBET' => $adminFee,
                'HELPDESK' => null,
            ]));
        }
    }

    private function resolveTrxDate(string $tanggalManual): Carbon
    {
        if ($tanggalManual !== '' && $tanggalManual !== '0000-00-00') {
            try {
                return Carbon::parse($tanggalManual)->setTimeFromTimeString(now()->format('H:i:s'));
            } catch (\Throwable) {
                // fallback
            }
        }

        return now();
    }

    private function generateTransNo(Carbon $trxDate): string
    {
        $prefix = self::NOREFF_CHANNEL . $trxDate->format('Ymd');

        $lastSpp = DB::connection('sikeu')
            ->table(self::TRAN_SPP)
            ->where('TRANSNO', 'like', $prefix . '%')
            ->orderByDesc('TRANSNO')
            ->value('TRANSNO');

        $lastCash = DB::connection('sikeu')
            ->table(self::TRAN_CASHLESS)
            ->where('TRANSNO', 'like', $prefix . '%')
            ->orderByDesc('TRANSNO')
            ->value('TRANSNO');

        $seq = max(
            $this->transNoSeqValue($lastSpp, $prefix),
            $this->transNoSeqValue($lastCash, $prefix)
        ) + 1;

        return $prefix . str_pad((string) $seq, self::TRANSNO_SEQ_LEN, '0', STR_PAD_LEFT);
    }

    /** @return array{saldo_spp: int, saldo_cashless: int} */
    private function fetchSaldos(int $custid): array
    {
        $row = DB::connection('sikeu')->selectOne(
            'SELECT
                (SELECT CAST(COALESCE(SUM(KREDIT), 0) - COALESCE(SUM(DEBET), 0) AS SIGNED)
                 FROM ' . self::TRAN_SPP . ' WHERE CUSTID = ?) AS saldo_spp,
                (SELECT CAST(COALESCE(SUM(KREDIT), 0) - COALESCE(SUM(DEBET), 0) AS SIGNED)
                 FROM ' . self::TRAN_CASHLESS . ' WHERE CUSTID = ?) AS saldo_cashless',
            [$custid, $custid]
        );

        return [
            'saldo_spp' => (int) ($row->saldo_spp ?? 0),
            'saldo_cashless' => (int) ($row->saldo_cashless ?? 0),
        ];
    }

    private function fetchSaldoSpp(int $custid): int
    {
        return $this->fetchSaldos($custid)['saldo_spp'];
    }

    private function transNoSeqValue(?string $transNo, string $prefix): int
    {
        if ($transNo === null || trim($transNo) === '') {
            return 0;
        }

        $transNo = trim($transNo);
        if (!str_starts_with($transNo, $prefix) || strlen($transNo) <= strlen($prefix)) {
            return 0;
        }

        $tail = substr($transNo, strlen($prefix));

        return ctype_digit($tail) ? (int) $tail : 0;
    }

    private function fetchSaldoCashless(int $custid): int
    {
        return $this->fetchSaldoCashlessOnly($custid);
    }

    private function fetchSaldoCashlessOnly(int $custid): int
    {
        $row = DB::connection('sikeu')
            ->table(self::TRAN_CASHLESS)
            ->where('CUSTID', $custid)
            ->selectRaw('CAST(COALESCE(SUM(KREDIT), 0) - COALESCE(SUM(DEBET), 0) AS SIGNED) AS saldo')
            ->first();

        return (int) ($row->saldo ?? 0);
    }

    private function fetchSiswaByCustid(int $custid): ?object
    {
        return DB::connection('sikeu')
            ->table('scctcust')
            ->leftJoin('mst_kelas', DB::raw('CAST(mst_kelas.id AS CHAR)'), '=', DB::raw('TRIM(scctcust.CODE03)'))
            ->where('scctcust.CUSTID', $custid)
            ->first([
                'scctcust.CUSTID as custid',
                'scctcust.NOCUST as nis',
                'scctcust.NMCUST as nama',
            ]);
    }

    private function fetchSiswaByCustidInScope(int $custid): ?object
    {
        $query = DB::connection('sikeu')
            ->table('scctcust')
            ->where('scctcust.CUSTID', $custid);

        $this->applySchoolScope($query);

        return $query->first([
            'scctcust.CUSTID as custid',
            'scctcust.NOCUST as nis',
            'scctcust.NMCUST as nama',
        ]);
    }

    /** @param list<int> $custids @return list<int> */
    private function filterCustidsInScope(array $custids): array
    {
        if ($custids === []) {
            return [];
        }

        $query = DB::connection('sikeu')
            ->table('scctcust')
            ->whereIn('CUSTID', $custids);

        $this->applySchoolScope($query);

        return $query->pluck('CUSTID')->map(static fn ($id) => (int) $id)->all();
    }

    /**
     * @return array{rows: Collection}
     */
    private function fetchSiswaRowsPaginated(?string $nisFilter, int $perPage, int $page): array
    {
        $base = $this->buildSiswaListQuery($nisFilter);

        $rows = (clone $base)
            ->select([
                'scctcust.CUSTID as custid',
                'scctcust.NOCUST as nis',
                'scctcust.NMCUST as nama',
                DB::raw('COALESCE(NULLIF(TRIM(mst_kelas.unit), \'\'), TRIM(scctcust.CODE02)) as kelas'),
                DB::raw('COALESCE(NULLIF(TRIM(mst_kelas.kelas), \'\'), TRIM(scctcust.DESC03)) as kelompok'),
                DB::raw('COALESCE(NULLIF(TRIM(mst_kelas.jenjang), \'\'), TRIM(scctcust.DESC02)) as jenjang'),
            ])
            ->orderBy('scctcust.NMCUST')
            ->offset(max(0, ($page - 1) * $perPage))
            ->limit($perPage + 1)
            ->get();

        if ($rows->count() > $perPage) {
            $rows = $rows->slice(0, $perPage)->values();
        }

        foreach ($rows as $row) {
            $row->saldo_spp = null;
            $row->saldo_cashless = 0;
        }

        return ['rows' => $rows];
    }

    private function buildSiswaListQuery(?string $nisFilter)
    {
        $query = DB::connection('sikeu')
            ->table('scctcust')
            ->leftJoin('mst_kelas', DB::raw('CAST(mst_kelas.id AS CHAR)'), '=', DB::raw('TRIM(scctcust.CODE03)'));

        $this->applySchoolScope($query);

        if ($nisFilter !== null && $nisFilter !== '') {
            $like = '%' . $nisFilter . '%';
            $query->where(function ($q) use ($like) {
                $q->where('scctcust.NOCUST', 'like', $like)
                    ->orWhere('scctcust.NMCUST', 'like', $like);
            });
        }

        return $query;
    }

    /** @param list<int> $custids */
    private function fetchSaldoMap(string $table, array $custids): array
    {
        if ($custids === []) {
            return [];
        }

        return DB::connection('sikeu')
            ->table($table)
            ->whereIn('CUSTID', $custids)
            ->selectRaw('CUSTID, CAST(COALESCE(SUM(KREDIT), 0) AS SIGNED) - CAST(COALESCE(SUM(DEBET), 0) AS SIGNED) AS saldo')
            ->groupBy('CUSTID')
            ->pluck('saldo', 'CUSTID')
            ->map(static fn ($saldo) => (int) $saldo)
            ->all();
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

    private function siswaInScope(int $custid): bool
    {
        if (session('auth_is_superadmin')) {
            return true;
        }

        $code01 = trim((string) session('auth_sekolah_code01', session('auth_fid', '')));
        if ($code01 === '') {
            return false;
        }

        return DB::connection('sikeu')
            ->table('scctcust')
            ->where('CUSTID', $custid)
            ->whereRaw('TRIM(CODE01) = ?', [$code01])
            ->exists();
    }
}
