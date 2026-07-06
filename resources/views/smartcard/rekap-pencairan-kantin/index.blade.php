@extends('layouts.app')

@section('content')
    <div class="sc-page">
        <div class="page-heading sc-page-heading">
            <h2>Rekap Pencairan Kantin</h2>
            <p>Smartcard / Pencairan belanja ke kantin merchant</p>
        </div>

        <div class="card sc-card">
            <div class="sc-card-body">
                @if (session('smartcard_success'))
                    <div class="sc-alert sc-alert-success">{{ session('smartcard_success') }}</div>
                @endif
                @if ($errors->any())
                    <div class="sc-alert sc-alert-error">{{ $errors->first() }}</div>
                @endif

                <div class="rp-top-grid">
                    <form method="GET" action="{{ route('smartcard.rekap_pencairan_kantin') }}" class="rp-panel">
                        <div class="rp-panel-title">Cari Transaksi</div>
                        <div class="sc-form-grid rp-form-grid">
                            <div class="sc-field">
                                <label for="kdMercanCari">Merchan</label>
                                <div class="sc-control-wrap sc-control-select">
                                    <select id="kdMercanCari" name="kd_mercan" required>
                                        <option value="">Pilih Merchan</option>
                                        @foreach ($mercanOptions as $m)
                                            <option value="{{ $m->kode }}" @selected(($kdMercan ?? '') === $m->kode)>{{ $m->nama }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="sc-field">
                                <label for="dariTanggal">Dari Tanggal</label>
                                <div class="sc-control-wrap">
                                    <input type="date" id="dariTanggal" name="dari_tanggal" value="{{ $dariTanggal ?? '' }}" required>
                                </div>
                            </div>
                            <div class="sc-field">
                                <label for="sampaiTanggal">Sampai Tanggal</label>
                                <div class="sc-control-wrap">
                                    <input type="date" id="sampaiTanggal" name="sampai_tanggal" value="{{ $sampaiTanggal ?? '' }}" required>
                                </div>
                            </div>
                        </div>
                        <input type="hidden" name="nama_penerima" value="{{ $namaPenerima ?? '' }}">
                        <input type="hidden" name="nominal" value="{{ $nominal ?? '' }}">
                        <div class="sc-actions rp-actions">
                            <button type="submit" name="cari_transaksi" value="1" class="sc-btn sc-btn-primary">Cari Transaksi</button>
                            <button type="submit" name="liat_pencairan" value="1" class="sc-btn">Liat Pencairan</button>
                        </div>
                    </form>

                    <form method="POST" action="{{ route('smartcard.rekap_pencairan_kantin.store') }}" class="rp-panel">
                        @csrf
                        <div class="rp-panel-title">Simpan Pencairan</div>
                        <div class="sc-form-grid rp-form-grid">
                            <div class="sc-field">
                                <label for="kdMercanSave">Merchan</label>
                                <div class="sc-control-wrap sc-control-select">
                                    <select id="kdMercanSave" name="kd_mercan" required>
                                        <option value="">Pilih Merchan</option>
                                        @foreach ($mercanOptions as $m)
                                            <option value="{{ $m->kode }}" @selected(($kdMercan ?? '') === $m->kode)>{{ $m->nama }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="sc-field">
                                <label for="dariSave">Dari Tanggal</label>
                                <div class="sc-control-wrap">
                                    <input type="date" id="dariSave" name="dari_tanggal" value="{{ $dariTanggal ?? '' }}" required>
                                </div>
                            </div>
                            <div class="sc-field">
                                <label for="sampaiSave">Sampai Tanggal</label>
                                <div class="sc-control-wrap">
                                    <input type="date" id="sampaiSave" name="sampai_tanggal" value="{{ $sampaiTanggal ?? '' }}" required>
                                </div>
                            </div>
                            <div class="sc-field">
                                <label for="namaPenerima">Nama Penerima</label>
                                <div class="sc-control-wrap">
                                    <input type="text" id="namaPenerima" name="nama_penerima" value="{{ old('nama_penerima', $namaPenerima ?? '') }}" placeholder="Nama penerima" required>
                                </div>
                            </div>
                            <div class="sc-field">
                                <label for="nominal">Nominal</label>
                                <div class="sc-control-wrap sc-control-kartu">
                                    <input type="number" id="nominal" name="nominal" value="{{ old('nominal', $nominal ?? '') }}" min="1" step="1" placeholder="0" required>
                                </div>
                            </div>
                            <div class="sc-field">
                                <label>No Terima</label>
                                <div class="sc-control-wrap sc-control-readonly">
                                    <input type="text" value="Otomatis (YYYYMMDD + 001)" readonly tabindex="-1">
                                </div>
                            </div>
                        </div>
                        <div class="sc-actions rp-actions">
                            <button type="submit" class="sc-btn sc-btn-primary">Simpan</button>
                        </div>
                    </form>
                </div>

                <div class="rp-tables-grid">
                    <div class="sc-table-section rp-table-block">
                        <div class="sc-table-title">History Transaksi Kantin</div>
                        <div class="sc-table-wrap rp-table-wrap">
                            <table class="sc-table">
                                <thead>
                                    <tr>
                                        <th>Tgl Transaksi</th>
                                        <th style="text-align:right;">Saldo</th>
                                        <th>Merchan</th>
                                        <th>Kantin</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @if ($cariTransaksi ?? false)
                                        @forelse (($transaksiRows ?? collect()) as $row)
                                            <tr>
                                                <td>
                                                    @if (!empty($row->tgl_transaksi))
                                                        {{ \Illuminate\Support\Carbon::parse($row->tgl_transaksi)->format('d-m-Y H:i') }}
                                                    @else
                                                        —
                                                    @endif
                                                </td>
                                                <td style="text-align:right;">{{ number_format((float) ($row->saldo ?? 0), 0, ',', '.') }}</td>
                                                <td>{{ $row->mercan ?? '—' }}</td>
                                                <td>{{ $row->kantin ?? '—' }}</td>
                                            </tr>
                                        @empty
                                            <tr><td colspan="4" class="sc-empty">Tidak ada transaksi pada periode ini.</td></tr>
                                        @endforelse
                                        @if (($transaksiRows ?? collect())->isNotEmpty())
                                            <tr class="rp-total-row">
                                                <td><strong>Total</strong></td>
                                                <td style="text-align:right;"><strong>{{ number_format((float) ($transaksiTotal ?? 0), 0, ',', '.') }}</strong></td>
                                                <td colspan="2"></td>
                                            </tr>
                                        @endif
                                    @else
                                        <tr><td colspan="4" class="sc-empty">Pilih merchan & tanggal, lalu klik Cari Transaksi.</td></tr>
                                    @endif
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="sc-table-section rp-table-block">
                        <div class="sc-table-title">Data Pencairan</div>
                        <div class="sc-table-wrap rp-table-wrap">
                            <table class="sc-table">
                                <thead>
                                    <tr>
                                        <th>Tgl Terima</th>
                                        <th>Nama Penerima</th>
                                        <th style="text-align:right;">Nominal</th>
                                        <th>No Terima</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @if ($liatPencairan ?? false)
                                        @forelse (($pencairanRows ?? collect()) as $row)
                                            <tr>
                                                <td>
                                                    @if (!empty($row->tgl_terima))
                                                        {{ \Illuminate\Support\Carbon::parse($row->tgl_terima)->format('d-m-Y H:i') }}
                                                    @else
                                                        —
                                                    @endif
                                                </td>
                                                <td>{{ $row->nama_penerima ?? '—' }}</td>
                                                <td style="text-align:right;">{{ number_format((float) ($row->nominal ?? 0), 0, ',', '.') }}</td>
                                                <td>{{ $row->no_terima ?? '—' }}</td>
                                            </tr>
                                        @empty
                                            <tr><td colspan="4" class="sc-empty">Belum ada data pencairan.</td></tr>
                                        @endforelse
                                        @if (($pencairanRows ?? collect())->isNotEmpty())
                                            <tr class="rp-total-row">
                                                <td colspan="2"><strong>Total</strong></td>
                                                <td style="text-align:right;"><strong>{{ number_format((float) ($pencairanTotal ?? 0), 0, ',', '.') }}</strong></td>
                                                <td></td>
                                            </tr>
                                        @endif
                                    @else
                                        <tr><td colspan="4" class="sc-empty">Klik Liat Pencairan untuk menampilkan data.</td></tr>
                                    @endif
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @include('smartcard.partials.styles')

    <style>
        .rp-top-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 24px;
        }
        @media (max-width: 992px) {
            .rp-top-grid { grid-template-columns: 1fr; }
        }
        .rp-panel {
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 16px;
            background: #fafbfd;
        }
        .rp-panel-title {
            font-size: 14px;
            font-weight: 800;
            color: #374151;
            margin-bottom: 14px;
        }
        .rp-form-grid {
            grid-template-columns: 1fr;
            gap: 12px;
            margin-bottom: 0;
        }
        .rp-actions {
            margin-top: 14px;
            margin-bottom: 0;
        }
        .rp-tables-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        @media (max-width: 992px) {
            .rp-tables-grid { grid-template-columns: 1fr; }
        }
        .rp-table-block { margin-top: 0; padding-top: 0; border-top: 0; }
        .rp-table-wrap { max-height: 420px; }
        .rp-total-row td {
            background: #f3f4f6 !important;
            border-top: 2px solid #d1d5db;
        }
        .sc-control-select select,
        .sc-field input[type="date"],
        .sc-field input[type="number"] {
            width: 100%;
            height: 44px;
            border: 0;
            padding: 0 14px;
            font-size: 14px;
            background: #fff;
            color: #374151;
        }
        .sc-control-kartu input[type="number"] { background: #fffbeb; }
    </style>
@endsection
