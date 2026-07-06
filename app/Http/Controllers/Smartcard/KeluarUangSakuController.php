<?php

namespace App\Http\Controllers\Smartcard;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class KeluarUangSakuController extends Controller
{
    private const PER_PAGE = 10;

    private const TRAN_TABLE = 'sccttran_cashless';

    public function index(Request $request): View
    {
        $isSearch = $request->boolean('search');
        $custid = (int) $request->query('custid', 0);
        $nisFilter = trim((string) $request->query('nis', ''));
        $tanggalManual = trim((string) $request->query('tanggal_manual', ''));
        $perPage = self::PER_PAGE;
        $page = max(1, (int) $request->query('page', 1));

        $nama = '';
        $nis = '';
        $saldo = 0;
        $hasActiveCard = false;
        $activeCards = [];

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
            $siswaPaginator = new LengthAwarePaginator(
                $result['rows'],
                $result['total'],
                $perPage,
                $page,
                ['path' => $request->url(), 'query' => $request->query()]
            );
        }

        if ($custid > 0) {
            $fromList = $isSearch
                ? collect($siswaPaginator->items())->first(static fn ($r) => (int) ($r->custid ?? 0) === $custid)
                : null;

            if ($fromList) {
                $nama = trim((string) ($fromList->nama ?? ''));
                $nis = trim((string) ($fromList->nis ?? ''));
                $saldo = (int) ($fromList->saldo ?? 0);
                $hasActiveCard = (int) ($fromList->has_active_card ?? 0) === 1;
            } else {
                $siswa = $this->fetchSiswaByCustid($custid);
                if ($siswa && $this->siswaInScope($custid)) {
                    $nama = trim((string) ($siswa->nama ?? ''));
                    $nis = trim((string) ($siswa->nis ?? ''));
                    $saldo = $this->fetchSaldo($custid);
                    $hasActiveCard = $this->hasActiveCard($custid);
                } else {
                    $custid = 0;
                }
            }

            if ($custid > 0) {
                $activeCards = $this->fetchActiveCards($custid);
            }
        }

        $historyPaginator = $custid > 0
            ? $this->fetchHistoryPaginator($custid, max(1, (int) $request->query('hist_page', 1)), $request)
            : new LengthAwarePaginator([], 0, $perPage, 1, ['path' => $request->url(), 'query' => $request->query()]);

