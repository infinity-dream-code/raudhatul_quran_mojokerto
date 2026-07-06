<?php

namespace App\Http\Controllers\Smartcard;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Barryvdh\DomPDF\Facade\Pdf;

class TopupCashController extends Controller
{
    private const PER_PAGE_DEFAULT = 10;

    private const CASH_FEE = 2000;

    private const TRAN_TABLE = 'sccttran_cashless';

    private const NOREFF_CHANNEL = 'WEB';

    private const FIDBANK = '1140002';

    private const METODE_TOPUP = 'TOP UP CASHLESS';

    private const METODE_FEE = 'ADMIN FEE';

    private const TRANSNO_SEQ_LEN = 5;

    public function index(Request $request): View
    {
        $isSearch = $request->boolean('search');
        $custid = (int) $request->query('custid', 0);
        $nisFilter = trim((string) $request->query('nis', $request->query('siswa_search', '')));
        $metode = trim((string) $request->query('metode', 'Cash'));
        if ($metode === '') {
            $metode = 'Cash';
        }
        $tanggalManual = trim((string) $request->query('tanggal_manual', ''));
        $note = trim((string) $request->query('note', ''));

        $perPage = (int) $request->query('per_page', self::PER_PAGE_DEFAULT);
        if (!in_array($perPage, [10, 25, 50], true)) {
            $perPage = self::PER_PAGE_DEFAULT;
        }
        $page = max(1, (int) $request->query('page', 1));

        $nama = '';
        $nis = '';
        $saldo = 0;

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
            } else {
                $siswa = $this->fetchSiswaByCustid($custid);
                if ($siswa) {
                    $nama = trim((string) ($siswa->nama ?? ''));
                    $nis = trim((string) ($siswa->nis ?? ''));
                    $saldo = $this->fetchSaldo($custid);
                } else {
                    $custid = 0;
                }
            }
        }

        $selectedTransNo = '';
        $reprintKuitansi = false;
        if ($custid > 0) {
            $flashTrans = trim((string) session('topup_cash_transno', ''));
            $flashCustid = (int) session('topup_cash_custid', 0);
            if ($flashTrans !== '' && $flashCustid === $custid) {
                $selectedTransNo = $flashTrans;
                $reprintKuitansi = true;
            }
        }

        return view('smartcard.topup-cash.index', [
            'isSearch' => $isSearch,
            'custid' => $custid,
            'nis' => $nis,
            'nama' => $nama,
            'saldo' => $saldo,
            'metode' => $metode,
            'tanggalManual' => $tanggalManual,
            'note' => $note,
            'siswaPaginator' => $siswaPaginator,
            'perPage' => $perPage,
            'cashFee' => self::CASH_FEE,
            'selectedTransNo' => $selectedTransNo,
            'reprintKuitansi' => $reprintKuitansi,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'custid' => ['required', 'integer', 'min:1'],
            'nominal' => ['required', 'integer', 'min:1'],
            'metode' => ['required', 'string', 'max:30'],
            'tanggal_manual' => ['nullable', 'date'],
            'note' => ['nullable', 'string', 'max:255'],
        ], [
            'custid.required' => 'Pilih siswa (NIS) terlebih dahulu.',
            'nominal.required' => 'Nominal top up wajib diisi.',
            'nominal.min' => 'Nominal top up harus lebih dari 0.',
        ]);

        $custid = (int) $validated['custid'];
        $nominal = (int) $validated['nominal'];
        $metode = trim($validated['metode']);
        $note = trim((string) ($validated['note'] ?? ''));
        $tanggalManual = trim((string) ($validated['tanggal_manual'] ?? ''));

        $siswa = $this->fetchSiswaByCustid($custid);
        if (!$siswa) {
            return redirect()
                ->back()
                ->withInput()
                ->with('smartcard_error', 'Siswa tidak ditemukan.');
        }

        if (!$this->siswaInScope($custid)) {
            return redirect()
                ->back()
                ->withInput()
                ->with('smartcard_error', 'Siswa tidak termasuk unit sekolah Anda.');
        }

        $trxDate = $this->resolveTrxDate($tanggalManual);
        $transNo = $this->generateTransNo($trxDate);

        $fee = strcasecmp($metode, 'Cash') === 0 ? self::CASH_FEE : 0;
        $user = trim((string) session('auth_username', session('auth_name', '')));
        $helpdesk = $this->buildHelpdesk($note, $fee, $user);

        try {
            DB::connection('sikeu')->transaction(function () use ($custid, $trxDate, $nominal, $fee, $transNo, $helpdesk) {
                $this->insertTopupRows($custid, $trxDate, $nominal, $fee, $transNo, $helpdesk);
            });
        } catch (\Throwable $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('smartcard_error', 'Gagal menyimpan top up: ' . $e->getMessage());
        }

        $saldoBaru = $this->fetchSaldo($custid);

        return redirect()
            ->route('smartcard.topup_cash', [
                'search' => 1,
                'custid' => $custid,
                'nis' => trim((string) ($siswa->nis ?? '')),
                'metode' => $metode,
                'note' => $note,
            ])
            ->with('smartcard_success', 'Top up berhasil. No transaksi: ' . $transNo . '. Saldo baru: ' . number_format($saldoBaru, 0, ',', '.'))
            ->with('topup_cash_transno', $transNo)
            ->with('topup_cash_custid', $custid);
    }

    public function printKuitansi(Request $request): Response|RedirectResponse
    {
        $validated = $request->validate([
            'custid' => ['required', 'integer', 'min:1'],
            'transno' => ['nullable', 'string', 'max:30'],
            'nominal' => ['nullable', 'integer', 'min:0'],
            'note' => ['nullable', 'string', 'max:255'],
            'metode' => ['nullable', 'string', 'max:30'],
            'tanggal_manual' => ['nullable', 'date'],
            'reprint' => ['nullable', 'boolean'],
        ]);

        $custid = (int) $validated['custid'];
        $transNo = trim((string) ($validated['transno'] ?? ''));
        $nominal = (int) ($validated['nominal'] ?? 0);
        $note = trim((string) ($validated['note'] ?? ''));
        $metode = trim((string) ($validated['metode'] ?? 'Cash'));
        if ($metode === '') {
            $metode = 'Cash';
        }
        $tanggalManual = trim((string) ($validated['tanggal_manual'] ?? ''));

        if (!$this->siswaInScope($custid)) {
            return redirect()
                ->back()
                ->with('smartcard_error', 'Siswa tidak termasuk unit sekolah Anda.');
        }

        $siswa = $this->fetchSiswaByCustid($custid);
        if (!$siswa) {
            return redirect()
                ->back()
                ->with('smartcard_error', 'Siswa tidak ditemukan.');
        }

        $trxDate = $this->resolveTrxDate($tanggalManual);
        $fee = strcasecmp($metode, 'Cash') === 0 ? self::CASH_FEE : 0;
        $nominalTopup = max(0, $nominal);

        if ($transNo !== '') {
            $tran = DB::connection('sikeu')
                ->table(self::TRAN_TABLE)
                ->where('CUSTID', $custid)
                ->where(function ($q) use ($transNo) {
                    $q->whereRaw('TRIM(TRANSNO) = ?', [$transNo])
                        ->orWhereRaw('TRIM(NOREFF) = ?', [$transNo]);
                })
                ->where(function ($q) {
                    $q->whereRaw('UPPER(TRIM(METODE)) = ?', [self::METODE_TOPUP])
                        ->orWhereRaw('UPPER(TRIM(FIDBANK)) = ?', ['TOPUP']);
                })
                ->orderByDesc('TRXDATE')
                ->orderByDesc('urut')
                ->first();

            if ($tran) {
                $trxDate = Carbon::parse($tran->TRXDATE ?? $tran->Tanggal ?? $trxDate);
                $nominalTopup = (int) ($tran->KREDIT ?? 0);
                $helpdesk = trim((string) ($tran->HELPDESK ?? ''));
                if (preg_match('/Biaya:\s*(\d+)/i', $helpdesk, $m)) {
                    $fee = (int) $m[1];
                }
                $note = preg_replace('/\s*\|\s*Biaya:\d+.*$/i', '', $helpdesk);

                $feeRow = DB::connection('sikeu')
                    ->table(self::TRAN_TABLE)
                    ->where('CUSTID', $custid)
                    ->whereRaw('TRIM(TRANSNO) = ?', [trim((string) ($tran->TRANSNO ?? $transNo))])
                    ->whereRaw('UPPER(TRIM(METODE)) = ?', [self::METODE_FEE])
                    ->first();
                if ($feeRow) {
                    $fee = (int) ($feeRow->DEBET ?? 0);
                }
            }
        }

        $saldo = $this->fetchSaldo($custid);
        $jumlah = $saldo > 0 ? $saldo : $nominalTopup;

        if ($transNo === '') {
            $transNo = $this->generateTransNo($trxDate);
        }

        $unit = trim((string) ($siswa->unit ?? ''));
        $kelas = trim((string) ($siswa->kelas ?? ''));
        $sekolahNama = $this->fetchSekolahNama();

        $pdf = Pdf::loadView('smartcard.topup-cash.kuitansi-pdf', [
            'sekolahNama' => $sekolahNama,
            'nama' => trim((string) ($siswa->nama ?? '')),
            'nis' => trim((string) ($siswa->nis ?? '')),
            'unit' => $unit,
            'kelas' => $kelas,
            'nominal' => $jumlah,
            'nominalTopup' => $nominalTopup,
            'fee' => $fee,
            'transNo' => $transNo,
            'trxDate' => $trxDate,
            'teller' => session('auth_name', session('auth_username', 'BMI')),
            'note' => $note,
        ])->setPaper('a5', 'portrait');

        return $pdf->stream('kuitansi-uang-saku-' . $transNo . '.pdf');
    }

    private function insertTopupRows(
        int $custid,
        Carbon $trxDate,
        int $nominal,
        int $fee,
        string $transNo,
        string $helpdesk
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

        DB::connection('sikeu')->table(self::TRAN_TABLE)->insert(array_merge($common, [
            'METODE' => self::METODE_TOPUP,
            'KREDIT' => $nominal,
            'DEBET' => 0,
            'HELPDESK' => $helpdesk !== '' ? $helpdesk : null,
        ]));

        if ($fee > 0) {
            DB::connection('sikeu')->table(self::TRAN_TABLE)->insert(array_merge($common, [
                'METODE' => self::METODE_FEE,
                'KREDIT' => 0,
                'DEBET' => $fee,
                'HELPDESK' => null,
            ]));
        }
    }

    private function buildHelpdesk(string $note, int $fee, string $user = ''): string
    {
        $parts = [];
        if ($note !== '') {
            $parts[] = $note;
        }
        if ($fee > 0) {
            $parts[] = 'Biaya:' . $fee;
        }
        if (trim($user) !== '') {
            $parts[] = 'User:' . trim($user);
        }

        return implode(' | ', $parts);
    }

    private function resolveTrxDate(string $tanggalManual): Carbon
    {
        if ($tanggalManual !== '' && $tanggalManual !== '0000-00-00') {
            try {
                return Carbon::parse($tanggalManual)->setTimeFromTimeString(now()->format('H:i:s'));
            } catch (\Throwable) {
                // fallback ke sekarang
            }
        }

        return now();
    }

    /** Format: WEB + YYYYMMDD + urut 5 digit, contoh WEB2026030300002 */
    private function generateTransNo(?Carbon $trxDate = null): string
    {
        $prefix = self::NOREFF_CHANNEL . ($trxDate ?? now())->format('Ymd');

        $last = DB::connection('sikeu')
            ->table(self::TRAN_TABLE)
            ->where('TRANSNO', 'like', $prefix . '%')
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

        return $prefix . str_pad((string) $seq, self::TRANSNO_SEQ_LEN, '0', STR_PAD_LEFT);
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
                DB::raw('COALESCE(NULLIF(TRIM(mst_kelas.unit), \'\'), TRIM(scctcust.CODE02)) as unit'),
                DB::raw('COALESCE(NULLIF(TRIM(mst_kelas.kelas), \'\'), TRIM(scctcust.DESC03)) as kelas'),
                DB::raw('COALESCE(NULLIF(TRIM(mst_kelas.jenjang), \'\'), TRIM(scctcust.DESC02)) as jenjang'),
                DB::raw('COALESCE(NULLIF(TRIM(mst_kelas.kelas), \'\'), TRIM(scctcust.DESC03)) as kelompok'),
            ]);
    }

    /**
     * @return array{rows: Collection, total: int}
     */
    private function fetchSiswaRowsPaginated(?string $nisFilter, int $perPage, int $page): array
    {
        $base = $this->buildSiswaListQuery($nisFilter);

        $total = (int) (clone $base)->count('scctcust.CUSTID');

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
            ->limit($perPage)
            ->get();

        $saldoMap = $this->fetchSaldoMap(
            $rows->pluck('custid')->map(static fn ($id) => (int) $id)->filter(static fn ($id) => $id > 0)->values()->all()
        );

        foreach ($rows as $row) {
            $row->saldo = $saldoMap[(int) ($row->custid ?? 0)] ?? 0;
        }

        return ['rows' => $rows, 'total' => $total];
    }

    private function buildSiswaListQuery(?string $nisFilter)
    {
        $query = DB::connection('sikeu')
            ->table('scctcust')
            ->leftJoin('mst_kelas', DB::raw('CAST(mst_kelas.id AS CHAR)'), '=', DB::raw('TRIM(scctcust.CODE03)'));

        $this->applySchoolScope($query);
        $this->applySiswaFilter($query, $nisFilter);

        return $query;
    }

    private function applySiswaFilter($query, ?string $nisFilter): void
    {
        if ($nisFilter === null || $nisFilter === '') {
            return;
        }

        $like = '%' . $nisFilter . '%';
        $query->where(function ($q) use ($like) {
            $q->where('scctcust.NOCUST', 'like', $like)
                ->orWhere('scctcust.NMCUST', 'like', $like);
        });
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

    private function fetchSekolahNama(): string
    {
        $code01 = trim((string) session('auth_sekolah_code01', session('auth_fid', '')));
        if ($code01 === '') {
            return trim((string) session('auth_name', 'Sekolah'));
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

        return trim((string) session('auth_name', 'Sekolah'));
    }
}
