@extends('layouts.app')

@section('content')
    <div class="sc-page sc-page-wide">
        <div class="page-heading sc-page-heading">
            <h2>TOPUP Cash</h2>
            <p>Smartcard / Input topup saldo uang saku (cash)</p>
        </div>

        <div class="card sc-card">
            <div class="sc-card-body">
                @if (session('smartcard_success'))
                    <div class="sc-alert sc-alert-success">{{ session('smartcard_success') }}</div>
                @endif
                @if (session('smartcard_error'))
                    <div class="sc-alert sc-alert-error">{{ session('smartcard_error') }}</div>
                @endif
                @if ($errors->any())
                    <div class="sc-alert sc-alert-error">{{ $errors->first() }}</div>
                @endif

                <div class="tc-keterangan">
                    <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
                    <span>
                        Kolom <strong>TOP UP</strong> = nominal bayar;
                        uang saku masuk setelah biaya admin
                        <strong>Rp {{ number_format((int) ($cashFee ?? 2000), 0, ',', '.') }}</strong>.
                    </span>
                </div>

                <div class="tc-builder-title">TOPUP CASH SALDO</div>

                <form method="GET" action="{{ route('smartcard.topup_cash') }}" id="tcFormSearch">
                    <input type="hidden" name="search" value="1">
                    <input type="hidden" id="custidHidden" name="custid" value="{{ (int) ($custid ?? 0) }}">

                    <div class="tc-builder-panel">
                        <div class="tc-builder-scroll">
                            <div class="tc-builder-grid">
                                <div class="tc-cell sc-field-nis">
                                    <div class="tc-cell-label">NIS</div>
                                    <div id="siswaAutoWrap" class="sc-siswa-wrap tc-cell-input">
                                        <input type="text" id="siswaSearchInput" name="nis" autocomplete="off"
                                               value="{{ $nis ?? '' }}" placeholder="Ketik NIS / nama">
                                        <div id="siswaAutoList"></div>
                                    </div>
                                </div>
                                <div class="tc-cell">
                                    <div class="tc-cell-label">NAMA</div>
                                    <div class="tc-cell-input tc-cell-readonly">
                                        <input type="text" id="namaSiswa" value="{{ $nama ?? '' }}" readonly tabindex="-1" placeholder="—">
                                    </div>
                                </div>
                                <div class="tc-cell">
                                    <div class="tc-cell-label">Metode</div>
                                    <div class="tc-cell-input">
                                        <select id="metode" name="metode">
                                            <option value="Cash" @selected(($metode ?? 'Cash') === 'Cash')>Cash</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="tc-cell tc-cell-topup">
                                    <div class="tc-cell-label">TOP UP</div>
                                    <div class="tc-cell-input tc-cell-highlight">
                                        <input type="text" id="nominalTopup" inputmode="numeric" autocomplete="off"
                                               placeholder="0" form="tcFormTopup">
                                    </div>
                                </div>
                                <div class="tc-cell">
                                    <div class="tc-cell-label">SALDO</div>
                                    <div class="tc-cell-input tc-cell-saldo">
                                        <input type="text" id="saldoDisplay" value="{{ number_format((int) ($saldo ?? 0), 0, ',', '.') }}" readonly tabindex="-1">
                                    </div>
                                </div>
                                <div class="tc-cell">
                                    <div class="tc-cell-label">Tanggal Manual</div>
                                    <div class="tc-cell-input">
                                        <input type="date" id="tanggalManual" name="tanggal_manual"
                                               value="{{ ($tanggalManual ?? '') !== '0000-00-00' ? ($tanggalManual ?? '') : '' }}">
                                    </div>
                                </div>
                                <div class="tc-cell tc-cell-note">
                                    <div class="tc-cell-label">Note</div>
                                    <div class="tc-cell-input">
                                        <input type="text" id="note" name="note" value="{{ $note ?? '' }}" placeholder="Catatan">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="tc-summary-bar">
                            <div class="tc-summary-item">
                                <span class="tc-summary-label">Biaya Cash</span>
                                <strong id="feeDisplay">Rp {{ number_format((int) ($cashFee ?? 2000), 0, ',', '.') }}</strong>
                            </div>
                            <div class="tc-summary-divider"></div>
                            <div class="tc-summary-item tc-summary-total">
                                <span class="tc-summary-label">Total Saldo Didapat</span>
                                <strong id="saldoDidapatDisplay">Rp 0</strong>
                            </div>
                        </div>
                    </div>

                    <div class="tc-actions">
                        <button type="submit" class="sc-btn sc-btn-primary tc-btn">
                            <i class="fa-solid fa-magnifying-glass tc-btn-icon" aria-hidden="true"></i> Cari
                        </button>
                        <button type="submit" form="tcFormTopup" class="sc-btn sc-btn-success tc-btn" id="btnTopup">
                            <i class="fa-solid fa-circle-arrow-up tc-btn-icon" aria-hidden="true"></i> TOPUP
                        </button>
                        <button type="submit" form="tcFormKuitansi" class="sc-btn tc-btn tc-btn-outline" id="btnKuitansi" @disabled((int)($custid ?? 0) <= 0)>
                            <i class="fa-solid fa-print tc-btn-icon" aria-hidden="true"></i> Cetak Kuitansi
                        </button>
                    </div>
                </form>

                <form method="POST" action="{{ route('smartcard.topup_cash.store') }}" id="tcFormTopup" class="tc-hidden-form">
                    @csrf
                    <input type="hidden" name="custid" id="custidTopup" value="{{ (int) ($custid ?? 0) }}">
                    <input type="hidden" name="metode" id="metodeTopup" value="{{ $metode ?? 'Cash' }}">
                    <input type="hidden" name="tanggal_manual" id="tanggalManualTopup" value="{{ ($tanggalManual ?? '') !== '0000-00-00' ? ($tanggalManual ?? '') : '' }}">
                    <input type="hidden" name="note" id="noteTopup" value="{{ $note ?? '' }}">
                </form>

                <form method="POST" action="{{ route('smartcard.topup_cash.kuitansi') }}" id="tcFormKuitansi" target="_blank" class="tc-hidden-form">
                    @csrf
                    <input type="hidden" name="custid" id="custidKuitansi" value="{{ (int) ($custid ?? 0) }}">
                    <input type="hidden" name="transno" id="transnoKuitansi" value="{{ $selectedTransNo ?? '' }}">
                    <input type="hidden" name="reprint" id="reprintKuitansi" value="{{ ($reprintKuitansi ?? false) ? '1' : '0' }}">
                    <input type="hidden" name="nominal" id="nominalKuitansi" value="0">
                    <input type="hidden" name="note" id="noteKuitansi" value="{{ $note ?? '' }}">
                    <input type="hidden" name="metode" id="metodeKuitansi" value="{{ $metode ?? 'Cash' }}">
                    <input type="hidden" name="tanggal_manual" id="tanggalManualKuitansi" value="{{ ($tanggalManual ?? '') !== '0000-00-00' ? ($tanggalManual ?? '') : '' }}">
                </form>

                <div class="sc-table-section">
                    <div class="tc-table-head">
                        <div class="sc-table-title">Daftar Siswa</div>
                        @if ($isSearch ?? false)
                            <span class="tc-count-badge">{{ ($siswaPaginator ?? null)?->total() ?? 0 }} siswa</span>
                        @endif
                    </div>
                    <div class="sc-table-wrap tc-table-wrap">
                        <table class="sc-table" id="tcTableSiswa">
                            <thead>
                                <tr>
                                    <th style="width:120px;">NIS</th>
                                    <th>Nama Siswa</th>
                                    <th style="text-align:right;width:120px;">SALDO</th>
                                    <th style="width:80px;">Kelas</th>
                                    <th style="width:100px;">Kelompok</th>
                                    <th style="width:100px;">Jenjang</th>
                                </tr>
                            </thead>
                            <tbody>
                                @if ($isSearch ?? false)
                                    @forelse (($siswaPaginator ?? null)?->items() ?? [] as $row)
                                        <tr class="tc-row-pick @if((int)($custid ?? 0) === (int)($row->custid ?? 0)) tc-row-active @endif"
                                            tabindex="0"
                                            data-custid="{{ (int) ($row->custid ?? 0) }}"
                                            data-nis="{{ $row->nis ?? '' }}"
                                            data-nama="{{ $row->nama ?? '' }}"
                                            data-saldo="{{ (int) ($row->saldo ?? 0) }}">
                                            <td><span class="tc-nis">{{ $row->nis ?? '—' }}</span></td>
                                            <td>{{ $row->nama ?? '—' }}</td>
                                            <td style="text-align:right;"><span class="tc-saldo-num">{{ number_format((int) ($row->saldo ?? 0), 0, ',', '.') }}</span></td>
                                            <td>{{ $row->kelas ?? '—' }}</td>
                                            <td>{{ $row->kelompok ?? '—' }}</td>
                                            <td>{{ $row->jenjang ?? '—' }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="sc-empty">
                                                <div class="tc-empty-state">
                                                    <i class="fa-solid fa-clipboard-list tc-empty-icon" aria-hidden="true"></i>
                                                    <div>Tidak ada data siswa.</div>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforelse
                                @else
                                    <tr>
                                        <td colspan="6" class="sc-empty">
                                            <div class="tc-empty-state">
                                                <i class="fa-regular fa-hand-pointer tc-empty-icon" aria-hidden="true"></i>
                                                <div>Klik <strong>Cari</strong> untuk menampilkan daftar siswa (10 data per halaman).</div>
                                            </div>
                                        </td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>

                    @if (($isSearch ?? false) && ($siswaPaginator ?? null))
                        <div class="tc-table-footer">
                            <div>
                                Menampilkan {{ $siswaPaginator->firstItem() ?? 0 }}–{{ $siswaPaginator->lastItem() ?? 0 }}
                                dari {{ $siswaPaginator->total() }} siswa
                            </div>
                            <div class="tc-table-pages">
                                @if ($siswaPaginator->onFirstPage())
                                    <span class="tc-page disabled">Sebelumnya</span>
                                @else
                                    <a class="tc-page" href="{{ $siswaPaginator->appends(request()->query())->previousPageUrl() }}">Sebelumnya</a>
                                @endif
                                <span class="tc-page active">{{ $siswaPaginator->currentPage() }}</span>
                                @if ($siswaPaginator->hasMorePages())
                                    <a class="tc-page" href="{{ $siswaPaginator->appends(request()->query())->nextPageUrl() }}">Selanjutnya</a>
                                @else
                                    <span class="tc-page disabled">Selanjutnya</span>
                                @endif
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

        .tc-keterangan {
            display: flex; align-items: flex-start; gap: 10px;
            padding: 12px 16px; margin-bottom: 16px;
            background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 10px;
            font-size: 13px; color: #1e40af;
        }
        .tc-keterangan i { margin-top: 2px; }
        .tc-builder-title {
            font-family: 'Sora', sans-serif;
            font-size: 18px;
            font-weight: 800;
            color: #5b21b6;
            text-align: center;
            margin-bottom: 20px;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }

        .tc-builder-panel {
            border: 1px solid #c4b5fd;
            border-radius: 14px;
            overflow: hidden;
            margin-bottom: 20px;
            background: #fff;
            box-shadow: 0 4px 20px rgba(109, 40, 217, 0.08);
        }

        .tc-builder-scroll {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        .tc-builder-grid {
            display: grid;
            grid-template-columns: minmax(130px, 1.1fr) minmax(150px, 1.4fr) minmax(90px, 0.8fr) minmax(100px, 0.9fr) minmax(110px, 1fr) minmax(130px, 1fr) minmax(140px, 1.2fr);
            min-width: 900px;
        }

        .tc-cell {
            display: flex;
            flex-direction: column;
            border-right: 1px solid #e9d5ff;
        }
        .tc-cell:last-child { border-right: 0; }

        .tc-cell-label {
            font-size: 12px;
            font-weight: 800;
            color: #5b21b6;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            padding: 10px 12px;
            background: linear-gradient(180deg, #ede9fe 0%, #e9d5ff 100%);
            border-bottom: 1px solid #ddd6fe;
            white-space: nowrap;
        }

        .tc-cell-input {
            flex: 1;
            min-height: 48px;
            display: flex;
            align-items: center;
            background: #fff;
            position: relative;
        }

        .tc-cell-input input,
        .tc-cell-input select {
            width: 100%;
            height: 48px;
            border: 0;
            padding: 0 12px;
            font-size: 14px;
            background: transparent;
            color: #1f2937;
            outline: none;
        }

        .tc-cell-input select {
            cursor: pointer;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%235b21b6' d='M6 8L1 3h10z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 12px center;
            padding-right: 32px;
        }

        .tc-cell-readonly input {
            color: #4b5563;
            background: #faf5ff;
        }

        .tc-cell-highlight input {
            background: #fffbeb;
            font-weight: 700;
            font-size: 15px;
            color: #92400e;
        }

        .tc-cell-saldo input {
            background: #f0fdf4;
            font-weight: 700;
            color: #047857;
            text-align: right;
        }

        .tc-summary-bar {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 16px 24px;
            padding: 12px 18px;
            background: linear-gradient(90deg, #faf5ff 0%, #f5f3ff 100%);
            border-top: 1px solid #e9d5ff;
        }

        .tc-summary-item {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
        }

        .tc-summary-label {
            color: #6b7280;
            font-weight: 600;
        }

        .tc-summary-item strong {
            color: #5b21b6;
            font-size: 14px;
        }

        .tc-summary-total strong {
            color: #047857;
            font-size: 15px;
        }

        .tc-summary-divider {
            width: 1px;
            height: 24px;
            background: #ddd6fe;
        }

        .tc-actions {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 12px;
            margin-bottom: 28px;
            padding: 16px;
            background: #f9fafb;
            border-radius: 12px;
            border: 1px solid #e5e7eb;
        }

        .tc-btn {
            min-width: 148px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            height: 44px;
            border-radius: 10px;
            font-weight: 700;
            transition: transform .12s, box-shadow .12s;
        }
        .tc-btn:hover:not(:disabled) {
            transform: translateY(-1px);
        }
        .tc-btn:disabled {
            opacity: 0.45;
            cursor: not-allowed;
        }
        .tc-btn-outline {
            background: #fff;
            border: 1px solid #d1d5db;
        }
        .tc-btn-icon { width: 1em; text-align: center; }

        .tc-table-head {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 12px;
        }

        .tc-count-badge {
            font-size: 12px;
            font-weight: 700;
            color: #6d28d9;
            background: #ede9fe;
            padding: 4px 10px;
            border-radius: 999px;
        }

        .tc-table-wrap { max-height: 400px; }

        .tc-row-pick { cursor: pointer; transition: background .12s; }
        .tc-row-pick.tc-row-active {
            background: #ede9fe !important;
            box-shadow: inset 3px 0 0 #7c3aed;
        }
        .tc-row-pick:hover:not(.tc-row-active) { background: #f5f3ff !important; }

        .tc-nis {
            font-family: ui-monospace, monospace;
            font-size: 13px;
            color: #4b5563;
        }
        .tc-saldo-num {
            font-weight: 700;
            color: #047857;
        }

        .tc-empty-state {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
            padding: 16px;
        }
        .tc-empty-icon { font-size: 1.75rem; color: #a78bfa; opacity: 0.85; }

        .sc-field-nis { position: relative; z-index: 1; }
        .sc-field-nis.sc-dropdown-open { z-index: 50; }

        #siswaAutoList {
            display: none;
            position: absolute;
            left: 0;
            right: 0;
            top: calc(100% + 4px);
            z-index: 200;
            background: #fff;
            border: 1px solid #c4b5fd;
            border-radius: 10px;
            max-height: 220px;
            overflow: auto;
            box-shadow: 0 12px 32px rgba(109, 40, 217, 0.15);
        }

        .tc-hidden-form { display: none; }

        .tc-table-footer {
            padding: 12px 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 8px;
            font-size: 12px;
            color: #6b7280;
            border-top: 1px solid #ede9fe;
            background: #faf5ff;
        }
        .tc-table-pages { display: flex; gap: 6px; align-items: center; }
        .tc-page {
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
        .tc-page.active { background: #7c3aed; color: #fff; border-color: #7c3aed; }
        .tc-page.disabled { pointer-events: none; opacity: 0.45; }

        @media (max-width: 768px) {
            .tc-builder-grid { min-width: 760px; }
            .tc-actions { padding: 12px; }
            .tc-btn { min-width: 120px; flex: 1; }
        }
    </style>

    <script>
        (function () {
            const siswaSearchUrl = @json(route('keu.manual.siswa_search'));
            const cashFee = {{ (int) ($cashFee ?? 2000) }};
            const siswaInput = document.getElementById('siswaSearchInput');
            const custidHidden = document.getElementById('custidHidden');
            const custidTopup = document.getElementById('custidTopup');
            const custidKuitansi = document.getElementById('custidKuitansi');
            const namaSiswa = document.getElementById('namaSiswa');
            const saldoDisplay = document.getElementById('saldoDisplay');
            const siswaList = document.getElementById('siswaAutoList');
            const siswaWrap = document.getElementById('siswaAutoWrap');
            const nisField = document.querySelector('.sc-field-nis');
            const nominalTopup = document.getElementById('nominalTopup');
            const saldoDidapatDisplay = document.getElementById('saldoDidapatDisplay');
            const metodeSelect = document.getElementById('metode');
            const metodeTopup = document.getElementById('metodeTopup');
            const tanggalManual = document.getElementById('tanggalManual');
            const tanggalManualTopup = document.getElementById('tanggalManualTopup');
            const noteInput = document.getElementById('note');
            const noteTopup = document.getElementById('noteTopup');
            const formTopup = document.getElementById('tcFormTopup');
            const formKuitansi = document.getElementById('tcFormKuitansi');
            const btnKuitansi = document.getElementById('btnKuitansi');
            const transnoKuitansi = document.getElementById('transnoKuitansi');
            const reprintKuitansi = document.getElementById('reprintKuitansi');
            let searchTimer = null;
            let searchSeq = 0;

            const parseNum = function (v) {
                return parseInt(String(v || '').replace(/\D/g, ''), 10) || 0;
            };

            const formatRp = function (n) {
                return String(Math.max(0, parseInt(n, 10) || 0)).replace(/\B(?=(\d{3})+(?!\d))/g, '.');
            };

            const updateSaldoDidapat = function () {
                const nominal = parseNum(nominalTopup ? nominalTopup.value : 0);
                const metode = metodeSelect ? metodeSelect.value : 'Cash';
                const fee = metode === 'Cash' ? cashFee : 0;
                const didapat = Math.max(0, nominal - fee);
                if (saldoDidapatDisplay) {
                    saldoDidapatDisplay.textContent = 'Rp ' + formatRp(didapat);
                }
            };

            const syncHiddenFields = function () {
                if (custidTopup && custidHidden) custidTopup.value = custidHidden.value || '';
                if (metodeTopup && metodeSelect) metodeTopup.value = metodeSelect.value || 'Cash';
                if (tanggalManualTopup && tanggalManual) tanggalManualTopup.value = tanggalManual.value || '';
                if (noteTopup && noteInput) noteTopup.value = noteInput.value || '';
                updateSaldoDidapat();
            };

            const setKuitansiState = function (custid, transno, reprint) {
                const cid = String(custid || '');
                const no = String(transno || '').trim();
                if (custidKuitansi) custidKuitansi.value = cid;
                if (transnoKuitansi) transnoKuitansi.value = no;
                if (reprintKuitansi) reprintKuitansi.value = reprint ? '1' : '0';
                if (btnKuitansi) btnKuitansi.disabled = parseInt(cid, 10) <= 0;
                syncKuitansiFormFields();
            };

            const syncKuitansiFormFields = function () {
                const nominalKuitansi = document.getElementById('nominalKuitansi');
                const noteKuitansi = document.getElementById('noteKuitansi');
                const metodeKuitansi = document.getElementById('metodeKuitansi');
                const tanggalManualKuitansi = document.getElementById('tanggalManualKuitansi');
                const topupNominal = parseNum(nominalTopup ? nominalTopup.value : 0);
                const saldoNominal = parseNum(saldoDisplay ? saldoDisplay.value : 0);
                if (nominalKuitansi) {
                    nominalKuitansi.value = String(topupNominal > 0 ? topupNominal : saldoNominal);
                }
                if (noteKuitansi && noteInput) noteKuitansi.value = noteInput.value || '';
                if (metodeKuitansi && metodeSelect) metodeKuitansi.value = metodeSelect.value || 'Cash';
                if (tanggalManualKuitansi && tanggalManual) tanggalManualKuitansi.value = tanggalManual.value || '';
            };

            const pickSiswa = function (custid, nis, nama, saldo) {
                if (custidHidden) custidHidden.value = custid || '';
                if (siswaInput) siswaInput.value = nis || '';
                if (namaSiswa) namaSiswa.value = nama || '';
                if (saldoDisplay) saldoDisplay.value = formatRp(saldo || 0);
                if (nominalTopup) nominalTopup.value = '';
                syncHiddenFields();
                setKuitansiState(custid, '', false);
            };

            if (nominalTopup) {
                nominalTopup.addEventListener('input', function () {
                    const raw = parseNum(nominalTopup.value);
                    nominalTopup.value = raw > 0 ? formatRp(raw) : '';
                    updateSaldoDidapat();
                    syncKuitansiFormFields();
                });
            }

            if (formTopup) {
                formTopup.addEventListener('submit', function (e) {
                    syncHiddenFields();
                    const cid = parseInt(custidTopup ? custidTopup.value : '0', 10);
                    const nominal = parseNum(nominalTopup ? nominalTopup.value : 0);
                    if (cid <= 0) {
                        e.preventDefault();
                        alert('Pilih siswa terlebih dahulu.');
                        return;
                    }
                    if (!nominal || nominal < 1) {
                        e.preventDefault();
                        alert('Isi nominal TOP UP.');
                        nominalTopup?.focus();
                        return;
                    }
                    const metode = metodeSelect ? metodeSelect.value : 'Cash';
                    const fee = metode === 'Cash' ? cashFee : 0;
                    const didapat = Math.max(0, nominal - fee);

                    if (metode === 'Cash' && nominal <= fee) {
                        e.preventDefault();
                        alert('Nominal top up harus lebih dari biaya admin Rp ' + formatRp(fee) + '.');
                        nominalTopup?.focus();
                        return;
                    }

                    const hiddenNominal = document.createElement('input');
                    hiddenNominal.type = 'hidden';
                    hiddenNominal.name = 'nominal';
                    hiddenNominal.value = String(nominal);
                    formTopup.appendChild(hiddenNominal);

                    if (metode === 'Cash') {
                        if (!confirm(
                            'Bayar Rp ' + formatRp(nominal) + '\n'
                            + 'Uang saku masuk: Rp ' + formatRp(didapat) + ' (setelah biaya admin Rp ' + formatRp(fee) + ')'
                        )) {
                            e.preventDefault();
                        }
                    }
                });
            }

            if (formKuitansi) {
                formKuitansi.addEventListener('submit', function (e) {
                    syncKuitansiFormFields();
                    const cid = parseInt(custidKuitansi ? custidKuitansi.value : '0', 10);
                    if (cid <= 0) {
                        e.preventDefault();
                        alert('Pilih siswa terlebih dahulu.');
                    }
                });
            }

            const tableBody = document.querySelector('#tcTableSiswa tbody');
            if (tableBody) {
                tableBody.addEventListener('click', function (e) {
                    const row = e.target.closest('.tc-row-pick');
                    if (!row) return;
                    document.querySelectorAll('.tc-row-pick').forEach(function (r) { r.classList.remove('tc-row-active'); });
                    row.classList.add('tc-row-active');
                    pickSiswa(
                        row.getAttribute('data-custid'),
                        row.getAttribute('data-nis'),
                        row.getAttribute('data-nama'),
                        row.getAttribute('data-saldo')
                    );
                    nominalTopup?.focus();
                });
                tableBody.addEventListener('keydown', function (e) {
                    if (e.key !== 'Enter' && e.key !== ' ') return;
                    const row = e.target.closest('.tc-row-pick');
                    if (!row) return;
                    e.preventDefault();
                    row.click();
                });
            }

            if (!siswaInput || !custidHidden || !siswaList || !siswaWrap) {
                syncHiddenFields();
                return;
            }

            const openDropdown = function () {
                if (nisField) nisField.classList.add('sc-dropdown-open');
            };
            const closeList = function () {
                siswaList.style.display = 'none';
                siswaList.innerHTML = '';
                if (nisField) nisField.classList.remove('sc-dropdown-open');
            };

            const renderRows = function (matched) {
                openDropdown();
                if (!matched.length) {
                    siswaList.innerHTML = '<div style="padding:10px 14px;color:#6b7280;font-size:13px;">Siswa tidak ditemukan.</div>';
                    siswaList.style.display = 'block';
                    return;
                }
                siswaList.innerHTML = matched.map(function (r) {
                    const label = (r.label || '').replace(/</g, '&lt;').replace(/"/g, '&quot;');
                    const nmcust = (r.nmcust || '').replace(/"/g, '&quot;');
                    const nisVal = (r.nocust || r.nis_like || r.nis || '').replace(/"/g, '&quot;');
                    return '<button type="button" data-cid="' + r.cid + '" data-nis="' + nisVal + '" data-nmcust="' + nmcust + '" class="tc-auto-item">' + label + '</button>';
                }).join('');
                siswaList.style.display = 'block';
            };

            siswaList.addEventListener('click', function (e) {
                const btn = e.target.closest('button[data-cid]');
                if (!btn) return;
                pickSiswa(
                    btn.getAttribute('data-cid') || '',
                    btn.getAttribute('data-nis') || '',
                    btn.getAttribute('data-nmcust') || '',
                    0
                );
                closeList();
            });

            const fetchSiswa = function (q) {
                const query = String(q || '').trim();
                if (query.length < 2) {
                    closeList();
                    return;
                }
                const seq = ++searchSeq;
                openDropdown();
                siswaList.innerHTML = '<div style="padding:10px 14px;color:#6b7280;font-size:13px;">Mencari…</div>';
                siswaList.style.display = 'block';
                fetch(siswaSearchUrl + '?mode=nis&q=' + encodeURIComponent(query), {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin',
                })
                    .then(function (res) { return res.json(); })
                    .then(function (json) {
                        if (seq !== searchSeq) return;
                        renderRows(Array.isArray(json.rows) ? json.rows : []);
                    })
                    .catch(function () {
                        if (seq !== searchSeq) return;
                        siswaList.innerHTML = '<div style="padding:10px 14px;color:#b91c1c;font-size:13px;">Gagal memuat data siswa.</div>';
                        siswaList.style.display = 'block';
                    });
            };

            siswaInput.addEventListener('input', function () {
                custidHidden.value = '';
                if (namaSiswa) namaSiswa.value = '';
                if (saldoDisplay) saldoDisplay.value = '0';
                setKuitansiState('', '', false);
                syncHiddenFields();
                clearTimeout(searchTimer);
                searchTimer = setTimeout(function () { fetchSiswa(siswaInput.value); }, 400);
            });

            siswaInput.addEventListener('focus', function () {
                if (String(siswaInput.value || '').trim() !== '') {
                    fetchSiswa(siswaInput.value);
                }
            });

            document.addEventListener('click', function (e) {
                if (!siswaWrap.contains(e.target)) closeList();
            });

            if (metodeSelect) metodeSelect.addEventListener('change', function () { syncHiddenFields(); syncKuitansiFormFields(); });
            if (tanggalManual) tanggalManual.addEventListener('change', function () { syncHiddenFields(); syncKuitansiFormFields(); });
            if (noteInput) noteInput.addEventListener('input', function () { syncHiddenFields(); syncKuitansiFormFields(); });

            syncHiddenFields();
            syncKuitansiFormFields();
        })();
    </script>

    <style>
        .tc-auto-item {
            width: 100%;
            text-align: left;
            padding: 10px 14px;
            border: 0;
            background: #fff;
            cursor: pointer;
            border-bottom: 1px solid #f3f4f6;
            font-size: 13px;
            color: #374151;
        }
        .tc-auto-item:hover { background: #f5f3ff; }
    </style>
@endsection
