<?php

namespace App\Http\Controllers\RekapData;

use App\Http\Controllers\Controller;
use App\Services\AmalFatimahApiService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Pagination\Paginator;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CekPelunasanController extends Controller
{
    public function index(Request $request, AmalFatimahApiService $api): View|StreamedResponse|Response
    {
        $export = $request->query('export');
        if ($export === 'csv') {
            return $this->exportCsv($request, $api);
        }
        if ($export === 'xls') {
            return $this->exportXls($request, $api);
        }

        [$filters, $perPage, $page] = $this->pagingFromRequest($request);
        $bundle = $api->loadPenerimaanFilterShell();
        $filterOptions = $bundle['filterOptions'] ?? [];

        $paginator = new Paginator([], $perPage, $page, [
            'path' => $request->url(),
            'query' => $request->query(),
        ]);

        return view('rekap-data.cek-pelunasan.index', [
            'pageTitle' => 'Cek Pelunasan',
            'filters' => $filters,
            'filterOptions' => $filterOptions,
            'rowsPaginator' => $paginator,
            'cekPelunasanRowsUrl' => route('rekap.cek_pelunasan.rows', $request->query()),
        ]);
    }

    public function rows(Request $request, AmalFatimahApiService $api): JsonResponse
    {
        [$filters, $perPage, $page] = $this->pagingFromRequest($request);

        $res = $api->getCekPelunasanRows($filters, $perPage, ($page - 1) * $perPage);
        if (!$res['ok']) {
            return response()->json([
                'ok' => false,
                'message' => $res['message'] ?? 'Gagal memuat data.',
            ], 422);
        }

        $rows = $res['data']['rows'] ?? [];
        $meta = is_array($res['data']['meta'] ?? null) ? $res['data']['meta'] : [];
        $hasMore = (bool) ($meta['has_more'] ?? false);

        $n = count($rows);
        $firstItem = $n > 0 ? (($page - 1) * $perPage + 1) : 0;
        $lastItem = $n > 0 ? ($firstItem + $n - 1) : 0;

        $q = $request->query();
        $prevUrl = $page > 1 ? route('rekap.cek_pelunasan', array_merge($q, ['page' => $page - 1])) : null;
        $nextUrl = $hasMore ? route('rekap.cek_pelunasan', array_merge($q, ['page' => $page + 1])) : null;

        return response()->json([
            'ok' => true,
            'rows' => $rows,
            'meta' => $meta,
            'page' => $page,
            'per_page' => $perPage,
            'has_more' => $hasMore,
            'first_item' => $firstItem,
            'last_item' => $lastItem,
            'prev_url' => $prevUrl,
            'next_url' => $nextUrl,
        ]);
    }

    public function printKartuSiswa(Request $request, AmalFatimahApiService $api): Response|RedirectResponse
    {
        $custids = $request->input('custids', []);
        if (!is_array($custids)) {
            $custids = [];
        }
        $custids = array_values(array_unique(array_filter(
            array_map(static fn ($v) => (int) $v, $custids),
            static fn ($n) => $n > 0
        )));

        if ($custids === []) {
            return redirect()->back()->with('export_error', 'Pilih minimal satu siswa (centang di tabel).');
        }

        $filters = [
            'thn_akademik' => trim((string) $request->input('thn_akademik', '')),
            'kelas_id' => trim((string) $request->input('kelas_id', '')),
            'nis' => trim((string) $request->input('nis', '')),
            'thn_angkatan' => trim((string) $request->input('thn_angkatan', '')),
            'nama' => trim((string) $request->input('nama', '')),
            'nama_tagihan' => trim((string) $request->input('nama_tagihan', '')),
            'cari' => trim((string) $request->input('cari', '')),
        ];

        $res = $api->getCekPelunasanCards($custids, $filters);
        if (!$res['ok']) {
            return redirect()->back()->with('export_error', $res['message'] ?? 'Gagal mengambil data kartu siswa.');
        }

        $cards = is_array($res['data']['cards'] ?? null) ? $res['data']['cards'] : [];
        if ($cards === []) {
            return redirect()->back()->with('export_error', 'Tidak ada data kartu siswa untuk pilihan ini.');
        }

        $pdf = Pdf::loadView('keuangan.tagihan-siswa.data-tagihan-kartu-siswa-pdf', [
            'cards' => $cards,
        ])->setPaper('a4', 'portrait');

        return $pdf->stream('kartu-siswa-cek-pelunasan-' . date('Ymd-His') . '.pdf');
    }

    /**
     * @return array{0: bool, 1: list<array<string, mixed>>, 2: string}
     */
    private function exportDataset(Request $request, AmalFatimahApiService $api): array
    {
        [$filters] = $this->pagingFromRequest($request);
        $res = $api->getCekPelunasanRowsExportAll($filters, 8000);
        if (!$res['ok']) {
            return [false, [], (string) ($res['message'] ?? 'Gagal memuat data.')];
        }
        $rows = $res['data']['rows'] ?? [];

        return [true, is_array($rows) ? $rows : [], ''];
    }

    private function exportCsv(Request $request, AmalFatimahApiService $api): StreamedResponse
    {
        [$ok, $rows, $msg] = $this->exportDataset($request, $api);
        if (!$ok) {
            abort(422, $msg);
        }

        $fn = 'cek-pelunasan-' . date('Ymd-His') . '.csv';

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            if ($out === false) {
                return;
            }
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, [
                'No', 'Tahun Pelajaran', 'NIS', 'No Pendaftaran', 'Nama', 'Nama Tagihan',
                'Kode Post', 'Nama Post', 'Nominal', 'Total Tagihan', 'Lunas',
            ], ';');
            $no = 1;
            foreach ($rows as $r) {
                if (!is_array($r)) {
                    continue;
                }
                fputcsv($out, [
                    $no++,
                    $r['tahun_pelajaran'] ?? '',
                    $r['nis'] ?? '',
                    $r['no_pendaftaran'] ?? '',
                    $r['nama'] ?? '',
                    $r['nama_tagihan'] ?? '',
                    $r['kode_post'] ?? '',
                    $r['nama_post'] ?? '',
                    (string) ((int) ($r['nominal'] ?? 0)),
                    (string) ((int) ($r['tagihan'] ?? 0)),
                    ((int) ($r['lunas'] ?? 0)) === 1 ? 'LUNAS' : 'BELUM',
                ], ';');
            }
            fclose($out);
        }, $fn, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function exportXls(Request $request, AmalFatimahApiService $api): Response
    {
        [$ok, $rows, $msg] = $this->exportDataset($request, $api);
        if (!$ok) {
            abort(422, $msg);
        }

        $esc = static fn (string $s): string => htmlspecialchars($s, ENT_XML1 | ENT_COMPAT, 'UTF-8');

        $buf = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $buf .= '<?mso-application progid="Excel.Sheet"?>' . "\n";
        $buf .= '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet" xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet">' . "\n";
        $buf .= '<Styles><Style ss:ID="hdr"><Font ss:Bold="1"/></Style><Style ss:ID="n"><NumberFormat ss:Format="#,##0"/></Style></Styles>' . "\n";
        $buf .= '<Worksheet ss:Name="Cek Pelunasan"><Table>' . "\n";
        $buf .= '<Row>';
        foreach ([
            'TAHUN PELAJARAN', 'NIS', 'NO PENDAFTARAN', 'NAMA', 'NAMA TAGIHAN',
            'KODE POST', 'NAMA POST', 'NOMINAL', 'TOTAL TAGIHAN', 'LUNAS',
        ] as $h) {
            $buf .= '<Cell ss:StyleID="hdr"><Data ss:Type="String">' . $esc($h) . '</Data></Cell>';
        }
        $buf .= '</Row>' . "\n";

        foreach ($rows as $r) {
            if (!is_array($r)) {
                continue;
            }
            $nominal = (int) ($r['nominal'] ?? 0);
            $tagihan = (int) ($r['tagihan'] ?? 0);
            $lunas = ((int) ($r['lunas'] ?? 0)) === 1 ? 'LUNAS' : 'BELUM';
            $buf .= '<Row>';
            $buf .= '<Cell><Data ss:Type="String">' . $esc((string) ($r['tahun_pelajaran'] ?? '')) . '</Data></Cell>';
            $buf .= '<Cell><Data ss:Type="String">' . $esc((string) ($r['nis'] ?? '')) . '</Data></Cell>';
            $buf .= '<Cell><Data ss:Type="String">' . $esc((string) ($r['no_pendaftaran'] ?? '')) . '</Data></Cell>';
            $buf .= '<Cell><Data ss:Type="String">' . $esc((string) ($r['nama'] ?? '')) . '</Data></Cell>';
            $buf .= '<Cell><Data ss:Type="String">' . $esc((string) ($r['nama_tagihan'] ?? '')) . '</Data></Cell>';
            $buf .= '<Cell><Data ss:Type="String">' . $esc((string) ($r['kode_post'] ?? '')) . '</Data></Cell>';
            $buf .= '<Cell><Data ss:Type="String">' . $esc((string) ($r['nama_post'] ?? '')) . '</Data></Cell>';
            $buf .= '<Cell ss:StyleID="n"><Data ss:Type="Number">' . $nominal . '</Data></Cell>';
            $buf .= '<Cell ss:StyleID="n"><Data ss:Type="Number">' . $tagihan . '</Data></Cell>';
            $buf .= '<Cell><Data ss:Type="String">' . $esc($lunas) . '</Data></Cell>';
            $buf .= '</Row>' . "\n";
        }

        $buf .= '</Table></Worksheet></Workbook>';

        $fn = 'cek-pelunasan-' . date('Ymd-His') . '.xls';

        return response($buf, 200, [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $fn . '"',
        ]);
    }

    /**
     * @return array{0: array<string, string>, 1: int, 2: int}
     */
    private function pagingFromRequest(Request $request): array
    {
        $perPage = (int) $request->query('per_page', 10);
        if (!in_array($perPage, [10, 25, 50, 100], true)) {
            $perPage = 10;
        }
        $page = max(1, (int) $request->query('page', 1));

        $filters = [
            'thn_akademik' => trim((string) $request->query('thn_akademik', '')),
            'kelas_id' => trim((string) $request->query('kelas_id', '')),
            'nis' => trim((string) $request->query('nis', '')),
            'thn_angkatan' => trim((string) $request->query('thn_angkatan', '')),
            'nama' => trim((string) $request->query('nama', '')),
            'nama_tagihan' => trim((string) $request->query('nama_tagihan', '')),
            'cari' => trim((string) $request->query('cari', '')),
        ];

        return [$filters, $perPage, $page];
    }
}
