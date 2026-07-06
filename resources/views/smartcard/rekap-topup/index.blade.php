@extends('layouts.app')

@section('content')
    <div class="sc-page sc-page-wide">
        <div class="page-heading sc-page-heading">
            <h2>Rekap TOPUP</h2>
            <p>Smartcard / Rekap topup uang saku</p>
        </div>

        <div class="card sc-card">
            <div class="sc-card-body">
                @if (session('smartcard_success'))
                    <div class="sc-alert sc-alert-success">{{ session('smartcard_success') }}</div>
                @endif
                @if (session('smartcard_error'))
                    <div class="sc-alert sc-alert-error">{{ session('smartcard_error') }}</div>
                @endif

                <div class="rt-builder-title">REKAP TOPUP</div>

                <form method="GET" action="{{ route('smartcard.rekap_topup') }}" id="rtFormSearch">
                    <input type="hidden" name="search" value="1">

                    <div class="rt-builder-panel">
                        <div class="rt-builder-scroll">
                            <div class="rt-builder-grid">
                                <div class="rt-cell">
                                    <div class="rt-cell-label">Tahun Angkatan</div>
                                    <div class="rt-cell-input">
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
                                <div class="rt-cell">
                                    <div class="rt-cell-label">Kelas</div>
                                    <div class="rt-cell-input">
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
                                <div class="rt-cell">
                                    <div class="rt-cell-label">NIS</div>
                                    <div class="rt-cell-input">
                                        <input type="text" name="nis" value="{{ $filters['nis'] ?? '' }}" placeholder="Nomor induk" autocomplete="off">
                                    </div>
                                </div>
                                <div class="rt-cell rt-cell-end">
                                    <div class="rt-cell-label">NAMA</div>
                                    <div class="rt-cell-input">
                                        <input type="text" name="nama" value="{{ $filters['nama'] ?? '' }}" placeholder="Nama siswa" autocomplete="off">
                                    </div>
                                </div>

                                <div class="rt-cell rt-cell-row2">
                                    <div class="rt-cell-label">Dari Tanggal</div>
                                    <div class="rt-cell-input">
                                        <input type="date" name="dari_tanggal"
                                               value="{{ ($filters['dari_tanggal'] ?? '') !== '0000-00-00' ? ($filters['dari_tanggal'] ?? '') : '' }}">
                                    </div>
                                </div>
                                <div class="rt-cell rt-cell-row2">
                                    <div class="rt-cell-label">Sampai Tanggal</div>
                                    <div class="rt-cell-input">
                                        <input type="date" name="sampai_tanggal"
                                               value="{{ ($filters['sampai_tanggal'] ?? '') !== '0000-00-00' ? ($filters['sampai_tanggal'] ?? '') : '' }}">
                                    </div>
                                </div>
                                <div class="rt-cell rt-cell-row2 rt-cell-action">
                                    <div class="rt-cell-label rt-cell-label-muted">Aksi</div>
                                    <div class="rt-cell-input rt-cell-input-action">
                                        <button type="submit" class="rt-action-btn rt-action-search">
                                            <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i> Cari
                                        </button>
                                    </div>
                                </div>
                                <div class="rt-cell rt-cell-row2 rt-cell-end rt-cell-action">
                                    <div class="rt-cell-label rt-cell-label-muted">Cetak</div>
                                    <div class="rt-cell-input rt-cell-input-action">
                                        <button type="submit" form="rtFormCetak" class="rt-action-btn rt-action-print" @disabled(!($isSearch ?? false))>
                                            <i class="fa-solid fa-print" aria-hidden="true"></i> Cetak Rekap
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>

                <form method="POST" action="{{ route('smartcard.rekap_topup.cetak') }}" id="rtFormCetak" target="_blank" class="rt-hidden-form">
                    @csrf
                    <input type="hidden" name="thn_angkatan" value="{{ $filters['thn_angkatan'] ?? '' }}">
                    <input type="hidden" name="kelas_id" value="{{ $filters['kelas_id'] ?? '' }}">
                    <input type="hidden" name="nis" value="{{ $filters['nis'] ?? '' }}">
                    <input type="hidden" name="nama" value="{{ $filters['nama'] ?? '' }}">
                    <input type="hidden" name="dari_tanggal" value="{{ $filters['dari_tanggal'] ?? '' }}">
                    <input type="hidden" name="sampai_tanggal" value="{{ $filters['sampai_tanggal'] ?? '' }}">
                </form>

                <div class="sc-table-section">
                    <div class="rt-table-head">
                        <div class="sc-table-title">Data Rekap TOPUP</div>
                        @if ($isSearch ?? false)
                            <span class="rt-count-badge">{{ $rows->total() ?? 0 }} transaksi</span>
                        @endif
                    </div>

                    @if ($isSearch ?? false)
                        <div class="rt-summary-bar">
                            <div class="rt-summary-item">
                                <span class="rt-summary-label">Total TOPUP (halaman)</span>
                                <strong>Rp {{ number_format((int) ($totals['topup'] ?? 0), 0, ',', '.') }}</strong>
                            </div>
                            <div class="rt-summary-divider"></div>
                            <div class="rt-summary-item">
                                <span class="rt-summary-label">Total Biaya (halaman)</span>
                                <strong>Rp {{ number_format((int) ($totals['fee'] ?? 0), 0, ',', '.') }}</strong>
                            </div>
                            <div class="rt-summary-divider"></div>
                            <div class="rt-summary-item rt-summary-grand">
                                <span class="rt-summary-label">Grand Total (halaman)</span>
                                <strong>Rp {{ number_format((int) ($totals['grand'] ?? 0), 0, ',', '.') }}</strong>
                            </div>
                        </div>
                    @endif

                    <div class="sc-table-wrap rt-table-wrap">
                        <table class="sc-table">
                            <thead>
                                <tr>
                                    <th class="rt-col-no">No</th>
                                    <th class="rt-col-nis">NIS</th>
                                    <th>Nama Siswa</th>
                                    <th class="rt-col-num">TOPUP</th>
                                    <th class="rt-col-num">Biaya</th>
                                    <th class="rt-col-num">Total</th>
                                    <th class="rt-col-date">Tgl Transaksi</th>
                                    <th class="rt-col-trans">No Transaksi</th>
                                    <th class="rt-col-user">User</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse (($rows ?? []) as $index => $row)
                                    <tr>
                                        <td class="rt-col-no">{{ ($rows->firstItem() ?? 0) + $index }}</td>
                                        <td class="rt-col-nis"><span class="rt-nis">{{ $row->nis ?? '—' }}</span></td>
                                        <td>{{ $row->nama ?? '—' }}</td>
                                        <td class="rt-col-num"><span class="rt-money">{{ number_format((int) ($row->topup ?? 0), 0, ',', '.') }}</span></td>
                                        <td class="rt-col-num"><span class="rt-money rt-money-fee">{{ number_format((int) ($row->fee ?? 0), 0, ',', '.') }}</span></td>
                                        <td class="rt-col-num"><span class="rt-money rt-money-total">{{ number_format((int) ($row->total ?? 0), 0, ',', '.') }}</span></td>
                                        <td class="rt-col-date">
                                            @if (!empty($row->tgl_transaksi))
                                                {{ \Illuminate\Support\Carbon::parse($row->tgl_transaksi)->format('Y-m-d H:i:s') }}
                                            @else
                                                —
                                            @endif
                                        </td>
                                        <td class="rt-col-trans"><span class="rt-transno">{{ $row->no_transaksi ?? '—' }}</span></td>
                                        <td class="rt-col-user">{{ $row->user ?? '—' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="sc-empty">
                                            <div class="rt-empty-state">
                                                <i class="fa-solid fa-table-list rt-empty-icon" aria-hidden="true"></i>
                                                <div>
                                                    @if ($isSearch ?? false)
                                                        Tidak ada data rekap topup untuk filter ini.
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
                        <div class="rt-table-footer">
                            <div>
                                Menampilkan {{ $rows->firstItem() ?? 0 }}–{{ $rows->lastItem() ?? 0 }}
                                dari {{ $rows->total() }} transaksi
                            </div>
                            <div class="rt-table-pages">
                                @if ($rows->onFirstPage())
                                    <span class="rt-page disabled">Sebelumnya</span>
                                @else
                                    <a class="rt-page" href="{{ $rows->previousPageUrl() }}">Sebelumnya</a>
                                @endif
                                <span class="rt-page active">{{ $rows->currentPage() }}</span>
                                @if ($rows->hasMorePages())
                                    <a class="rt-page" href="{{ $rows->nextPageUrl() }}">Selanjutnya</a>
                                @else
                                    <span class="rt-page disabled">Selanjutnya</span>
                                @endif
                            </div>
                        </div>
                    @elseif (($isSearch ?? false) && ($rows->total() ?? 0) > 0)
                        <div class="rt-table-footer">
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

        .rt-builder-title {
            font-family: 'Sora', sans-serif;
            font-size: 18px;
            font-weight: 800;
            color: #5b21b6;
            text-align: center;
            margin-bottom: 20px;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }

        .rt-builder-panel {
            border: 1px solid #c4b5fd;
            border-radius: 14px;
            overflow: hidden;
            margin-bottom: 24px;
            background: #fff;
            box-shadow: 0 4px 20px rgba(109, 40, 217, 0.08);
        }

        .rt-builder-scroll {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        .rt-builder-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            min-width: 720px;
        }

        .rt-cell {
            display: flex;
            flex-direction: column;
            border-right: 1px solid #e9d5ff;
            border-bottom: 1px solid #e9d5ff;
        }
        .rt-cell-end { border-right: 0; }
        .rt-cell-row2 { border-bottom: 0; }

        .rt-cell-label {
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
        .rt-cell-label-muted {
            color: #7c3aed;
            background: linear-gradient(180deg, #f5f3ff 0%, #ede9fe 100%);
        }

        .rt-cell-input {
            flex: 1;
            min-height: 48px;
            display: flex;
            align-items: center;
            background: #fff;
        }

        .rt-cell-input input,
        .rt-cell-input select {
            width: 100%;
            height: 48px;
            border: 0;
            padding: 0 12px;
            font-size: 14px;
            background: transparent;
            color: #1f2937;
            outline: none;
        }

        .rt-cell-input select {
            cursor: pointer;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%235b21b6' d='M6 8L1 3h10z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 12px center;
            padding-right: 32px;
        }

        .rt-cell-input-action {
            padding: 8px 10px;
            background: #faf5ff;
        }

        .rt-action-btn {
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
            transition: background 0.15s, opacity 0.15s;
        }
        .rt-action-search {
            background: #4f6ef7;
            color: #fff;
        }
        .rt-action-search:hover { background: #4338ca; }
        .rt-action-print {
            background: #fff;
            color: #5b21b6;
            border: 1px solid #c4b5fd;
        }
        .rt-action-print:hover:not(:disabled) { background: #f5f3ff; }
        .rt-action-print:disabled {
            opacity: 0.45;
            cursor: not-allowed;
        }

        .rt-hidden-form { display: none; }

        .rt-table-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 12px;
            flex-wrap: wrap;
        }
        .rt-count-badge {
            font-size: 12px;
            font-weight: 700;
            color: #5b21b6;
            background: #ede9fe;
            border: 1px solid #c4b5fd;
            border-radius: 999px;
            padding: 4px 12px;
        }

        .rt-summary-bar {
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
        .rt-summary-item {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }
        .rt-summary-label {
            font-size: 11px;
            font-weight: 700;
            color: #7c3aed;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .rt-summary-item strong {
            font-size: 15px;
            color: #1f2937;
        }
        .rt-summary-grand strong { color: #047857; }
        .rt-summary-divider {
            width: 1px;
            height: 36px;
            background: #ddd6fe;
        }

        .rt-table-wrap {
            border: 1px solid #e9d5ff;
            border-radius: 12px;
            overflow: hidden;
        }

        .rt-col-no { width: 48px; text-align: center; }
        .rt-col-nis { width: 110px; }
        .rt-col-num { text-align: right; width: 96px; }
        .rt-col-date { width: 148px; white-space: nowrap; font-size: 13px; }
        .rt-col-trans { width: 120px; }
        .rt-col-user { width: 90px; }

        .sc-table thead th.rt-col-num { text-align: right; }

        .rt-nis {
            font-family: ui-monospace, monospace;
            font-size: 13px;
            color: #5b21b6;
            font-weight: 600;
        }
        .rt-transno {
            font-family: ui-monospace, monospace;
            font-size: 12px;
            color: #4b5563;
        }
        .rt-money { font-weight: 600; }
        .rt-money-fee { color: #b45309; }
        .rt-money-total { color: #047857; }

        .rt-empty-state {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
            padding: 28px 16px;
            color: #6b7280;
            font-size: 14px;
        }
        .rt-empty-icon {
            font-size: 28px;
            color: #c4b5fd;
        }

        .rt-table-footer {
            padding: 12px 4px 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 8px;
            font-size: 12px;
            color: #6b7280;
        }
        .rt-table-pages { display: flex; gap: 6px; align-items: center; }
        .rt-page {
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
        .rt-page.active { background: #7c3aed; color: #fff; border-color: #7c3aed; }
        .rt-page.disabled { pointer-events: none; opacity: 0.45; }

        @media (max-width: 768px) {
            .rt-builder-grid { min-width: 640px; }
            .rt-summary-divider { display: none; }
        }
    </style>
@endsection
