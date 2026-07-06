<?php

namespace App\Http\Controllers\Smartcard;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
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
                $saldoSpp = (int) ($fromList->saldo_spp ?? 0);
                $saldoCashless = (int) ($fromList->saldo_cashless ?? 0);
            } else {
                $siswa = $this->fetchSiswaByCustid($custid);
                if ($siswa && $this->siswaInScope($custid)) {
                    $nama = trim((string) ($siswa->nama ?? ''));
                    $nis = trim((string) ($siswa->nis ?? ''));
                    $saldoSpp = $this->fetchSaldoSpp($custid);
                    $saldoCashless = $this->fetchSaldoCashless($custid);
                } else {
                    $custid = 0;
                }
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
        $user = trim((string) session('auth_username', session('auth_name', '')));
        $helpdeskSpp = $this->buildHelpdeskSpp($user);
        $helpdeskCashless = $this->buildHelpdeskCashless($note, $adminFee, $user);

        try {
            DB::connection('sikeu')->transaction(function () use (
                $custid,
                $trxDate,
                $nominal,
                $totalPotong,
                $transNo,
                $helpdeskSpp,
                $helpdeskCashless
            ) {
                DB::connection('sikeu')->table(self::TRAN_SPP)->insert([
                    'CUSTID' => $custid,
                    'METODE' => 'PINDAH SALDO',
                    'TRXDATE' => $trxDate->format('Y-m-d H:i:s'),
                    'NOREFF' => $transNo,
                    'FIDBANK' => 'CASHLESS',
                    'KDCHANNEL' => 11,
                    'DEBET' => $totalPotong,
                    'KREDIT' => 0,
                    'REFFBANK' => '',
                    'TRANSNO' => $transNo,
                    'HELPDESK' => $helpdeskSpp,
                ]);

                DB::connection('sikeu')->table(self::TRAN_CASHLESS)->insert([
                    'CUSTID' => $custid,
                    'METODE' => 'FROM SALDO',
                    'TRXDATE' => $trxDate->format('Y-m-d H:i:s'),
                    'KREDIT' => $nominal,
                    'DEBET' => 0,
                    'TRANSNO' => $transNo,
                    'NOREFF' => $transNo,
                    'HELPDESK' => $helpdeskCashless,
                    'FIDBANK' => 'PINDAH',
                    'KDCHANNEL' => 0,
                    'REFFBANK' => '',
                ]);
            });
        } catch (\Throwable $e) {
            return redirect()->back()->withInput()->with('smartcard_error', 'Gagal pindah saldo: ' . $e->getMessage());
        }

        $saldoSppBaru = $this->fetchSaldoSpp($custid);
        $saldoCashlessBaru = $this->fetchSaldoCashless($custid);

        return redirect()
            ->route('smartcard.pindah_saldo', [
                'search' => 1,
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

    /** Kolom HELPDESK sccttran pendek — cukup simpan user (seperti data lama). */
    private function buildHelpdeskSpp(string $user): string
    {
        $user = trim($user);

        return mb_substr($user !== '' ? $user : 'pindah', 0, 20);
    }

    private function buildHelpdeskCashless(string $note, int $fee, string $user = ''): string
    {
        $parts = [];
        if ($note !== '') {
            $parts[] = mb_substr($note, 0, 120);
        }
        $parts[] = 'Pindah SPP';
        if ($fee > 0) {
            $parts[] = 'Biaya:' . $fee;
        }
        if (trim($user) !== '') {
            $parts[] = 'User:' . mb_substr(trim($user), 0, 30);
        }

        return mb_substr(implode(' | ', $parts), 0, 255);
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
        $prefix = $trxDate->format('Ymd');

        $lastSpp = DB::connection('sikeu')
            ->table(self::TRAN_SPP)
            ->where(function ($q) use ($prefix) {
                $q->whereRaw('TRIM(TRANSNO) LIKE ?', [$prefix . '%'])
                    ->orWhereRaw('TRIM(NOREFF) LIKE ?', [$prefix . '%']);
            })
            ->orderByDesc('TRANSNO')
            ->value('TRANSNO');

        $lastCash = DB::connection('sikeu')
            ->table(self::TRAN_CASHLESS)
            ->where(function ($q) use ($prefix) {
                $q->whereRaw('TRIM(TRANSNO) LIKE ?', [$prefix . '%'])
                    ->orWhereRaw('TRIM(NOREFF) LIKE ?', [$prefix . '%']);
            })
            ->orderByDesc('TRANSNO')
            ->value('TRANSNO');

        $last = max(
            $this->transNoSeqValue($lastSpp, $prefix),
            $this->transNoSeqValue($lastCash, $prefix)
        );

        return $prefix . str_pad((string) ($last + 1), 3, '0', STR_PAD_LEFT);
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

    private function fetchSaldoSpp(int $custid): int
    {
        $row = DB::connection('sikeu')
            ->table(self::TRAN_SPP)
            ->where('CUSTID', $custid)
            ->selectRaw('CAST(COALESCE(SUM(KREDIT), 0) AS SIGNED) - CAST(COALESCE(SUM(DEBET), 0) AS SIGNED) AS saldo')
            ->first();

        return (int) ($row->saldo ?? 0);
    }

    private function fetchSaldoCashless(int $custid): int
    {
        $row = DB::connection('sikeu')
            ->table(self::TRAN_CASHLESS)
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

        $custids = $rows->pluck('custid')->map(static fn ($id) => (int) $id)->filter(static fn ($id) => $id > 0)->values()->all();
        $saldoSppMap = $this->fetchSaldoMap(self::TRAN_SPP, $custids);
        $saldoCashlessMap = $this->fetchSaldoMap(self::TRAN_CASHLESS, $custids);

        foreach ($rows as $row) {
            $cid = (int) ($row->custid ?? 0);
            $row->saldo_spp = $saldoSppMap[$cid] ?? 0;
            $row->saldo_cashless = $saldoCashlessMap[$cid] ?? 0;
        }

        return ['rows' => $rows, 'total' => $total];
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
