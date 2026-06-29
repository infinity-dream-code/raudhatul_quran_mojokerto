<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 6.5pt; color: #111; margin: 0; padding: 8px; }
        .head-top { border-bottom: 3px solid #111; padding-bottom: 6px; margin-bottom: 8px; }
        .head-row { width: 100%; border-collapse: collapse; }
        .head-row td { border: 0; vertical-align: middle; }
        .logo-cell { width: 70px; }
        .logo { width: 56px; height: 56px; object-fit: contain; display: block; }
        .text-cell { text-align: center; }
        .yayasan { font-size: 16px; font-weight: 700; margin: 0; }
        .sub { font-size: 8px; margin: 2px 0 0; }
        .title { font-size: 11pt; font-weight: 700; text-align: center; margin: 0 0 6px; }
        .doc-title { font-size: 11pt; font-weight: 700; text-align: center; margin: 0 0 6px; }
        .meta { margin: 0 0 8px; font-size: 7pt; }
        .meta table { width: 100%; border-collapse: collapse; }
        .meta td { border: 0; padding: 1px 6px 1px 0; vertical-align: top; }
        .meta .k { font-weight: 700; white-space: nowrap; width: 22%; }
        table.tbl { width: 100%; border-collapse: collapse; table-layout: fixed; margin-top: 3px; }
        .tbl th, .tbl td { border: 1px solid #000; padding: 2px 2px; vertical-align: middle; word-wrap: break-word; }
        .tbl th { background: #ededed; font-size: 6pt; text-align: center; font-weight: 700; }
        .num { text-align: right; white-space: nowrap; }
        .tot-row td { font-weight: 700; background: #f3f4f6; }
    </style>
</head>
<body>
    @php
        $matrix = is_array($matrix ?? null) ? $matrix : [];
        $rows = is_array($matrix['rows'] ?? null) ? $matrix['rows'] : [];
        $kelasOrder = is_array($matrix['kelasOrder'] ?? null) ? $matrix['kelasOrder'] : [];
        $kelompokOrder = is_array($matrix['kelompokOrder'] ?? null) ? $matrix['kelompokOrder'] : [];
        $meta = is_array($meta ?? null) ? $meta : [];
        if (!function_exists('dt_rekap_matrix_rp')) {
            function dt_rekap_matrix_rp(int $n): string
            {
                return 'Rp. ' . number_format($n, 0, ',', '.');
            }
        }
        $colTotals = [];
        $grandTotal = 0;
        $prevTahun = null;
    @endphp

    @include('partials.pdf-letterhead', ['docTitle' => 'REKAP TAGIHAN'])

    <div class="meta">
        <table>
            <tr><td class="k">Sekolah</td><td>: {{ $meta['sekolah'] ?? 'Semua' }}</td></tr>
            <tr><td class="k">Tahun Pelajaran</td><td>: {{ $meta['tahun_pelajaran'] ?? 'Semua' }}</td></tr>
            <tr><td class="k">Periode Mulai</td><td>: {{ $meta['periode_mulai'] ?? '-' }}</td></tr>
            <tr><td class="k">Periode Akhir</td><td>: {{ $meta['periode_akhir'] ?? '-' }}</td></tr>
            <tr><td class="k">Dari Tanggal</td><td>: {{ $meta['dari_tanggal'] ?? '-' }}</td></tr>
            <tr><td class="k">Sampai Tanggal</td><td>: {{ $meta['sampai_tanggal'] ?? '-' }}</td></tr>
            <tr><td class="k">Kelas</td><td>: {{ $meta['kelas'] ?? 'Semua' }}</td></tr>
            <tr><td class="k">Kode Post</td><td>: {{ $meta['kode_post'] ?? 'Semua' }}</td></tr>
            <tr><td class="k">Nama Post</td><td>: {{ $meta['nama_post'] ?? 'Semua' }}</td></tr>
            <tr><td class="k">Nama Tagihan</td><td>: {{ $meta['nama_tagihan'] ?? 'Semua' }}</td></tr>
        </table>
    </div>

    @if ($rows === [] || $kelasOrder === [])
        <p style="font-size:8pt;">Tidak ada data untuk ditampilkan.</p>
    @else
        <table class="tbl">
            <thead>
                <tr>
                    <th rowspan="2" style="width:9%;">Thn Akademik</th>
                    <th rowspan="2" style="width:6%;">Kode</th>
                    <th rowspan="2" style="width:16%;">Nama</th>
                    @foreach ($kelasOrder as $kelas)
                        <th colspan="{{ count($kelompokOrder) + 1 }}">{{ $kelas }}</th>
                    @endforeach
                    <th rowspan="2" style="width:7%;">Total</th>
                </tr>
                <tr>
                    @foreach ($kelasOrder as $kelas)
                        @foreach ($kelompokOrder as $k)
                            <th>{{ $k }}</th>
                        @endforeach
                        <th>Sum</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach ($rows as $r)
                    @php
                        $r = is_array($r) ? $r : [];
                        $tahun = (string) ($r['tahun'] ?? '-');
                        $showTahun = $tahun !== $prevTahun ? $tahun : '';
                        $prevTahun = $tahun;
                    @endphp
                    <tr>
                        <td>{{ $showTahun !== '' ? $showTahun : '' }}</td>
                        <td style="text-align:left;">{{ $r['kode'] ?? '-' }}</td>
                        <td>{{ $r['nama'] ?? '-' }}</td>
                        @foreach ($kelasOrder as $ki => $kelas)
                            @php $sub = 0; @endphp
                            @foreach ($kelompokOrder as $kj => $k)
                                @php
                                    $v = (int) (($r['byClass'][$kelas][$k] ?? 0));
                                    $sub += $v;
                                    $key = ($ki * 1000) + $kj;
                                    $colTotals[$key] = ($colTotals[$key] ?? 0) + $v;
                                @endphp
                                <td class="num">{{ dt_rekap_matrix_rp($v) }}</td>
                            @endforeach
                            @php
                                $sumKey = 'sum_' . $ki;
                                $colTotals[$sumKey] = ($colTotals[$sumKey] ?? 0) + $sub;
                            @endphp
                            <td class="num">{{ dt_rekap_matrix_rp($sub) }}</td>
                        @endforeach
                        @php
                            $rowTotal = (int) ($r['total'] ?? 0);
                            $grandTotal += $rowTotal;
                        @endphp
                        <td class="num">{{ dt_rekap_matrix_rp($rowTotal) }}</td>
                    </tr>
                @endforeach
                <tr class="tot-row">
                    <td colspan="3" class="num" style="text-align:right;padding-right:4px;">Total</td>
                    @foreach ($kelasOrder as $ki => $kelas)
                        @foreach ($kelompokOrder as $kj => $k)
                            @php $key = ($ki * 1000) + $kj; @endphp
                            <td class="num">{{ dt_rekap_matrix_rp((int) ($colTotals[$key] ?? 0)) }}</td>
                        @endforeach
                        <td class="num">{{ dt_rekap_matrix_rp((int) ($colTotals['sum_' . $ki] ?? 0)) }}</td>
                    @endforeach
                    <td class="num">{{ dt_rekap_matrix_rp($grandTotal) }}</td>
                </tr>
            </tbody>
        </table>
    @endif

    <p style="font-size:6pt;color:#6b7280;margin-top:6px;">Dicetak {{ now('Asia/Jakarta')->format('d/m/Y H:i') }} WIB</p>
</body>
</html>