        return view('smartcard.keluar-uang-saku.index', [
            'isSearch' => $isSearch,
            'custid' => $custid,
            'nis' => $nis,
            'nisFilter' => $nisFilter,
            'nama' => $nama,
            'saldo' => $saldo,
            'hasActiveCard' => $hasActiveCard,
            'activeCards' => $activeCards,
            'tanggalManual' => $tanggalManual,
            'siswaPaginator' => $siswaPaginator,
            'historyPaginator' => $historyPaginator,
        ]);
    }

    public function history(Request $request): JsonResponse
    {
        $custid = (int) $request->query('custid', 0);
        if ($custid <= 0 || !$this->siswaInScope($custid)) {
            return response()->json(['ok' => false, 'message' => 'Siswa tidak valid.', 'rows' => [], 'total' => 0]);
        }

        $page = max(1, (int) $request->query('page', 1));
        $paginator = $this->fetchHistoryPaginator($custid, $page, $request);

        $rows = collect($paginator->items())->map(static function ($row) {
            return [
                'tgl' => !empty($row->tgl_transaksi)
                    ? Carbon::parse($row->tgl_transaksi)->format('Y-m-d H:i:s')
                    : '—',
                'metode' => trim((string) ($row->metode ?? '—')),
                'masuk' => (int) ($row->masuk ?? 0),
                'keluar' => (int) ($row->keluar ?? 0),
            ];
        })->values()->all();

        return response()->json([
            'ok' => true,
            'rows' => $rows,
            'total' => $paginator->total(),
            'page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'custid' => ['required', 'integer', 'min:1'],
            'nominal' => ['required', 'integer', 'min:1'],
            'tanggal_manual' => ['nullable', 'date'],
            'nis' => ['nullable', 'string', 'max:50'],
        ], [
            'custid.required' => 'Pilih siswa terlebih dahulu.',
            'nominal.required' => 'Nominal pengeluaran wajib diisi.',
            'nominal.min' => 'Nominal harus lebih dari 0.',
        ]);

        $custid = (int) $validated['custid'];
        $nominal = (int) $validated['nominal'];
        $tanggalManual = trim((string) ($validated['tanggal_manual'] ?? ''));
        $nisFilter = trim((string) ($validated['nis'] ?? ''));

        if (!$this->siswaInScope($custid)) {
            return redirect()->back()->withInput()->with('smartcard_error', 'Siswa tidak termasuk unit sekolah Anda.');
        }

        $siswa = $this->fetchSiswaByCustid($custid);
        if (!$siswa) {
            return redirect()->back()->withInput()->with('smartcard_error', 'Siswa tidak ditemukan.');
        }

        if (!$this->hasActiveCard($custid)) {
            return redirect()->back()->withInput()->with('smartcard_error', 'Siswa tidak memiliki kartu aktif (semua kartu diblokir atau belum terdaftar).');
        }

        $saldo = $this->fetchSaldo($custid);
        if ($nominal > $saldo) {
            return redirect()->back()->withInput()->with(
                'smartcard_error',
                'Saldo tidak mencukupi. Saldo: Rp ' . number_format($saldo, 0, ',', '.') . ', nominal: Rp ' . number_format($nominal, 0, ',', '.') . '.'
            );
        }

        $trxDate = $this->resolveTrxDate($tanggalManual);
        $transNo = $this->generateTransNo($trxDate);
        $user = trim((string) session('auth_username', session('auth_name', '')));
        $helpdesk = $user !== '' ? 'User:' . $user : '';

        try {
            DB::connection('sikeu')->transaction(function () use ($custid, $trxDate, $nominal, $transNo, $helpdesk) {
                DB::connection('sikeu')->table(self::TRAN_TABLE)->insert([
                    'CUSTID' => $custid,
                    'METODE' => 'Cash',
                    'TRXDATE' => $trxDate->format('Y-m-d H:i:s'),
                    'KREDIT' => 0,
                    'DEBET' => $nominal,
                    'TRANSNO' => $transNo,
                    'NOREFF' => $transNo,
                    'HELPDESK' => $helpdesk,
                    'FIDBANK' => 'cash',
                    'KDCHANNEL' => 0,
                    'REFFBANK' => '',
                ]);
            });
        } catch (\Throwable $e) {
            return redirect()->back()->withInput()->with('smartcard_error', 'Gagal menyimpan pengeluaran: ' . $e->getMessage());
        }

        $saldoBaru = $this->fetchSaldo($custid);

        return redirect()
            ->route('smartcard.keluar_uang_saku', [
                'search' => 1,
                'custid' => $custid,
                'nis' => $nisFilter !== '' ? $nisFilter : trim((string) ($siswa->nis ?? '')),
                'tanggal_manual' => $tanggalManual !== '' && $tanggalManual !== '0000-00-00' ? $tanggalManual : null,
                'hist_page' => 1,
            ])
            ->with('smartcard_success', 'Pengeluaran uang saku berhasil. No: ' . $transNo . '. Saldo baru: Rp ' . number_format($saldoBaru, 0, ',', '.'));
    }

    private function fetchHistoryPaginator(int $custid, int $page, Request $request): LengthAwarePaginator
    {
        $query = DB::connection('sikeu')
            ->table(self::TRAN_TABLE)
            ->where('CUSTID', $custid);

        $total = (int) (clone $query)->count();

        $rows = (clone $query)
            ->select([
                'TRXDATE as tgl_transaksi',
                'METODE as metode',
                'KREDIT as masuk',
                'DEBET as keluar',
            ])
            ->orderByDesc('TRXDATE')
            ->orderByDesc('urut')
            ->offset(max(0, ($page - 1) * self::PER_PAGE))
            ->limit(self::PER_PAGE)
            ->get();

        return new LengthAwarePaginator(
            $rows,
            $total,
            self::PER_PAGE,
            $page,
            ['path' => $request->url(), 'query' => $request->query(), 'pageName' => 'hist_page']
        );
    }

    /**
     * @return array{rows: Collection, total: int}
     */
    private function fetchSiswaRowsPaginated(?string $nisFilter, int $perPage, int $page): array
    {
        $base = DB::connection('sikeu')
            ->table('scctcust')
            ->leftJoin('mst_kelas', DB::raw('CAST(mst_kelas.id AS CHAR)'), '=', DB::raw('TRIM(scctcust.CODE03)'));

        $this->applySchoolScope($base);

        if ($nisFilter !== null && $nisFilter !== '') {
            $like = '%' . $nisFilter . '%';
            $base->where(function ($q) use ($like) {
                $q->where('scctcust.NOCUST', 'like', $like)
                    ->orWhere('scctcust.NMCUST', 'like', $like);
            });
        }

        $total = (int) (clone $base)->count('scctcust.CUSTID');

        $rows = (clone $base)
            ->select([
                'scctcust.CUSTID as custid',
                'scctcust.NOCUST as nis',
                'scctcust.NMCUST as nama',
                DB::raw('COALESCE(NULLIF(TRIM(mst_kelas.jenjang), \'\'), TRIM(scctcust.DESC02)) as kelas'),
                DB::raw('COALESCE(NULLIF(TRIM(mst_kelas.kelas), \'\'), TRIM(scctcust.DESC03)) as kelompok'),
                DB::raw('EXISTS (
                    SELECT 1 FROM sm_pin sp
                    WHERE sp.CUSTID = scctcust.CUSTID
                    AND (sp.BLOKIR = 0 OR sp.BLOKIR IS NULL)
                ) as has_active_card'),
            ])
            ->orderBy('scctcust.NMCUST')
            ->offset(max(0, ($page - 1) * $perPage))
            ->limit($perPage)
            ->get();

        $saldoMap = $this->fetchSaldoMap(
            $rows->pluck('custid')->map(static fn ($id) => (int) $id)->filter(static fn ($id) => $id > 0)->values()->all()
        );

        foreach ($rows as $row) {
            $row->saldo = $saldoMap[(int) ($row->custid ?? 0)] ?? 0;
            $row->has_active_card = (int) ($row->has_active_card ?? 0) === 1 ? 1 : 0;
        }

        return ['rows' => $rows, 'total' => $total];
    }

    private function hasActiveCard(int $custid): bool
    {
        return DB::connection('sikeu')
            ->table('sm_pin')
            ->where('CUSTID', $custid)
            ->where(function ($q) {
                $q->where('BLOKIR', 0)->orWhereNull('BLOKIR');
            })
            ->exists();
    }

    /** @return list<object> */
    private function fetchActiveCards(int $custid): array
    {
        return DB::connection('sikeu')
            ->table('sm_pin')
            ->where('CUSTID', $custid)
            ->where(function ($q) {
                $q->where('BLOKIR', 0)->orWhereNull('BLOKIR');
            })
            ->orderBy('PID')
            ->get(['PID as no_kartu', 'BLOKIR as blokir'])
            ->all();
    }

    private function fetchSaldo(int $custid): int
    {
        $row = DB::connection('sikeu')
            ->table(self::TRAN_TABLE)
            ->where('CUSTID', $custid)
            ->selectRaw('CAST(COALESCE(SUM(KREDIT), 0) AS SIGNED) - CAST(COALESCE(SUM(DEBET), 0) AS SIGNED) AS saldo')
            ->first();

        return (int) ($row->saldo ?? 0);
    }

    /** @param list<int> $custids */
    private function fetchSaldoMap(array $custids): array
    {
        if ($custids === []) {
            return [];
        }

        return DB::connection('sikeu')
            ->table(self::TRAN_TABLE)
            ->whereIn('CUSTID', $custids)
            ->selectRaw('CUSTID, CAST(COALESCE(SUM(KREDIT), 0) AS SIGNED) - CAST(COALESCE(SUM(DEBET), 0) AS SIGNED) AS saldo')
            ->groupBy('CUSTID')
            ->pluck('saldo', 'CUSTID')
            ->map(static fn ($saldo) => (int) $saldo)
            ->all();
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

    private function generateTransNo(?Carbon $trxDate = null): string
    {
        $prefix = ($trxDate ?? now())->format('Ymd');

        $last = DB::connection('sikeu')
            ->table(self::TRAN_TABLE)
            ->where(function ($q) use ($prefix) {
                $q->whereRaw('TRIM(TRANSNO) LIKE ?', [$prefix . '%'])
                    ->orWhereRaw('TRIM(NOREFF) LIKE ?', [$prefix . '%']);
            })
            ->orderByDesc('TRANSNO')
            ->value('TRANSNO');

        $seq = 1;
        if ($last !== null && $last !== '') {
            $last = trim((string) $last);
            if (str_starts_with($last, $prefix) && strlen($last) > strlen($prefix)) {
                $tail = substr($last, strlen($prefix));
                if (ctype_digit($tail)) {
                    $seq = (int) $tail + 1;
                }
            }
        }

        return $prefix . str_pad((string) $seq, 3, '0', STR_PAD_LEFT);
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
