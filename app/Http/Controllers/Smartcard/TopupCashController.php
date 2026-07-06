<?php

namespace App\Http\Controllers\Smartcard;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Barryvdh\DomPDF\Facade\Pdf;

class TopupCashController extends Controller
{
    private const MAX_ROWS = 500;

    private const CASH_FEE = 2000;

    private const TRAN_TABLE = 'sccttran_cashless';

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

        $nama = '';
        $nis = '';
        $saldo = 0;

        if ($custid > 0) {
            $siswa = $this->fetchSiswaByCustid($custid);
            if ($siswa) {
                $nama = trim((string) ($siswa->nama ?? ''));
                $nis = trim((string) ($siswa->nis ?? ''));
                $saldo = $this->fetchSaldo($custid);
            } else {
                $custid = 0;
            }
        }

        $siswaRows = collect();
        if ($isSearch) {
            $siswaRows = $this->fetchSiswaRows($nisFilter !== '' ? $nisFilter : null);
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
            'siswaRows' => $siswaRows,
            'cashFee' => self::CASH_FEE,
            'selectedTransNo' => $selectedTransNo,
            'reprintKuitansi' => $reprintKuitansi,
        ]);
    }

    public function lastTransNo(Request $request): \Illuminate\Http\JsonResponse
    {
        $custid = (int) $request->query('custid', 0);
        if ($custid <= 0 || !$this->siswaInScope($custid)) {
            return response()->json(['transno' => '']);
        }

        return response()->json([
            'transno' => $this->fetchLastTransNo($custid),
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
        $helpdesk = $this->buildHelpdesk($note, $fee);

        try {
            DB::connection('sikeu')->transaction(function () use ($custid, $metode, $trxDate, $nominal, $transNo, $helpdesk) {
                $this->insertTopupRow($custid, $metode, $trxDate, $nominal, $transNo, $helpdesk);
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
        $reprint = $request->boolean('reprint');

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

        if ($reprint && $transNo !== '') {
            $tran = DB::connection('sikeu')
                ->table(self::TRAN_TABLE)
                ->where('CUSTID', $custid)
                ->where(function ($q) use ($transNo) {
                    $q->whereRaw('TRIM(TRANSNO) = ?', [$transNo])
                        ->orWhereRaw('TRIM(NOREFF) = ?', [$transNo]);
                })
                ->orderByDesc('TRXDATE')
                ->orderByDesc('urut')
                ->first();

            if ($tran) {
                $trxDate = Carbon::parse($tran->TRXDATE ?? $tran->Tanggal ?? $trxDate);
                $nominal = (int) ($tran->KREDIT ?? 0);
                $helpdesk = trim((string) ($tran->HELPDESK ?? ''));
                if (preg_match('/Biaya:\s*(\d+)/i', $helpdesk, $m)) {
                    $fee = (int) $m[1];
                }
                $note = preg_replace('/\s*\|\s*Biaya:\d+.*$/i', '', $helpdesk);
            }
        }

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
            'nominal' => $nominal,
            'fee' => $fee,
            'transNo' => $transNo,
            'trxDate' => $trxDate,
            'teller' => session('auth_name', session('auth_username', 'BMI')),
            'note' => $note,
        ])->setPaper('a5', 'portrait');

        return $pdf->stream('kuitansi-uang-saku-' . $transNo . '.pdf');
    }

    private function insertTopupRow(
        int $custid,
        string $metode,
        Carbon $trxDate,
        int $nominal,
        string $transNo,
        string $helpdesk
    ): void {
        $payload = [
            'CUSTID' => $custid,
            'METODE' => $metode,
            'TRXDATE' => $trxDate->format('Y-m-d H:i:s'),
            'KREDIT' => $nominal,
            'DEBET' => 0,
            'TRANSNO' => $transNo,
            'NOREFF' => $transNo,
            'HELPDESK' => $helpdesk,
            'FIDBANK' => 'TOPUP',
            'KDCHANNEL' => 0,
            'REFFBANK' => '',
        ];

        DB::connection('sikeu')->table(self::TRAN_TABLE)->insert($payload);
    }

    private function buildHelpdesk(string $note, int $fee): string
    {
        $parts = [];
        if ($note !== '') {
            $parts[] = $note;
        }
        if ($fee > 0) {
            $parts[] = 'Biaya:' . $fee;
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

    /** Format: YYYYMMDD + urut 3 digit, contoh 20260706001 */
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

        if ($last === null || $last === '') {
            $last = DB::connection('sikeu')
                ->table(self::TRAN_TABLE)
                ->whereRaw('TRIM(NOREFF) LIKE ?', [$prefix . '%'])
                ->orderByDesc('NOREFF')
                ->value('NOREFF');
        }

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

    private function fetchSiswaRows(?string $nisFilter): Collection
    {
        $query = DB::connection('sikeu')
            ->table('scctcust')
            ->leftJoin('mst_kelas', DB::raw('CAST(mst_kelas.id AS CHAR)'), '=', DB::raw('TRIM(scctcust.CODE03)'))
            ->leftJoin(DB::raw('(
                SELECT
                    CUSTID,
                    CAST(COALESCE(SUM(KREDIT), 0) AS SIGNED) - CAST(COALESCE(SUM(DEBET), 0) AS SIGNED) AS saldo_net
                FROM ' . self::TRAN_TABLE . '
                GROUP BY CUSTID
            ) sal'), 'sal.CUSTID', '=', 'scctcust.CUSTID')
            ->leftJoin(DB::raw('(
                SELECT
                    CUSTID,
                    SUBSTRING_INDEX(
                        GROUP_CONCAT(
                            COALESCE(NULLIF(TRIM(TRANSNO), \'\'), TRIM(NOREFF))
                            ORDER BY TRXDATE DESC, urut DESC
                            SEPARATOR \'||\'
                        ),
                        \'||\',
                        1
                    ) AS last_transno
                FROM ' . self::TRAN_TABLE . '
                WHERE CAST(COALESCE(KREDIT, 0) AS SIGNED) > 0
                GROUP BY CUSTID
            ) ltran'), 'ltran.CUSTID', '=', 'scctcust.CUSTID');

        $this->applySchoolScope($query);

        if ($nisFilter !== null && $nisFilter !== '') {
            $query->where(function ($q) use ($nisFilter) {
                $like = '%' . $nisFilter . '%';
                $q->where('scctcust.NOCUST', 'like', $like)
                    ->orWhere('scctcust.NMCUST', 'like', $like);
            });
        }

        return $query
            ->select([
                'scctcust.CUSTID as custid',
                'scctcust.NOCUST as nis',
                'scctcust.NMCUST as nama',
                DB::raw('COALESCE(sal.saldo_net, 0) as saldo'),
                DB::raw('COALESCE(NULLIF(TRIM(mst_kelas.unit), \'\'), TRIM(scctcust.CODE02)) as kelas'),
                DB::raw('COALESCE(NULLIF(TRIM(mst_kelas.kelas), \'\'), TRIM(scctcust.DESC03)) as kelompok'),
                DB::raw('COALESCE(NULLIF(TRIM(mst_kelas.jenjang), \'\'), TRIM(scctcust.DESC02)) as jenjang'),
                DB::raw('COALESCE(ltran.last_transno, \'\') as last_transno'),
            ])
            ->orderBy('scctcust.NMCUST')
            ->limit(self::MAX_ROWS)
            ->get();
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

    private function fetchLastTransNo(int $custid): string
    {
        if ($custid <= 0) {
            return '';
        }

        $row = DB::connection('sikeu')
            ->table(self::TRAN_TABLE)
            ->where('CUSTID', $custid)
            ->whereRaw('CAST(COALESCE(KREDIT, 0) AS SIGNED) > 0')
            ->orderByDesc('TRXDATE')
            ->orderByDesc('urut')
            ->first(['TRANSNO', 'NOREFF']);

        if (!$row) {
            return '';
        }

        $no = trim((string) ($row->TRANSNO ?? ''));
        if ($no === '') {
            $no = trim((string) ($row->NOREFF ?? ''));
        }

        return $no;
    }
}
