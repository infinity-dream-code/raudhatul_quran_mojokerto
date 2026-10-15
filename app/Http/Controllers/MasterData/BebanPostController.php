<?php

namespace App\Http\Controllers\MasterData;

use App\Http\Controllers\Controller;
use App\Services\AmalFatimahApiService;
use App\Support\TableSort;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;

class BebanPostController extends Controller
{
    public function index(Request $request, AmalFatimahApiService $api): View
    {
        $thnMasuk = trim((string) $request->query('thn_masuk', ''));
        $kodeProd = trim((string) $request->query('kode_prod', ''));
        $kodeAkun = trim((string) $request->query('kode_akun', ''));
        $nominal = trim((string) $request->query('nominal', ''));
        $kelompok = trim((string) $request->query('kelompok', ''));
        $keyword = trim((string) $request->query('q', ''));
        $sort = TableSort::resolve($request->query(), 'kodeakun', 'asc');
        $page = max(1, (int) $request->query('page', 1));
        $perPage = (int) $request->query('per_page', 10);
        if (!in_array($perPage, [10, 25, 50], true)) {
            $perPage = 10;
        }

        $filters = [
            'thn_masuk' => $thnMasuk !== '' ? $thnMasuk : null,
            'kode_prod' => $kodeProd !== '' ? $kodeProd : null,
            'kode_akun' => $kodeAkun !== '' ? $kodeAkun : null,
            'nominal' => $nominal !== '' ? preg_replace('/\D+/', '', $nominal) : null,
        ];

        $allRows = $this->fetchAllBebanPostRows($api, $filters);
        if ($kelompok !== '') {
            $needleKelompok = mb_strtolower($kelompok);
            $allRows = array_values(array_filter($allRows, static function ($row) use ($needleKelompok) {
                return mb_strtolower(trim((string) ($row['nama_kelas'] ?? ''))) === $needleKelompok;
            }));
        }
        if ($keyword !== '') {
            $needle = mb_strtolower($keyword);
            $allRows = array_values(array_filter($allRows, static function ($row) use ($needle) {
                $kode = mb_strtolower((string) ($row['kodeakun'] ?? ''));
                $nama = mb_strtolower((string) ($row['namaakun'] ?? ''));
                $nml = mb_strtolower((string) ($row['nominal'] ?? ''));
                return str_contains($kode, $needle) || str_contains($nama, $needle) || str_contains($nml, $needle);
            }));
        }

        $allRows = TableSort::sortRows($allRows, $sort['sort_by'], $sort['sort_dir'], [
            'kodeakun' => 'kodeakun',
            'kode' => 'kodeakun',
            'namaakun' => 'namaakun',
            'nama_post' => 'namaakun',
            'nominal' => 'nominal',
        ], 'kodeakun');

        $total = count($allRows);
        $lastPage = max(1, (int) ceil($total / $perPage));
        if ($page > $lastPage) {
            $page = $lastPage;
        }
        $offset = ($page - 1) * $perPage;
        $rows = array_slice($allRows, $offset, $perPage);
        $bebanRows = new LengthAwarePaginator($rows, $total, $perPage, $page, [
            'path' => $request->url(),
            'query' => $request->query(),
        ]);

        $filterOptions = $api->getFilterBebanPost();
        $kelasFromApi = is_array($filterOptions['kelas'] ?? null) ? $filterOptions['kelas'] : [];
        if ($kelasFromApi === []) {
            $kelasFromApi = $api->getKelas();
        }
        $filterOptions['kelas'] = array_map(static function ($row) {
            $item = is_array($row) ? array_change_key_case($row, CASE_LOWER) : [];

            return [
                'id' => (string) ($item['id'] ?? ''),
                'kelas' => (string) ($item['kelas'] ?? ''),
                'unit' => (string) ($item['unit'] ?? ''),
                'jenjang' => (string) ($item['jenjang'] ?? ''),
                'kelompok' => (string) ($item['kelompok'] ?? ''),
            ];
        }, $kelasFromApi);
        $filterOptions['kelompok'] = self::kelompokOptions($filterOptions['kelas']);

        return view('master-data.beban-post.index', [
            'pageTitle' => 'Beban Post',
            'bebanRows' => $bebanRows,
            'filterOptions' => $filterOptions,
            'thnMasuk' => $thnMasuk,
            'kodeProd' => $kodeProd,
            'kodeAkun' => $kodeAkun,
            'nominal' => $nominal,
            'kelompok' => $kelompok,
            'perPage' => $perPage,
            'keyword' => $keyword,
            'sortBy' => $sort['sort_by'],
            'sortDir' => $sort['sort_dir'],
        ]);
    }

