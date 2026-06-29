<?php

namespace App\Http\Controllers\MasterData;

use App\Http\Controllers\Controller;
use App\Services\AmalFatimahApiService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MasterKelasController extends Controller
{
    public function index(Request $request, AmalFatimahApiService $api): View
    {
        $keyword = trim((string) $request->query('q', ''));
        $rows = array_map(static function ($row) {
            if (is_array($row)) {
                return array_change_key_case($row, CASE_LOWER);
            }

            return is_object($row) ? array_change_key_case((array) $row, CASE_LOWER) : [];
        }, $api->getKelas());

        if ($keyword !== '') {
            $keywordLower = mb_strtolower($keyword);
            $rows = array_values(array_filter($rows, static function ($row) use ($keywordLower) {
                $unit = mb_strtolower((string) ($row['unit'] ?? ''));
                // Kolom DB: jenjang = label "Kelas" di UI; kelas = label "Kelompok" di UI.
                $jenjang = mb_strtolower((string) ($row['jenjang'] ?? ''));
                $kelas = mb_strtolower((string) ($row['kelas'] ?? ''));
                $kelompok = mb_strtolower((string) ($row['kelompok'] ?? ''));

                return str_contains($unit, $keywordLower)
                    || str_contains($jenjang, $keywordLower)
                    || str_contains($kelas, $keywordLower)
                    || str_contains($kelompok, $keywordLower);
            }));
        }

        $perPage = 10;
        $currentPage = max(1, (int) $request->query('page', 1));
        $total = count($rows);
        $offset = ($currentPage - 1) * $perPage;
        $pageItems = array_slice($rows, $offset, $perPage);

        $kelasRows = new LengthAwarePaginator(
            $pageItems,
            $total,
            $perPage,
            $currentPage,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );

        return view('master-data.master-kelas.index', [
            'pageTitle' => 'Master Kelas',
            'kelasRows' => $kelasRows,
            'keyword' => $keyword,
        ]);
    }

    public function create(): RedirectResponse
    {
        return redirect()->route('master.kelas')->with('openCreateModal', true);
    }

    public function store(Request $request, AmalFatimahApiService $api): RedirectResponse
    {
        $validated = $request->validate([
            'unit' => ['required', 'string', 'max:100'],
            'kelas' => ['required', 'string', 'max:100'],
            'kelompok' => ['required', 'string', 'max:100'],
        ], [
            'unit.required' => 'Unit wajib diisi.',
            'kelas.required' => 'Kelas wajib diisi.',
            'kelompok.required' => 'Kelompok wajib diisi.',
        ]);

        $result = $api->createKelas([
            'unit' => $validated['unit'],
            'kelas' => $validated['kelas'],
            'kelompok' => $validated['kelompok'],
        ]);

        if (!($result['ok'] ?? false)) {
            $msg = trim((string) ($result['message'] ?? 'Gagal menambahkan data kelas.'));

            return redirect()
                ->route('master.kelas')
                ->withInput()
                ->with('openCreateModal', true)
                ->with('error', $msg)
                ->withErrors(['api' => $msg]);
        }

        return redirect()->route('master.kelas')->with('status', 'Data Master Kelas berhasil ditambahkan.');
    }

    public function edit(string $id): View
    {
        return view('master-data.master-kelas.edit', [
            'pageTitle' => 'Edit Master Kelas',
            'id' => $id,
        ]);
    }

    public function update(Request $request, string $id): RedirectResponse
    {
        return redirect()->route('master.kelas')->with('status', "Data Master Kelas #{$id} terupdate (dummy).");
    }

    public function destroy(string $id, AmalFatimahApiService $api): RedirectResponse
    {
        $kelasId = (int) $id;

        if ($kelasId <= 0) {
            return redirect()->route('master.kelas')->with('status', 'ID kelas tidak valid.');
        }

        $result = $api->deleteKelas($kelasId);

        if (!($result['ok'] ?? false)) {
            return redirect()->route('master.kelas')->with('error', $result['message'] ?? "Gagal menghapus data Master Kelas #{$id}.");
        }

        return redirect()->route('master.kelas')->with('status', "Data Master Kelas #{$id} berhasil dihapus.");
    }
}

