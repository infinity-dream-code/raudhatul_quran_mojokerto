<?php

namespace App\Http\Controllers\MasterData;

use Barryvdh\DomPDF\Facade\Pdf;
use App\Http\Controllers\Controller;
use App\Services\AmalFatimahApiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DataSiswaController extends Controller
{
    public function index(Request $request, AmalFatimahApiService $api): View
    {
        $perPage = (int) $request->query('per_page', 10);
        if (!in_array($perPage, [10, 25, 50, 100], true)) {
            $perPage = 10;
        }
        $page = max(1, (int) $request->query('page', 1));
        $ui = $this->extractUiFilters($request);
        $filters = $this->toWsFilters($ui);

        $total = $api->getSiswaCount($filters);
        $offset = ($page - 1) * $perPage;
        $rows = $api->getSiswa($filters, $perPage, $offset);

        $siswaRows = new LengthAwarePaginator(
            $rows,
            $total,
            $perPage,
            $page,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );

        $filterOptions = $api->getFilterSiswa();

        return view('master-data.data-siswa.index', [
            'pageTitle' => 'Data Siswa',
            'siswaRows' => $siswaRows,
            'filterOptions' => $filterOptions,
            'angkatan' => $ui['angkatan'],
            'sekolah' => $ui['sekolah'],
            'kelas' => $ui['kelas'],
            'kelompok' => $ui['kelompok'],
            'siswa' => $ui['siswa'],
            'keyword' => $ui['q'],
            'perPage' => $perPage,
        ]);
    }

    public function exportExcel(Request $request, AmalFatimahApiService $api): StreamedResponse
    {
        $rows = $api->getSiswa($this->toWsFilters($this->extractUiFilters($request)), 200, 0);

        $filename = 'data-siswa-' . now()->format('Ymd-His') . '.xls';
        $headers = [
            'No',
            'NIS',
            'NO VA',
            'NAMA',
            'NO PENDAFTARAN',
            'UNIT',
            'KELAS',
            'KELOMPOK',
            'ANGKATAN',
            'STATUS',
            'JENIS KELAMIN',
            'ALAMAT',
            'WALI',
        ];

        $callback = static function () use ($rows, $headers): void {
            $output = fopen('php://output', 'w');
            if ($output === false) {
                return;
            }

            fwrite($output, implode("\t", $headers) . PHP_EOL);

            foreach ($rows as $index => $row) {
                $r = array_change_key_case((array) $row, CASE_LOWER);
                $nocust = trim((string) ($r['nocust'] ?? ''));
                $vaDigits = preg_replace('/\D+/', '', $nocust);
                $noVa = $vaDigits !== '' ? ('7510050' . $vaDigits) : '-';
                $unit = trim((string) ($r['code02'] ?? ''));
                if ($unit === '') {
                    $c01 = trim((string) ($r['code01'] ?? ''));
                    $uSek = trim((string) ($r['unit_sekolah'] ?? ''));
                    $unit = ($c01 !== '' && $uSek !== '') ? ($c01 . ' — ' . $uSek) : (($uSek !== '') ? $uSek : (($c01 !== '') ? $c01 : '-'));
                }
                $wali = trim((string) ($r['wali'] ?? $r['genus'] ?? ''));
                $line = [
                    (string) ($index + 1),
                    $nocust !== '' ? $nocust : '-',
                    $noVa,
                    trim((string) ($r['nmcust'] ?? '')) !== '' ? (string) $r['nmcust'] : '-',
                    trim((string) ($r['num2nd'] ?? '')) !== '' ? (string) $r['num2nd'] : '-',
                    $unit !== '' ? $unit : '-',
                    trim((string) ($r['desc02'] ?? '')) !== '' ? (string) $r['desc02'] : '-',
                    trim((string) ($r['desc03'] ?? '')) !== '' ? (string) $r['desc03'] : '-',
                    trim((string) ($r['desc04'] ?? '')) !== '' ? (string) $r['desc04'] : '-',
                    self::siswaStatusLabel($r['stcust'] ?? null),
                    self::siswaGenderLabel($r['code04'] ?? ''),
                    trim((string) ($r['desc05'] ?? '')) !== '' ? (string) $r['desc05'] : '-',
                    $wali !== '' ? $wali : '-',
                ];
                fwrite($output, implode("\t", array_map(static fn ($v) => str_replace(["\r", "\n", "\t"], ' ', $v), $line)) . PHP_EOL);
            }

            fclose($output);
        };

        return response()->streamDownload($callback, $filename, [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
        ]);
    }

    public function exportPdf(Request $request, AmalFatimahApiService $api): Response
    {
        $ui = $this->extractUiFilters($request);
        $rows = $api->getSiswa($this->toWsFilters($ui), 200, 0);

        $pdf = Pdf::loadView('master-data.data-siswa.export-pdf', [
            'rows' => $rows,
            'filters' => $ui,
            'printedAt' => now('Asia/Jakarta'),
        ])->setPaper('a4', 'landscape');

        return $pdf->stream('data-siswa-' . now()->format('Ymd-His') . '.pdf');
    }

    public function create(): View
    {
        return view('master-data.data-siswa.create', [
            'pageTitle' => 'Tambah Data Siswa',
        ]);
    }

    public function store(Request $request, AmalFatimahApiService $api): RedirectResponse
    {
        $validated = $request->validate([
            'nis' => ['required', 'string', 'max:50'],
            'nama' => ['required', 'string', 'max:200'],
        ], [
            'nis.required' => 'NIS wajib diisi.',
            'nama.required' => 'Nama wajib diisi.',
        ]);

        $result = $api->createSiswa([
            'nis' => $validated['nis'],
            'nama' => $validated['nama'],
        ]);

        if (!($result['ok'] ?? false)) {
            return back()
                ->withInput()
                ->withErrors(['api' => $result['message'] ?? 'Gagal menyimpan data siswa.']);
        }

        return redirect()->route('master.data_siswa')->with('status', 'Data siswa berhasil disimpan.');
    }

    public function edit(string $id): View
    {
        return view('master-data.data-siswa.edit', [
            'pageTitle' => 'Edit Data Siswa',
            'id' => $id,
        ]);
    }

    public function update(Request $request, string $id): RedirectResponse
    {
        return redirect()->route('master.data_siswa')->with('status', "Data Siswa #{$id} terupdate (dummy).");
    }

    public function destroy(string $id): RedirectResponse
    {
        return redirect()->route('master.data_siswa')->with('status', "Data Siswa #{$id} terhapus (dummy).");
    }

    public function resetLoginAndroid(string $id, AmalFatimahApiService $api): JsonResponse
    {
        $custid = (int) $id;
        $result = $api->resetLoginAndroid($custid);

        if (!$result['ok']) {
            return response()->json(['message' => $result['message']], 422);
        }

        return response()->json(['message' => $result['message']], 200);
    }

    public function resetLoginAndroidBulk(Request $request, AmalFatimahApiService $api): JsonResponse
    {
        $custids = $request->input('custids', []);
        if (!is_array($custids)) {
            return response()->json(['message' => 'Format data tidak valid'], 422);
        }

        $result = $api->resetLoginAndroidBulk($custids);

        if (!$result['ok']) {
            return response()->json(['message' => $result['message']], 422);
        }

        return response()->json(['message' => $result['message']], 200);
    }

    private function extractUiFilters(Request $request): array
    {
        return [
            'angkatan' => trim((string) $request->query('angkatan', '')),
            'sekolah' => trim((string) $request->query('sekolah', '')),
            'kelas' => trim((string) $request->query('kelas', '')),
            'kelompok' => trim((string) $request->query('kelompok', '')),
            'siswa' => trim((string) $request->query('siswa', '')),
            'q' => trim((string) $request->query('q', '')),
        ];
    }

    private function toWsFilters(array $ui): array
    {
        $search = $ui['q'] !== '' ? $ui['q'] : $ui['siswa'];
        $search = $search !== '' ? $search : null;

        return [
            'search' => $search,
            'desc04' => $ui['angkatan'] !== '' ? $ui['angkatan'] : null,
            'code01' => $ui['sekolah'] !== '' ? $ui['sekolah'] : null,
            'desc02' => $ui['kelas'] !== '' ? $ui['kelas'] : null,
            'desc03' => $ui['kelompok'] !== '' ? $ui['kelompok'] : null,
        ];
    }

    private static function siswaStatusLabel(mixed $stcust): string
    {
        $st = trim((string) $stcust);

        return ($st === '1' || $st === '1.0') ? 'Aktif' : 'Tidak Aktif';
    }

    private static function siswaGenderLabel(mixed $code04): string
    {
        $g = strtoupper(trim((string) $code04));
        if ($g === '') {
            return '-';
        }
        if (in_array($g, ['L', 'LK', 'LAKI', 'LAKI-LAKI', 'PRIA', 'M'], true)) {
            return 'Laki-laki';
        }
        if (in_array($g, ['P', 'PR', 'PEREMPUAN', 'WANITA', 'F'], true)) {
            return 'Perempuan';
        }

        return $g;
    }
}
