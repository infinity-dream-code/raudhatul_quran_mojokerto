@extends('layouts.app')

@section('content')
    <div class="sc-page sc-page-wide">
        <div class="page-heading sc-page-heading">
            <h2>Rekap Keluar Uang Saku</h2>
            <p>Smartcard / Rekap pengeluaran uang saku (FIDBANK cash)</p>
        </div>

        <div class="card sc-card">
            <div class="sc-card-body">
                @if (session('smartcard_success'))
                    <div class="sc-alert sc-alert-success">{{ session('smartcard_success') }}</div>
                @endif
                @if (session('smartcard_error'))
                    <div class="sc-alert sc-alert-error">{{ session('smartcard_error') }}</div>
                @endif

                <div class="rku-builder-title">REKAP KELUAR UANG SAKU</div>

                <form method="GET" action="{{ route('smartcard.rekap_keluar_uang_saku') }}" id="rkuFormSearch">
                    <input type="hidden" name="search" value="1">

                    <div class="rku-builder-panel">
                        <div class="rku-builder-scroll">
                            <div class="rku-builder-grid">
                                <div class="rku-cell">
                                    <div class="rku-cell-label">Tahun Angkatan</div>
                                    <div class="rku-cell-input">
                                        <select name="thn_angkatan">
                                            <option value="">Semua</option>
                                            @foreach ($thnAka as $row)
                                                @php $val = trim((string) ($row->thn_aka ?? '')); @endphp
                                                @if ($val !== '')
                                                    <option value="{{ $val }}" @selected(($filters['thn_angkatan'] ?? '') === $val)>{{ $val }}</option>
                                                @endif
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="rku-cell">
                                    <div class="rku-cell-label">Kelas</div>
                                    <div class="rku-cell-input">
                                        <select name="kelas_id">
                                            <option value="">Semua</option>
                                            @foreach ($kelasOptions as $k)
                                                @php
                                                    $id = (string) ($k->id ?? '');
                                                    $parts = array_values(array_filter([
                                                        trim((string) ($k->unit ?? '')),
                                                        trim((string) ($k->jenjang ?? '')),
                                                        trim((string) ($k->kelas ?? '')),
                                                    ], static fn ($v) => $v !== ''));
                                                    $lbl = implode(' - ', $parts);
                                                @endphp
                                                @if ($id !== '' && $lbl !== '')
                                                    <option value="{{ $id }}" @selected(($filters['kelas_id'] ?? '') === $id)>{{ $lbl }}</option>
                                                @endif
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="rku-cell">
                                    <div class="rku-cell-label">NIS</div>
                                    <div class="rku-cell-input">
                                        <input type="text" name="nis" value="{{ $filters['nis'] ?? '' }}" placeholder="Nomor induk" autocomplete="off">
                                    </div>
                                </div>
                                <div class="rku-cell rku-cell-end">
                                    <div class="rku-cell-label">NAMA</div>
                                    <div class="rku-cell-input">
                                        <input type="text" name="nama" value="{{ $filters['nama'] ?? '' }}" placeholder="Nama siswa" autocomplete="off">
                                    </div>
                                </div>

                                <div class="rku-cell rku-cell-row2">
                                    <div class="rku-cell-label">Dari Tanggal</div>
                                    <div class="rku-cell-input">
                                        <input type="date" name="dari_tanggal"
                                               value="{{ ($filters['dari_tanggal'] ?? '') !== '0000-00-00' ? ($filters['dari_tanggal'] ?? '') : '' }}">
                                    </div>
                                </div>
                                <div class="rku-cell rku-cell-row2">
                                    <div class="rku-cell-label">Sampai Tanggal</div>
                                    <div class="rku-cell-input">
                                        <input type="date" name="sampai_tanggal"
                                               value="{{ ($filters['sampai_tanggal'] ?? '') !== '0000-00-00' ? ($filters['sampai_tanggal'] ?? '') : '' }}">
                                    </div>
                                </div>
                                <div class="rku-cell rku-cell-row2 rku-cell-action">
                                    <div class="rku-cell-label rku-cell-label-muted">Aksi</div>
                                    <div class="rku-cell-input rku-cell-input-action">
                                        <button type="submit" class="rku-action-btn rku-action-search">
                                            <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i> Cari
                                        </button>
                                    </div>
                                </div>
                                <div class="rku-cell rku-cell-row2 rku-cell-end rku-cell-action">
                                    <div class="rku-cell-label rku-cell-label-muted">Cetak</div>
                                    <div class="rku-cell-input rku-cell-input-action">
                                        <button type="submit" form="rkuFormCetak" class="rku-action-btn rku-action-print" @disabled(!($isSearch ?? false))>
                                            <i class="fa-solid fa-print" aria-hidden="true"></i> Cetak Rekap
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>

                <form method="POST" action="{{ route('smartcard.rekap_keluar_uang_saku.cetak') }}" id="rkuFormCetak" target="_blank" class="rku-hidden-form">
                    @csrf
                    <input type="hidden" name="thn_angkatan" value="{{ $filters['thn_angkatan'] ?? '' }}">
                    <input type="hidden" name="kelas_id" value="{{ $filters['kelas_id'] ?? '' }}">
                    <input type="hidden" name="nis" value="{{ $filters['nis'] ?? '' }}">
                    <input type="hidden" name="nama" value="{{ $filters['nama'] ?? '' }}">
                    <input type="hidden" name="dari_tanggal" value="{{ $filters['dari_tanggal'] ?? '' }}">
                    <input type="hidden" name="sampai_tanggal" value="{{ $filters['sampai_tanggal'] ?? '' }}">
                </form>

                <div class="sc-table-section">
                    <div class="rku-table-head">
                        <div class="sc-table-title">Data Rekap Keluar Uang Saku</div>
                        @if ($isSearch ?? false)
                            <span class="rku-count-badge">{{ $rows->total() ?? 0 }} transaksi</span>
                        @endif
                    </div>

                    @if ($isSearch ?? false)
                        <div class="rku-summary-bar">
                            <div class="rku-summary-item rku-summary-grand">
                                <span class="rku-summary-label">Total DEBET (halaman)</span>
                                <strong>Rp {{ number_format((int) ($totals['debet'] ?? 0), 0, ',', '.') }}</strong>
                            </div>
                        </div>
                    @endif

                    <div class="sc-table-wrap rku-table-wrap">
                        <table class="sc-table">
                            <thead>
                                <tr>
                                    <th class="rku-col-no">No</th>
                                    <th>Kelas</th>
                                    <th class="rku-col-gender">L/P</th>
                                    <th>Lokasi</th>
                                    <th class="rku-col-nis">NIS</th>
                                    <th>Nama Siswa</th>
                                    <th class="rku-col-date">Tgl Transaksi</th>
                                    <th class="rku-col-num">DEBET</th>
                                    <th class="rku-col-num">Saldo</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse (($rows ?? []) as $index => $row)
                                    <tr>
                                        <td class="rku-col-no">{{ ($rows->firstItem() ?? 0) + $index }}</td>
                                        <td>{{ $row->kelas ?? '—' }}</td>
                                        <td class="rku-col-gender">{{ $row->gender ?? '—' }}</td>
                                        <td>{{ $row->lokasi ?? '—' }}</td>
                                        <td class="rku-col-nis"><span class="rku-nis">{{ $row->nis ?? '—' }}</span></td>
                                        <td>{{ $row->nama ?? '—' }}</td>
                                        <td class="rku-col-date">
                                            @if (!empty($row->tgl_transaksi))
                                                {{ \Illuminate\Support\Carbon::parse($row->tgl_transaksi)->format('Y-m-d H:i:s') }}
                                            @else
                                                —
                                            @endif
                                        </td>
                                        <td class="rku-col-num"><span class="rku-money rku-money-debet">{{ number_format((int) ($row->debet ?? 0), 0, ',', '.') }}</span></td>
                                        <td class="rku-col-num"><span class="rku-money rku-money-saldo">{{ number_format((int) ($row->saldo ?? 0), 0, ',', '.') }}</span></td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="sc-empty">
                                            <div class="rku-empty-state">
                                                <i class="fa-solid fa-table-list rku-empty-icon" aria-hidden="true"></i>
                                                <div>
                                                    @if ($isSearch ?? false)
                                                        Tidak ada data rekap keluar uang saku untuk filter ini.
                                                    @else
                                                        Atur filter lalu klik <strong>Cari</strong> (10 data per halaman).
                                                    @endif
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if (isset($rows) && method_exists($rows, 'hasPages') && $rows->hasPages())
                        <div class="rku-table-footer">
                            <div>
                                Menampilkan {{ $rows->firstItem() ?? 0 }}–{{ $rows->lastItem() ?? 0 }}
                                dari {{ $rows->total() }} transaksi
                            </div>
                            <div class="rku-table-pages">
                                @if ($rows->onFirstPage())
                                    <span class="rku-page disabled">Sebelumnya</span>
                                @else
                                    <a class="rku-page" href="{{ $rows->previousPageUrl() }}">Sebelumnya</a>
                                @endif
                                <span class="rku-page active">{{ $rows->currentPage() }}</span>
                                @if ($rows->hasMorePages())
                                    <a class="rku-page" href="{{ $rows->nextPageUrl() }}">Selanjutnya</a>
                                @else
                                    <span class="rku-page disabled">Selanjutnya</span>
                                @endif
                            </div>
                        </div>
                    @elseif (($isSearch ?? false) && ($rows->total() ?? 0) > 0)
                        <div class="rku-table-footer">
                            <div>
                                Menampilkan {{ $rows->firstItem() ?? 0 }}–{{ $rows->lastItem() ?? 0 }}
                                dari {{ $rows->total() }} transaksi
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @include('smartcard.partials.styles')

    <style>
        .sc-page-wide { max-width: 1320px; }
        .rku-builder-title {
            font-family: 'Sora', sans-serif;
            font-size: 18px;
            font-weight: 800;
            color: #5b21b6;
            text-align: center;
            margin-bottom: 20px;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }
        .rku-builder-panel {
            border: 1px solid #c4b5fd;
            border-radius: 14px;
            overflow: hidden;
            margin-bottom: 24px;
            background: #fff;
            box-shadow: 0 4px 20px rgba(109, 40, 217, 0.08);
        }
        .rku-builder-scroll { overflow-x: auto; -webkit-overflow-scrolling: touch; }
        .rku-builder-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            min-width: 720px;
        }
        .rku-cell {
            display: flex;
            flex-direction: column;
            border-right: 1px solid #e9d5ff;
            border-bottom: 1px solid #e9d5ff;
        }
        .rku-cell-end { border-right: 0; }
        .rku-cell-row2 { border-bottom: 0; }
        .rku-cell-label {
            font-size: 12px;
            font-weight: 800;
            color: #5b21b6;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            padding: 10px 12px;
            background: linear-gradient(180deg, #ede9fe 0%, #e9d5ff 100%);
            border-bottom: 1px solid #ddd6fe;
            white-space: nowrap;
            min-height: 40px;
            display: flex;
            align-items: center;
        }
        .rku-cell-label-muted {
            color: #7c3aed;
            background: linear-gradient(180deg, #f5f3ff 0%, #ede9fe 100%);
        }
        .rku-cell-input {
            flex: 1;
            min-height: 48px;
            display: flex;
            align-items: center;
            background: #fff;
        }
        .rku-cell-input input,
        .rku-cell-input select {
            width: 100%;
            height: 48px;
            border: 0;
            padding: 0 12px;
            font-size: 14px;
            background: transparent;
            color: #1f2937;
            outline: none;
        }
        .rku-cell-input select {
            cursor: pointer;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%235b21b6' d='M6 8L1 3h10z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 12px center;
            padding-right: 32px;
        }
        .rku-cell-input-action { padding: 8px 10px; background: #faf5ff; }
        .rku-action-btn {
            width: 100%;
            height: 40px;
            border: 0;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }
        .rku-action-search { background: #4f6ef7; color: #fff; }
        .rku-action-search:hover { background: #4338ca; }
        .rku-action-print {
            background: #fff;
            color: #5b21b6;
            border: 1px solid #c4b5fd;
        }
        .rku-action-print:hover:not(:disabled) { background: #f5f3ff; }
        .rku-action-print:disabled { opacity: 0.45; cursor: not-allowed; }
        .rku-hidden-form { display: none; }
        .rku-table-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 12px;
            flex-wrap: wrap;
        }
        .rku-count-badge {
            font-size: 12px;
            font-weight: 700;
            color: #5b21b6;
            background: #ede9fe;
            border: 1px solid #c4b5fd;
            border-radius: 999px;
            padding: 4px 12px;
        }
        .rku-summary-bar {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 12px 20px;
            padding: 12px 16px;
            margin-bottom: 14px;
            background: #faf5ff;
            border: 1px solid #e9d5ff;
            border-radius: 10px;
        }
        .rku-summary-item { display: flex; flex-direction: column; gap: 2px; }
        .rku-summary-label {
            font-size: 11px;
            font-weight: 700;
            color: #7c3aed;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .rku-summary-item strong { font-size: 15px; color: #1f2937; }
        .rku-summary-grand strong { color: #b91c1c; }
        .rku-table-wrap {
            border: 1px solid #e9d5ff;
            border-radius: 12px;
            overflow: auto;
            -webkit-overflow-scrolling: touch;
        }
        .rku-col-no { width: 48px; text-align: center; }
        .rku-col-nis { width: 110px; }
        .rku-col-gender { width: 48px; text-align: center; }
        .rku-col-num { text-align: right; width: 96px; }
        .rku-col-date { width: 148px; white-space: nowrap; font-size: 13px; }
        .sc-table thead th.rku-col-num { text-align: right; }
        .rku-nis {
            font-family: ui-monospace, monospace;
            font-size: 13px;
            color: #5b21b6;
            font-weight: 600;
        }
        .rku-money { font-weight: 600; }
        .rku-money-debet { color: #b91c1c; }
        .rku-money-saldo { color: #047857; }
        .rku-empty-state {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
            padding: 28px 16px;
            color: #6b7280;
            font-size: 14px;
        }
        .rku-empty-icon { font-size: 28px; color: #c4b5fd; }
        .rku-table-footer {
            padding: 12px 4px 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 8px;
            font-size: 12px;
            color: #6b7280;
        }
        .rku-table-pages { display: flex; gap: 6px; align-items: center; }
        .rku-page {
            min-width: 30px;
            height: 30px;
            border: 1px solid #c4b5fd;
            border-radius: 999px;
            padding: 0 10px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            color: #5b21b6;
            font-weight: 700;
            background: #fff;
        }
        .rku-page.active { background: #7c3aed; color: #fff; border-color: #7c3aed; }
        .rku-page.disabled { pointer-events: none; opacity: 0.45; }
        @media (max-width: 768px) {
            .rku-builder-grid { min-width: 640px; }
        }
    </style>
@endsection
