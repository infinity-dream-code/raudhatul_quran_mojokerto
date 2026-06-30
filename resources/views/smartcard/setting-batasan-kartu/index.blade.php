@extends('layouts.app')

@section('content')
    <div class="sc-page">
        <div class="page-heading sc-page-heading">
            <h2>Setting Batasan Kartu</h2>
            <p>Smartcard / Batas nominal transaksi harian</p>
        </div>

        <div class="card sc-card">
            <div class="sc-card-body">
                @if (session('smartcard_success'))
                    <div class="sc-alert sc-alert-success">{{ session('smartcard_success') }}</div>
                @endif
                @if (session('smartcard_error'))
                    <div class="sc-alert sc-alert-error">{{ session('smartcard_error') }}</div>
                @endif

                <form id="formSearch" method="GET" action="{{ route('smartcard.batasan_kartu') }}">
                    <input type="hidden" name="search" value="1">

                    <div class="sc-form-grid">
                        <div class="sc-field">
                            <label for="periodeInput">Periode</label>
                            <div class="sc-control-wrap sc-control-kartu">
                                <input type="text" id="periodeInput" name="periode" value="{{ old('periode', $periode ?? '') }}"
                                       placeholder="Contoh: 202606 atau 2026-06" maxlength="20">
                            </div>
                        </div>
                        <div class="sc-field">
                            <label for="batasBelanjaInput">Batas Belanja Hari</label>
                            <div class="sc-control-wrap">
                                <input type="text" id="batasBelanjaInput" name="batas_belanja_hari"
                                       class="sc-formatted-number" value="{{ old('batas_belanja_hari', $batasBelanjaHari ?? '') }}"
                                       placeholder="0" inputmode="numeric">
                            </div>
                        </div>
                        <div class="sc-field">
                            <label for="batasCashInput">Batas Cash</label>
                            <div class="sc-control-wrap">
                                <input type="text" id="batasCashInput" name="batas_cash"
                                       class="sc-formatted-number" value="{{ old('batas_cash', $batasCash ?? '') }}"
                                       placeholder="0" inputmode="numeric">
                            </div>
                        </div>
                        <div class="sc-field">
                            <label for="aktifInput">Aktif</label>
                            <div class="sc-control-wrap sc-control-check">
                                <label class="sc-check-label">
                                    <input type="checkbox" id="aktifInput" name="aktif" value="1"
                                        @checked(old('aktif', $aktif ?? false))>
                                    <span>Periode aktif</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="sc-actions">
                        <button type="submit" class="sc-btn">Lihat</button>
                        <button type="submit" class="sc-btn sc-btn-primary" form="formSave">Simpan</button>
                    </div>
                </form>

                <form id="formSave" method="POST" action="{{ route('smartcard.batasan_kartu.store') }}">
                    @csrf
                    <input type="hidden" name="periode" id="periodeSave">
                    <input type="hidden" name="batas_belanja_hari" id="batasBelanjaSave">
                    <input type="hidden" name="batas_cash" id="batasCashSave">
                    <input type="hidden" name="aktif" id="aktifSave" value="0">
                </form>

                <p class="sc-footnote">Secara default batasan akan diberlakukan secara harian.</p>

                <div class="sc-table-section">
                    <div class="sc-table-title">
                        Daftar Batasan
                        @if (($isSearch ?? false) && ($periode ?? '') !== '')
                            <span class="sc-table-subtitle">— periode {{ $periode }}</span>
                        @endif
                    </div>
                    <div class="sc-table-wrap">
                        <table class="sc-table">
                            <thead>
                                <tr>
                                    <th style="width:64px;">No</th>
                                    <th>Periode</th>
                                    <th>Batas Belanja Hari</th>
                                    <th>Batas Cash</th>
                                    <th style="width:100px;">Aktif</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse (($batasanRows ?? null) as $index => $row)
                                    <tr>
                                        <td>{{ ($batasanRows->firstItem() ?? 0) + $index }}</td>
                                        <td>{{ $row->periode ?? '—' }}</td>
                                        <td>{{ number_format((int) ($row->batas_belanja_hari ?? 0), 0, ',', '.') }}</td>
                                        <td>{{ number_format((int) ($row->batas_cash ?? 0), 0, ',', '.') }}</td>
                                        <td>
                                            @if ((int) ($row->aktif ?? 0) === 1)
                                                <span class="sc-badge sc-badge-success">Aktif</span>
                                            @else
                                                <span class="sc-badge sc-badge-muted">Nonaktif</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="sc-empty">Data batasan tidak ditemukan.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if (isset($batasanRows) && method_exists($batasanRows, 'hasPages'))
                        <div class="sc-pagination-wrap">
                            <div class="sc-pagination-info">
                                Menampilkan {{ $batasanRows->firstItem() ?? 0 }} sampai {{ $batasanRows->lastItem() ?? 0 }} dari {{ $batasanRows->total() ?? 0 }} entri
                            </div>
                            @if ($batasanRows->hasPages())
                                <div class="sc-pagination">
                                    @php
                                        $current = $batasanRows->currentPage();
                                        $last = $batasanRows->lastPage();
                                        $start = max(1, $current - 2);
                                        $end = min($last, $current + 2);
                                    @endphp
                                    @if ($batasanRows->onFirstPage())
                                        <span class="sc-page-link disabled">Sebelumnya</span>
                                    @else
                                        <a class="sc-page-link" href="{{ $batasanRows->previousPageUrl() }}">Sebelumnya</a>
                                    @endif
                                    @for ($page = $start; $page <= $end; $page++)
                                        @if ($page === $current)
                                            <span class="sc-page-link active">{{ $page }}</span>
                                        @else
                                            <a class="sc-page-link" href="{{ $batasanRows->url($page) }}">{{ $page }}</a>
                                        @endif
                                    @endfor
                                    @if ($batasanRows->hasMorePages())
                                        <a class="sc-page-link" href="{{ $batasanRows->nextPageUrl() }}">Selanjutnya</a>
                                    @else
                                        <span class="sc-page-link disabled">Selanjutnya</span>
                                    @endif
                                </div>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @include('smartcard.partials.styles')

    <style>
        .sc-control-check {
            min-height: 44px;
            display: flex;
            align-items: center;
            padding: 0 14px;
        }
        .sc-check-label {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            margin: 0;
            font-size: 14px;
            font-weight: 600;
            color: #374151;
            cursor: pointer;
        }
        .sc-check-label input {
            width: 18px;
            height: 18px;
            cursor: pointer;
        }
        .sc-footnote {
            margin: -12px 0 20px;
            font-size: 13px;
            font-weight: 600;
            color: #7c3aed;
        }
        .sc-badge-muted {
            color: #6b7280;
            background: #f3f4f6;
        }
    </style>

    <script>
        (function () {
            const periodeInput = document.getElementById('periodeInput');
            const batasBelanjaInput = document.getElementById('batasBelanjaInput');
            const batasCashInput = document.getElementById('batasCashInput');
            const aktifInput = document.getElementById('aktifInput');
            const periodeSave = document.getElementById('periodeSave');
            const batasBelanjaSave = document.getElementById('batasBelanjaSave');
            const batasCashSave = document.getElementById('batasCashSave');
            const aktifSave = document.getElementById('aktifSave');

            function digitsOnly(value) {
                return String(value || '').replace(/[^\d]/g, '');
            }

            function formatNumberInput(el) {
                const raw = digitsOnly(el.value);
                el.value = raw ? parseInt(raw, 10).toLocaleString('id-ID') : '';
            }

            document.querySelectorAll('.sc-formatted-number').forEach(function (el) {
                if (el.value && /^\d+$/.test(el.value)) {
                    el.value = parseInt(el.value, 10).toLocaleString('id-ID');
                }
                el.addEventListener('input', function () { formatNumberInput(el); });
            });

            function syncSaveFields() {
                if (periodeSave && periodeInput) periodeSave.value = periodeInput.value;
                if (batasBelanjaSave && batasBelanjaInput) batasBelanjaSave.value = digitsOnly(batasBelanjaInput.value);
                if (batasCashSave && batasCashInput) batasCashSave.value = digitsOnly(batasCashInput.value);
                if (aktifSave) aktifSave.value = aktifInput && aktifInput.checked ? '1' : '0';
            }

            document.getElementById('formSave')?.addEventListener('submit', function (e) {
                syncSaveFields();
                const periodeDigits = digitsOnly(periodeSave?.value || '');
                if (periodeDigits.length !== 6) {
                    e.preventDefault();
                    alert('Periode wajib diisi dengan format 6 digit, contoh: 202606.');
                    return;
                }
                if (batasBelanjaSave?.value === '') {
                    e.preventDefault();
                    alert('Batas belanja harian wajib diisi.');
                    return;
                }
                if (batasCashSave?.value === '') {
                    e.preventDefault();
                    alert('Batas cash wajib diisi.');
                    return;
                }
            });

            syncSaveFields();
            [periodeInput, batasBelanjaInput, batasCashInput, aktifInput].forEach(function (el) {
                if (el) el.addEventListener('change', syncSaveFields);
                if (el) el.addEventListener('input', syncSaveFields);
            });
        })();
    </script>
@endsection
