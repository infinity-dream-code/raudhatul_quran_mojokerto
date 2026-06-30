<?php

namespace App\Http\Controllers\Smartcard;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class SettingBatasanKartuController extends Controller
{
    private const PER_PAGE = 10;

    public function index(Request $request): View
    {
        $isSearch = $request->boolean('search');
        $periode = $isSearch ? $this->normalizePeriode((string) $request->query('periode', '')) : '';
        $batasBelanjaHari = $isSearch ? $this->parseAmount($request->query('batas_belanja_hari', '')) : '';
        $batasCash = $isSearch ? $this->parseAmount($request->query('batas_cash', '')) : '';
        $aktif = $isSearch && $request->boolean('aktif');

        return view('smartcard.setting-batasan-kartu.index', [
            'batasanRows' => $this->fetchRows($periode, $isSearch),
            'isSearch' => $isSearch,
            'periode' => $isSearch ? trim((string) $request->query('periode', '')) : '',
            'batasBelanjaHari' => $batasBelanjaHari,
            'batasCash' => $batasCash,
            'aktif' => $aktif,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'periode' => ['required', 'string', 'max:20'],
            'batas_belanja_hari' => ['required', 'string', 'max:30'],
            'batas_cash' => ['required', 'string', 'max:30'],
            'aktif' => ['nullable', 'boolean'],
        ], [
            'periode.required' => 'Periode wajib diisi.',
            'batas_belanja_hari.required' => 'Batas belanja harian wajib diisi.',
            'batas_cash.required' => 'Batas cash wajib diisi.',
        ]);

        $periode = $this->normalizePeriode($validated['periode']);
        if ($periode === '') {
            return redirect()
                ->back()
                ->withInput()
                ->with('smartcard_error', 'Format periode tidak valid. Contoh: 202606 atau 2026-06.');
        }

        $batasBelanjaHari = $this->parseAmount($validated['batas_belanja_hari']);
        $batasCash = $this->parseAmount($validated['batas_cash']);

        if ($batasBelanjaHari === '' || !is_numeric($batasBelanjaHari)) {
            return redirect()->back()->withInput()->with('smartcard_error', 'Batas belanja harian harus berupa angka.');
        }
        if ($batasCash === '' || !is_numeric($batasCash)) {
            return redirect()->back()->withInput()->with('smartcard_error', 'Batas cash harus berupa angka.');
        }

        $exists = DB::connection('sikeu')
            ->table('sm_batasan')
            ->where('periode', $periode)
            ->exists();

        if ($exists) {
            return redirect()
                ->back()
                ->withInput()
                ->with('smartcard_error', 'Periode sudah ada. Gunakan Lihat untuk melihat data atau gunakan periode lain.');
        }

        DB::connection('sikeu')->table('sm_batasan')->insert([
            'periode' => $periode,
            'batas_belanja_hari' => (int) $batasBelanjaHari,
            'batas_cash' => (int) $batasCash,
            'aktif' => $request->boolean('aktif') ? 1 : 0,
            'kelompok_kantin' => null,
            'urut' => null,
        ]);

        return redirect()
            ->route('smartcard.batasan_kartu')
            ->with('smartcard_success', 'Setting batasan kartu berhasil disimpan.');
    }

    private function fetchRows(string $periode, bool $isSearch): LengthAwarePaginator
    {
        $query = DB::connection('sikeu')
            ->table('sm_batasan')
            ->select([
                'periode',
                'batas_belanja_hari',
                'batas_cash',
                'aktif',
            ]);

        if ($isSearch && $periode !== '') {
            $query->where('periode', $periode);
        }

        return $query
            ->orderByDesc('periode')
            ->paginate(self::PER_PAGE)
            ->withQueryString();
    }

    private function normalizePeriode(string $value): string
    {
        $digits = preg_replace('/\D+/', '', $value) ?? '';
        if (strlen($digits) === 6) {
            return $digits;
        }

        return '';
    }

    private function parseAmount(mixed $value): string
    {
        $raw = preg_replace('/[^\d]/', '', (string) $value) ?? '';

        return $raw;
    }
}
