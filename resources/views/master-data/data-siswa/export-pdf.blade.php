<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 8px; color: #111827; margin: 0; padding: 12px; }
        .head-top { border-bottom: 3px solid #111; padding-bottom: 6px; margin-bottom: 8px; }
        .head-row { width: 100%; border-collapse: collapse; }
        .head-row td { border: 0; vertical-align: middle; }
        .logo-cell { width: 70px; }
        .logo { width: 56px; height: 56px; object-fit: contain; display: block; }
        .text-cell { text-align: center; }
        .yayasan { font-size: 20px; font-weight: 700; margin: 0; }
        .sub { font-size: 10px; margin: 2px 0 0; }
        .title { font-size: 14px; font-weight: 700; margin: 0 0 4px; text-align: center; }
        .meta { font-size: 8px; color: #4b5563; margin: 0 0 10px; text-align: center; line-height: 1.5; }
        table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        th, td { border: 1px solid #7b7b7b; padding: 4px 5px; vertical-align: top; word-wrap: break-word; }
        th { background: #1e3a8a; color: #fff; font-size: 7px; text-align: center; font-weight: 700; }
        tbody tr:nth-child(even) td { background: #f3f4f6; }
        .ctr { text-align: center; }
    </style>
</head>
<body>
    @php
        use App\Support\BrandLogo;
        $logoDataUri = BrandLogo::dataUri();
        $genderLabel = static function ($code04): string {
            $g = strtoupper(trim((string) $code04));
            if ($g === '') return '-';
            if (in_array($g, ['L', 'LK', 'LAKI', 'LAKI-LAKI', 'PRIA', 'M'], true)) return 'Laki-laki';
            if (in_array($g, ['P', 'PR', 'PEREMPUAN', 'WANITA', 'F'], true)) return 'Perempuan';
            return $g;
        };
        $statusLabel = static function ($stcust): string {
            $st = trim((string) $stcust);
            return ($st === '1' || $st === '1.0') ? 'Aktif' : 'Tidak Aktif';
        };
    @endphp

    <div class="head-top">
        <table class="head-row">
            <tr>
                <td class="logo-cell">
                    @if ($logoDataUri)
                        <img class="logo" src="{{ $logoDataUri }}" alt="Logo MA'HAD TAHFIDZ RAUDHATUL QUR'AN">
                    @endif
                </td>
                <td class="text-cell">
                    <p class="yayasan">MA'HAD TAHFIDZ RAUDHATUL QUR'AN</p>
                    <p class="sub">Mojokerto, Jawa Timur</p>
                </td>
                <td class="logo-cell"></td>
            </tr>
        </table>
    </div>

    <h1 class="title">Data Siswa</h1>
    <p class="meta">
        Dicetak {{ $printedAt->format('d/m/Y H:i') }} WIB · Total data: {{ count($rows ?? []) }}<br>
        Filter: Angkatan {{ $filters['angkatan'] !== '' ? $filters['angkatan'] : 'Semua' }},
        Sekolah {{ $filters['sekolah'] !== '' ? $filters['sekolah'] : 'Semua' }},
        Kelas {{ $filters['kelas'] !== '' ? $filters['kelas'] : 'Semua' }},
        Kelompok {{ $filters['kelompok'] !== '' ? $filters['kelompok'] : 'Semua' }},
        Siswa {{ $filters['siswa'] !== '' ? $filters['siswa'] : 'Semua' }},
        Cari {{ $filters['q'] !== '' ? $filters['q'] : 'Semua' }}
    </p>

    <table>
        <thead>
            <tr>
                <th style="width:3%">No</th>
                <th style="width:8%">NIS</th>
                <th style="width:9%">NO VA</th>
                <th style="width:12%">NAMA</th>
                <th style="width:8%">No Pendaftaran</th>
                <th style="width:9%">Unit</th>
                <th style="width:6%">Kelas</th>
                <th style="width:7%">Kelompok</th>
                <th style="width:6%">Angkatan</th>
                <th style="width:6%">Status</th>
                <th style="width:7%">Jenis Kelamin</th>
                <th style="width:11%">Alamat</th>
                <th style="width:8%">Wali</th>
            </tr>
        </thead>
        <tbody>
            @forelse (($rows ?? []) as $index => $row)
                @php
                    $r = array_change_key_case((array) $row, CASE_LOWER);
                    $nocust = trim((string) ($r['nocust'] ?? ''));
                    $noVa = \App\Support\VaFormatter::fromNis($nocust);
                    $unit = trim((string) ($r['code02'] ?? ''));
                    if ($unit === '') {
                        $c01 = trim((string) ($r['code01'] ?? ''));
                        $uSek = trim((string) ($r['unit_sekolah'] ?? ''));
                        $unit = ($c01 !== '' && $uSek !== '') ? ($c01 . ' — ' . $uSek) : (($uSek !== '') ? $uSek : (($c01 !== '') ? $c01 : '-'));
                    }
                    $wali = trim((string) ($r['wali'] ?? $r['genus'] ?? ''));
                @endphp
                <tr>
                    <td class="ctr">{{ $index + 1 }}</td>
                    <td class="ctr">{{ $nocust !== '' ? $nocust : '-' }}</td>
                    <td class="ctr">{{ $noVa !== '' ? $noVa : '-' }}</td>
                    <td>{{ trim((string) ($r['nmcust'] ?? '')) !== '' ? $r['nmcust'] : '-' }}</td>
                    <td class="ctr">{{ trim((string) ($r['num2nd'] ?? '')) !== '' ? $r['num2nd'] : '-' }}</td>
                    <td>{{ $unit !== '' ? $unit : '-' }}</td>
                    <td class="ctr">{{ trim((string) ($r['desc02'] ?? '')) !== '' ? $r['desc02'] : '-' }}</td>
                    <td class="ctr">{{ trim((string) ($r['desc03'] ?? '')) !== '' ? $r['desc03'] : '-' }}</td>
                    <td class="ctr">{{ trim((string) ($r['desc04'] ?? '')) !== '' ? $r['desc04'] : '-' }}</td>
                    <td class="ctr">{{ $statusLabel($r['stcust'] ?? null) }}</td>
                    <td class="ctr">{{ $genderLabel($r['code04'] ?? '') }}</td>
                    <td>{{ trim((string) ($r['desc05'] ?? '')) !== '' ? $r['desc05'] : '-' }}</td>
                    <td>{{ $wali !== '' ? $wali : '-' }}</td>
                </tr>
            @empty
                <tr><td colspan="13" class="ctr" style="padding:16px;color:#6b7280;">Tidak ada data</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
