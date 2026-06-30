<?php

namespace App\Http\Controllers\Smartcard;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DataKartuSiswaController extends Controller
{
    public function index(Request $request): View
    {
        $custid = (int) $request->query('custid', 0);
        $noKartu = trim((string) $request->query('no_kartu', ''));
        $pin = trim((string) $request->query('pin', '123'));
        if ($pin === '') {
            $pin = '123';
        }

        $nama = '';
        $siswaLabel = trim((string) $request->query('siswa_search', ''));
        if ($custid > 0) {
            $siswa = DB::connection('sikeu')
                ->table('scctcust')
                ->where('CUSTID', $custid)
                ->first(['NOCUST', 'NMCUST']);
            if ($siswa) {
                $nama = trim((string) ($siswa->NMCUST ?? ''));
                if ($siswaLabel === '') {
                    $nis = trim((string) ($siswa->NOCUST ?? ''));
                    $siswaLabel = $nis !== '' && $nama !== '' ? $nis . ' - ' . $nama : ($nis !== '' ? $nis : $nama);
                }
            }
        }

        return view('smartcard.data-kartu-siswa.index', [
            'rows' => $this->fetchRows($custid, $noKartu),
            'custid' => $custid,
            'noKartu' => $noKartu,
            'pin' => $pin,
            'nama' => $nama,
            'siswaLabel' => $siswaLabel,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'custid' => ['required', 'integer', 'min:1'],
            'no_kartu' => ['required', 'string', 'max:50'],
            'pin' => ['nullable', 'string', 'max:20'],
        ], [
            'custid.required' => 'Pilih siswa (NIS) terlebih dahulu.',
            'custid.min' => 'Data siswa tidak valid.',
            'no_kartu.required' => 'Nomor kartu wajib diisi.',
        ]);

        $custid = (int) $validated['custid'];
        $noKartu = trim((string) $validated['no_kartu']);
        $pin = trim((string) ($validated['pin'] ?? ''));
        if ($pin === '') {
            $pin = '123';
        }

        $siswa = DB::connection('sikeu')
            ->table('scctcust')
            ->where('CUSTID', $custid)
            ->first(['CUSTID', 'NOCUST', 'NMCUST']);

        if (!$siswa) {
            return redirect()
                ->back()
                ->withInput()
                ->with('smartcard_error', 'Siswa tidak ditemukan di database.');
        }

        $existsCustid = DB::connection('sikeu')
            ->table('sm_pin')
            ->where('CUSTID', $custid)
            ->exists();

        if ($existsCustid) {
            return redirect()
                ->back()
                ->withInput()
                ->with('smartcard_error', 'Siswa ini sudah memiliki kartu. Gunakan pencarian untuk melihat data.');
        }

        $existsPid = DB::connection('sikeu')
            ->table('sm_pin')
            ->where('PID', $noKartu)
            ->exists();

        if ($existsPid) {
            return redirect()
                ->back()
                ->withInput()
                ->with('smartcard_error', 'Nomor kartu sudah digunakan siswa lain.');
        }

        DB::connection('sikeu')->table('sm_pin')->insert([
            'CUSTID' => $custid,
            'PID' => $noKartu,
            'PIN' => $pin,
            'BLOKIR' => 0,
            'urut' => null,
        ]);

        $nis = trim((string) ($siswa->NOCUST ?? ''));
        $nama = trim((string) ($siswa->NMCUST ?? ''));
        $label = $nis !== '' && $nama !== '' ? $nis . ' - ' . $nama : ($nis !== '' ? $nis : $nama);

        return redirect()
            ->route('smartcard.data_kartu', [
                'search' => 1,
                'custid' => $custid,
                'no_kartu' => $noKartu,
                'pin' => $pin,
                'siswa_search' => $label,
            ])
            ->with('smartcard_success', 'Data kartu siswa berhasil disimpan.');
    }

    /**
     * @return Collection<int, object>
     */
    private function fetchRows(int $custid, string $noKartu): Collection
    {
        $query = DB::connection('sikeu')
            ->table('sm_pin')
            ->join('scctcust', 'sm_pin.CUSTID', '=', 'scctcust.CUSTID')
            ->select([
                'scctcust.NOCUST as nis',
                'scctcust.NMCUST as nama',
                'sm_pin.PID as no_kartu',
            ]);

        if ($custid > 0) {
            $query->where('sm_pin.CUSTID', $custid);
        }

        if ($noKartu !== '') {
            $query->where('sm_pin.PID', $noKartu);
        }

        return $query
            ->orderByDesc('scctcust.NOCUST')
            ->limit(1000)
            ->get();
    }
}