    public function create(AmalFatimahApiService $api): View
    {
        $filterOptions = $api->getFilterBebanPost();
        $thnAkaOptions = is_array($filterOptions['thn_masuk'] ?? null)
            ? $filterOptions['thn_masuk']
            : $api->getThnAka();
        $kelasOptions = array_map(static function ($row) {
            $item = is_array($row) ? array_change_key_case($row, CASE_LOWER) : [];
            return [
                'id' => (string) ($item['id'] ?? ''),
                'kelas' => (string) ($item['kelas'] ?? ''),
                'unit' => (string) ($item['unit'] ?? ''),
                'kelompok' => (string) ($item['kelompok'] ?? ''),
                'jenjang' => (string) ($item['jenjang'] ?? ''),
            ];
        }, $api->getKelas());
        $akunOptions = $api->getAkun();

        $kelasGroups = [];
        foreach ($kelasOptions as $kls) {
            if ($kls['id'] === '') {
                continue;
            }
            $key = $kls['unit'] . '|' . $kls['jenjang'];
            if (!isset($kelasGroups[$key])) {
                $groupLabel = implode(' - ', array_values(array_filter([$kls['unit'], $kls['jenjang']], static fn ($v) => $v !== '')));
                $kelasGroups[$key] = $groupLabel !== '' ? $groupLabel : '-';
            }
        }

        return view('master-data.beban-post.create', [
            'pageTitle' => 'Tambah Beban Post',
            'thnAkaOptions' => $thnAkaOptions,
            'kelasOptions' => $kelasOptions,
            'kelasGroups' => $kelasGroups,
            'akunOptions' => $akunOptions,
        ]);
    }

    public function store(Request $request, AmalFatimahApiService $api): RedirectResponse
    {
        $validated = $request->validate([
            'thn_masuk' => ['required', 'string', 'max:50'],
            'kode_prod' => ['required', 'string', 'max:20'],
            'kode_akun' => ['required', 'string', 'max:10'],
            'nominal' => ['required', 'string', 'max:50'],
        ], [
            'thn_masuk.required' => 'Tahun Angkatan wajib diisi.',
            'kode_prod.required' => 'Kelas dan Kelompok Kelas wajib dipilih.',
            'kode_akun.required' => 'Kode Akun wajib dipilih.',
            'nominal.required' => 'Nominal wajib diisi.',
        ]);

        $result = $api->createBebanPost([
            'thn_masuk' => $validated['thn_masuk'],
            'kode_prod' => $validated['kode_prod'],
            'kode_akun' => $validated['kode_akun'],
            'nominal' => preg_replace('/\D+/', '', $validated['nominal']),
        ]);

        if (!($result['ok'] ?? false)) {
            return back()->withInput()->withErrors([
                'api' => $result['message'] ?? 'Gagal menambahkan beban post dari web service.',
            ]);
        }

        return redirect()->route('master.beban_post')->with('status', 'Data Beban Post berhasil ditambahkan.');
    }

    public function edit(string $id): View
    {
        return view('master-data.beban-post.edit', [
            'pageTitle' => 'Edit Beban Post',
            'id' => $id,
        ]);
    }

    public function update(Request $request, string $id): RedirectResponse
    {
        return redirect()->route('master.beban_post')->with('status', "Data Beban Post #{$id} terupdate (dummy).");
    }

    public function destroy(string $id): RedirectResponse
    {
        return redirect()->route('master.beban_post')->with('status', "Data Beban Post #{$id} terhapus (dummy).");
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function fetchAllBebanPostRows(AmalFatimahApiService $api, array $filters): array
    {
        $rows = [];
        $chunkSize = 200;
        for ($offset = 0; $offset < 20000; $offset += $chunkSize) {
            $chunk = $api->getBebanPost($filters, $chunkSize, $offset);
            $rows = array_merge($rows, $chunk);
            if (count($chunk) < $chunkSize) {
                break;
            }
        }

        return $rows;
    }

    /**
     * @param list<array<string, string>> $kelasRows
     * @return list<string>
     */
    private static function kelompokOptions(array $kelasRows): array
    {
        $values = [];
        foreach ($kelasRows as $row) {
            $v = trim((string) ($row['kelas'] ?? ''));
            if ($v !== '') {
                $values[mb_strtolower($v)] = $v;
            }
        }
        natcasesort($values);

        return array_values($values);
    }
}

