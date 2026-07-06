@extends('layouts.app')

@section('content')
    <div class="sc-page sc-page-wide">
        <div class="page-heading sc-page-heading">
            <h2>Rekap TOPUP</h2>
            <p>Smartcard / Rekap topup uang saku dari sccttran_cashless</p>
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
                                <div class="rt-cell rt-cell-spacer"></div>
                                <div class="rt-cell">
                                    <div class="rt-cell-label">NIS</div>
                                    <div class="rt-cell-input">
                                        <input type="text" name="nis" value="{{ $filters['nis'] ?? '' }}" placeholder="Nomor induk">
                                    </div>
                                </div>
                                <div class="rt-cell">
                                    <div class="rt-cell-label">NAMA</div>
                                    <div class="rt-cell-input">
                                        <input type="text" name="nama" value="{{ $filters['nama'] ?? '' }}" placeholder="Nama siswa">
                                    </div>
                                </div>
                                <div class="rt-cell">
                                    <div class="rt-cell-label">Dari Tanggal</div>
                                    <div class="rt-cell-input">
                                        <input type="date" name="dari_tanggal"
                                               value="{{ ($filters['dari_tanggal'] ?? '') !== '0000-00-00' ? ($filters['dari_tanggal'] ?? '') : '' }}">
                                    </div>
                                </div>
                                <div class="rt-cell">
                                    <div class="rt-cell-label">Sampai Tanggal</div>
                                    <div class="rt-cell-input">
                                        <input type="date" name="sampai_tanggal"
                                               value="{{ ($filters['sampai_tanggal'] ?? '') !== '0000-00-00' ? ($filters['sampai_tanggal'] ?? '') : '' }}">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="rt-actions">
                            <button type="submit" class="sc-btn sc-btn-primary rt-btn">
                                <i class="fa-solid fa-magnifying-glass rt-btn-icon" aria-hidden="true"></i> Cari
                            </button>
                            <button type="submit" form="rtFormCetak" class="sc-btn rt-btn rt-btn-outline" @disabled(!($isSearch ?? false))>
                                <i class="fa-solid fa-print rt-btn-icon" aria-hidden="true"></i> Cetak Rekap
                            </button>
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
                    @if ($isSearch ?? false)
                        <div class="rt-summary-bar">
                            <span>Total TOPUP: <strong>Rp {{ number_format((int) ($totals['topup'] ?? 0), 0, ',', '.') }}</strong></span>
                            <span>Total Biaya: <strong>Rp {{ number_format((int) ($totals['fee'] ?? 0), 0, ',', '.') }}</strong></span>
                            <span>Grand Total: <strong>Rp {{ number_format((int) ($totals['grand'] ?? 0), 0, ',', '.') }}</strong></span>
                        </div>
                    @endif

                    <div class="sc-table-wrap">
                        <table class="sc-table">
                            <thead>
                                <tr>
                                    <th style="width:56px;">No</th>
                                    <th style="width:120px;">NIS</th>
                                    <th>Nama</th>
                                    <th style="text-align:right;width:110px;">TOPUP</th>
                                    <th style="text-align:right;width:90px;">Biaya</th>
                                    <th style="text-align:right;width:110px;">Total</th>
                                    <th style="width:150px;">Tgl Transaksi</th>
                                    <th style="width:130px;">No Transaksi</th>
                                    <th style="width:100px;">User</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse (($rows ?? []) as $index => $row)
                                    <tr>
                                        <td>{{ ($rows->firstItem() ?? 0) + $index }}</td>
                                        <td>{{ $row->nis ?? '—' }}</td>
                                        <td>{{ $row->nama ?? '—' }}</td>
                                        <td style="text-align:right;">{{ number_format((int) ($row->topup ?? 0), 0, ',', '.') }}</td>
                                        <td style="text-align:right;">{{ number_format((int) ($row->fee ?? 0), 0, ',', '.') }}</td>
                                        <td style="text-align:right;">{{ number_format((int) ($row->total ?? 0), 0, ',', '.') }}</td>
                                        <td>
                                            @if (!empty($row->tgl_transaksi))
                                                {{ \Illuminate\Support\Carbon::parse($row->tgl_transaksi)->format('Y-m-d H:i:s') }}
                                            @else
                                                —
                                            @endif
                                        </td>
                                        <td>{{ $row->no_transaksi ?? '—' }}</td>
                                        <td>{{ $row->user ?? '—' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="sc-empty">
                                            @if ($isSearch ?? false)
                                                Tidak ada data rekap topup.
                                            @else
                                                Atur filter lalu klik <strong>Cari</strong> untuk menampilkan data (10 per halaman).
                                            @endif
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
            margin-bottom: 20px;
            background: #fff;
        }
        .rt-builder-scroll { overflow-x: auto; }
        .rt-builder-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(160px, 1fr));
            min-width: 720px;
        }
        .rt-cell { display: flex; flex-direction: column; border-right: 1px solid #e9d5ff; border-bottom: 1px solid #e9d5ff; }
        .rt-cell-spacer { background: #faf5ff; }
        .rt-cell-label {
            font-size: 12px;
            font-weight: 800;
            color: #5b21b6;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            padding: 10px 12px;
            background: linear-gradient(180deg, #ede9fe 0%, #e9d5ff 100%);
            border-bottom: 1px solid #ddd6fe;
        }
        .rt-cell-input input,
        .rt-cell-input select {
            width: 100%;
            height: 44px;
            border: 0;
            padding: 0 12px;
            font-size: 14px;
            background: #fff;
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
        .rt-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            padding: 14px 16px;
            background: #faf5ff;
            border-top: 1px solid #e9d5ff;
        }
        .rt-btn { min-width: 130px; }
        .rt-btn-icon { margin-right: 6px; }
        .rt-btn-outline {
            background: #fff;
            border: 1px solid #c4b5fd;
            color: #5b21b6;
        }
        .rt-btn-outline:disabled { opacity: 0.5; cursor: not-allowed; }
        .rt-hidden-form { display: none; }
        .rt-summary-bar {
            display: flex;
            flex-wrap: wrap;
            gap: 16px 24px;
            font-size: 13px;
            color: #4b5563;
            margin-bottom: 12px;
            padding: 10px 14px;
            background: #faf5ff;
            border: 1px solid #e9d5ff;
            border-radius: 10px;
        }
        .rt-summary-bar strong { color: #7c3aed; }
        .rt-table-footer {
            padding: 12px 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 8px;
            font-size: 12px;
            color: #6b7280;
            border-top: 1px solid #ede9fe;
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
            .rt-builder-grid { grid-template-columns: repeat(2, minmax(140px, 1fr)); min-width: 0; }
            .rt-cell-spacer { display: none; }
        }
    </style>
@endsection
