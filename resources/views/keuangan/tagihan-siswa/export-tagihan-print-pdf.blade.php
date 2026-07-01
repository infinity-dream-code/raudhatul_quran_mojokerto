<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Export Tagihan</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #111; margin: 0; padding: 10px; }
        .sheet { width: 100%; page-break-after: always; }
        .sheet:last-child { page-break-after: auto; }
        .head { border-bottom: 3px solid #111; padding-bottom: 6px; margin-bottom: 6px; }
        .row { width: 100%; border-collapse: collapse; }
        .row td { border: 0; vertical-align: middle; }
        .logo-cell { width: 72px; }
        .logo { width: 56px; height: 56px; object-fit: contain; display: block; }
        .mid { text-align: center; }
        .nm { font-size: 28px; font-weight: 700; margin: 0; line-height: 1; }
        .addr { font-size: 10px; margin: 2px 0 0; }
        .title { text-align: center; font-size: 18px; font-weight: 700; margin: 6px 0; }
        .meta-wrap { width: 100%; border-collapse: collapse; margin-bottom: 5px; }
        .meta-wrap td { border: 0; padding: 1px 3px; font-size: 10px; }
        .lbl { width: 14%; font-weight: 700; }
        .val { width: 36%; }
        .lbl-r { width: 14%; font-weight: 700; }
        .val-r { width: 36%; }
        .tbl { width: 100%; border-collapse: collapse; table-layout: fixed; }
        .tbl th, .tbl td { border: 1px solid #444; padding: 4px 5px; }
        .tbl th { background: #fff; font-size: 10px; text-align: center; font-weight: 700; }
        .ctr { text-align: center; }
        .num { text-align: right; }
        .status-belum { color: #b91c1c; font-weight: 700; }
        .total-row td { font-weight: 700; }
        .foot { margin-top: 6px; text-align: right; font-size: 10px; font-weight: 700; }
        .err { border: 1px solid #fecaca; background: #fef2f2; color: #b91c1c; padding: 8px; font-weight: 700; margin-bottom: 8px; }
    </style>
</head>
<body>
    @php
        use App\Support\BrandLogo;

        $logoDataUri = BrandLogo::dataUri();
        $cleanMeta = static function ($v): string {
            $s = trim((string) $v);
            $s = ltrim($s, ": \t\n\r\0\x0B");

            return trim($s);
        };
        $todayId = \Carbon\Carbon::now('Asia/Jakarta')->locale('id');
        $sheetList = is_array($sheets ?? null) && ($sheets ?? []) !== []
            ? $sheets
            : [['rows' => is_array($rows ?? null) ? $rows : []]];
    @endphp

    @if (($errorMessage ?? '') !== '' && (($sheetList[0]['rows'] ?? []) === []))
        <div class="sheet">
            <div class="head">
                <table class="row">
                    <tr>
                        <td class="logo-cell">@if ($logoDataUri)<img class="logo" src="{{ $logoDataUri }}" alt="Logo">@endif</td>
                        <td class="mid">
                            <p class="nm">MA'HAD TAHFIDZ RAUDHATUL QUR'AN</p>
                            <p class="addr">Mojokerto, Jawa Timur</p>
                        </td>
                        <td class="logo-cell"></td>
                    </tr>
                </table>
            </div>
            <div class="title">Tagihan Siswa</div>
            <div class="err">{{ $errorMessage }}</div>
        </div>
    @else
        @foreach ($sheetList as $sheet)
            @php
                $sheetRows = is_array($sheet['rows'] ?? null) ? $sheet['rows'] : [];
                $first = (count($sheetRows) > 0 && is_array($sheetRows[0] ?? null)) ? $sheetRows[0] : [];
                $nisDigits = preg_replace('/\D+/', '', (string) ($first['nis'] ?? ''));
                $noVa = trim((string) ($first['no_va'] ?? ''));
                if ($noVa === '') {
                    $noVa = \App\Support\VaFormatter::fromNis($nisDigits !== '' ? $nisDigits : '');
                }
                $angkatan = $cleanMeta($first['angkatan'] ?? '');
                if ($angkatan === '') {
                    $angkatan = trim((string) ($filters['thn_angkatan'] ?? ''));
                }
                if ($angkatan === '') {
                    $tahunAka = trim((string) ($first['tahun_aka'] ?? ''));
                    if (preg_match('/^\d{4}/', $tahunAka, $m) === 1) {
                        $angkatan = $m[0];
                    }
                }
                if ($angkatan === '') {
                    $angkatan = '-';
                }
                $totalTagihan = 0;
                foreach ($sheetRows as $row) {
                    if (is_array($row)) {
                        $totalTagihan += (int) ($row['tagihan'] ?? 0);
                    }
                }
            @endphp

            <div class="sheet">
                <div class="head">
                    <table class="row">
                        <tr>
                            <td class="logo-cell">@if ($logoDataUri)<img class="logo" src="{{ $logoDataUri }}" alt="Logo">@endif</td>
                            <td class="mid">
                                <p class="nm">MA'HAD TAHFIDZ RAUDHATUL QUR'AN</p>
                                <p class="addr">Mojokerto, Jawa Timur</p>
                            </td>
                            <td class="logo-cell"></td>
                        </tr>
                    </table>
                </div>

                <div class="title">Tagihan Siswa</div>

                <table class="meta-wrap">
                    <tr>
                        <td class="lbl">NIS</td><td class="val">: {{ $cleanMeta($first['nis'] ?? '') !== '' ? $cleanMeta($first['nis'] ?? '') : '-' }}</td>
                        <td class="lbl-r">Kelas</td><td class="val-r">: {{ $cleanMeta($first['kelas'] ?? '') !== '' ? $cleanMeta($first['kelas'] ?? '') : '-' }}</td>
                    </tr>
                    <tr>
                        <td class="lbl">NO VA</td><td class="val">: {{ $noVa }}</td>
                        <td class="lbl-r">Unit</td><td class="val-r">: {{ $cleanMeta($first['unit'] ?? '') !== '' ? $cleanMeta($first['unit'] ?? '') : '-' }}</td>
                    </tr>
                    <tr>
                        <td class="lbl">Nama Siswa</td><td class="val">: {{ $cleanMeta($first['nama'] ?? '') !== '' ? $cleanMeta($first['nama'] ?? '') : '-' }}</td>
                        <td class="lbl-r">Angkatan</td><td class="val-r">: {{ $angkatan }}</td>
                    </tr>
                </table>

                <table class="tbl">
                    <thead>
                        <tr>
                            <th style="width:5%">#</th>
                            <th style="width:20%">Tahun Akademik</th>
                            <th>Nama Tagihan</th>
                            <th style="width:23%">Jumlah</th>
                            <th style="width:18%">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($sheetRows as $idx => $r)
                            @php $st = strtoupper((string) ($r['status'] ?? '-')); @endphp
                            <tr>
                                <td class="ctr">{{ $idx + 1 }}</td>
                                <td class="ctr">{{ trim((string) ($r['tahun_aka'] ?? '')) !== '' ? $r['tahun_aka'] : '-' }}</td>
                                <td>{{ trim((string) ($r['nama_tagihan'] ?? '')) !== '' ? $r['nama_tagihan'] : '-' }}</td>
                                <td class="num">Rp {{ number_format((int) ($r['tagihan'] ?? 0), 0, ',', '.') }}</td>
                                <td class="ctr {{ $st === 'BELUM LUNAS' ? 'status-belum' : '' }}">{{ $st }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="ctr" style="padding: 14px; color:#6b7280;">Tidak ada data</td></tr>
                        @endforelse
                        <tr class="total-row">
                            <td colspan="3">Total Tagihan</td>
                            <td class="num">Rp {{ number_format($totalTagihan, 0, ',', '.') }}</td>
                            <td></td>
                        </tr>
                    </tbody>
                </table>

                <div class="foot">Mojokerto, {{ $todayId->translatedFormat('l, d F Y') }}</div>
            </div>
        @endforeach
    @endif
</body>
</html>
