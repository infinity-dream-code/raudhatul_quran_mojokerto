@extends('layouts.app')

@section('content')
    <div class="sc-page sc-page-wide">
        <div class="page-heading sc-page-heading">
            <h2>Pindah Saldo</h2>
            <p>Smartcard / Transfer saldo SPP ke uang saku</p>
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

                <div class="ps-keterangan">
                    <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
                    <span>
                        Ubah saldo <strong>sccttran</strong> (SPP) ke <strong>sccttran_cashless</strong> (uang saku),
                        dipotong biaya admin <strong>Rp {{ number_format((int) ($adminFee ?? 1000), 0, ',', '.') }}</strong>.
                    </span>
                </div>

                <div class="ps-builder-title">PINDAH SALDO SPP → UANG SAKU</div>

                <form method="GET" action="{{ route('smartcard.pindah_saldo') }}" id="psFormSearch">
                    <input type="hidden" name="search" value="1">
                    <input type="hidden" id="custidHidden" name="custid" value="{{ (int) ($custid ?? 0) }}">

                    <div class="ps-builder-panel">
                        <div class="ps-builder-scroll">
                            <div class="ps-builder-grid">
                                <div class="ps-cell sc-field-nis">
                                    <div class="ps-cell-label">NIS</div>
                                    <div id="siswaAutoWrap" class="sc-siswa-wrap ps-cell-input">
                                        <input type="text" id="siswaSearchInput" name="nis" autocomplete="off"
                                               value="{{ $nis ?? '' }}" placeholder="Ketik NIS / nama">
                                        <div id="siswaAutoList"></div>
                                    </div>
                                </div>
                                <div class="ps-cell">
                                    <div class="ps-cell-label">NAMA</div>
                                    <div class="ps-cell-input ps-cell-readonly">
                                        <input type="text" id="namaSiswa" value="{{ $nama ?? '' }}" readonly tabindex="-1" placeholder="—">
                                    </div>
                                </div>
                                <div class="ps-cell">
                                    <div class="ps-cell-label">Saldo SPP</div>
                                    <div class="ps-cell-input ps-cell-saldo-spp">
                                        <input type="text" id="saldoSppDisplay" value="{{ number_format((int) ($saldoSpp ?? 0), 0, ',', '.') }}" readonly tabindex="-1">
                                    </div>
                                </div>
                                <div class="ps-cell">
                                    <div class="ps-cell-label">Saldo Uang Saku</div>
                                    <div class="ps-cell-input ps-cell-saldo-cashless">
                                        <input type="text" id="saldoCashlessDisplay" value="{{ number_format((int) ($saldoCashless ?? 0), 0, ',', '.') }}" readonly tabindex="-1">
                                    </div>
                                </div>
                                <div class="ps-cell ps-cell-pindah">
                                    <div class="ps-cell-label">PINDAH</div>
                                    <div class="ps-cell-input ps-cell-highlight">
                                        <input type="text" id="nominalPindah" inputmode="numeric" autocomplete="off"
                                               placeholder="0" form="psFormStore">
                                    </div>
                                </div>
                                <div class="ps-cell">
                                    <div class="ps-cell-label">Tgl Manual</div>
                                    <div class="ps-cell-input">
                                        <input type="date" id="tanggalManual" name="tanggal_manual"
                                               value="{{ ($tanggalManual ?? '') !== '0000-00-00' ? ($tanggalManual ?? '') : '' }}">
                                    </div>
                                </div>
                                <div class="ps-cell ps-cell-note">
                                    <div class="ps-cell-label">Keterangan</div>
                                    <div class="ps-cell-input">
                                        <input type="text" id="note" name="note" value="{{ $note ?? '' }}" placeholder="Catatan (opsional)">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="ps-summary-bar">
                            <div class="ps-summary-item">
                                <span class="ps-summary-label">Biaya Admin</span>
                                <strong>Rp {{ number_format((int) ($adminFee ?? 1000), 0, ',', '.') }}</strong>
                            </div>
                            <div class="ps-summary-divider"></div>
                            <div class="ps-summary-item ps-summary-total">
                                <span class="ps-summary-label">Total Potong SPP</span>
                                <strong id="totalPotongDisplay">Rp 0</strong>
                            </div>
                        </div>
                    </div>

                    <div class="ps-actions">
                        <button type="submit" class="sc-btn sc-btn-primary ps-btn">
                            <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i> Cari
                        </button>
                        <button type="submit" form="psFormStore" class="sc-btn sc-btn-success ps-btn" id="btnPindah" @disabled((int)($custid ?? 0) <= 0)>
                            <i class="fa-solid fa-right-left" aria-hidden="true"></i> Pindah
                        </button>
                    </div>
                </form>

                <form method="POST" action="{{ route('smartcard.pindah_saldo.store') }}" id="psFormStore" class="ps-hidden-form">
                    @csrf
                    <input type="hidden" name="custid" id="custidStore" value="{{ (int) ($custid ?? 0) }}">
                    <input type="hidden" name="tanggal_manual" id="tanggalManualStore" value="{{ ($tanggalManual ?? '') !== '0000-00-00' ? ($tanggalManual ?? '') : '' }}">
                    <input type="hidden" name="note" id="noteStore" value="{{ $note ?? '' }}">
                </form>

                <div class="sc-table-section">
                    <div class="ps-table-head">
                        <div class="sc-table-title">Daftar Siswa</div>
                        @if ($isSearch ?? false)
                            <span class="ps-count-badge">{{ ($siswaPaginator ?? null)?->total() ?? 0 }} siswa</span>
                        @endif
                    </div>
                    <div class="sc-table-wrap ps-table-wrap">
                        <table class="sc-table" id="psTableSiswa">
                            <thead>
                                <tr>
                                    <th style="width:110px;">NIS</th>
                                    <th>Nama Siswa</th>
                                    <th style="text-align:right;width:110px;">Saldo SPP</th>
                                    <th style="text-align:right;width:110px;">Uang Saku</th>
                                    <th style="width:80px;">Kelas</th>
                                    <th style="width:90px;">Kelompok</th>
                                </tr>
                            </thead>
                            <tbody>
                                @if ($isSearch ?? false)
                                    @forelse (($siswaPaginator ?? null)?->items() ?? [] as $row)
                                        <tr class="ps-row-pick @if((int)($custid ?? 0) === (int)($row->custid ?? 0)) ps-row-active @endif"
                                            tabindex="0"
                                            data-custid="{{ (int) ($row->custid ?? 0) }}"
                                            data-nis="{{ $row->nis ?? '' }}"
                                            data-nama="{{ $row->nama ?? '' }}"
                                            data-saldo-spp="{{ (int) ($row->saldo_spp ?? 0) }}"
                                            data-saldo-cashless="{{ (int) ($row->saldo_cashless ?? 0) }}">
                                            <td><span class="ps-nis">{{ $row->nis ?? '—' }}</span></td>
                                            <td>{{ $row->nama ?? '—' }}</td>
                                            <td style="text-align:right;"><span class="ps-saldo-spp">{{ number_format((int) ($row->saldo_spp ?? 0), 0, ',', '.') }}</span></td>
                                            <td style="text-align:right;"><span class="ps-saldo-cashless">{{ number_format((int) ($row->saldo_cashless ?? 0), 0, ',', '.') }}</span></td>
                                            <td>{{ $row->kelas ?? '—' }}</td>
                                            <td>{{ $row->kelompok ?? '—' }}</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="6" class="sc-empty">Tidak ada data siswa.</td></tr>
                                    @endforelse
                                @else
                                    <tr>
                                        <td colspan="6" class="sc-empty">Klik <strong>Cari</strong> untuk menampilkan daftar siswa (10 per halaman).</td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>

                    @if (($isSearch ?? false) && ($siswaPaginator ?? null) && $siswaPaginator->total() > 0)
                        <div class="ps-table-footer">
                            <div>Menampilkan {{ $siswaPaginator->firstItem() }}–{{ $siswaPaginator->lastItem() }} dari {{ $siswaPaginator->total() }} siswa</div>
                            @if ($siswaPaginator->hasPages())
                                <div class="ps-table-pages">
                                    @if ($siswaPaginator->onFirstPage())
                                        <span class="ps-page disabled">Sebelumnya</span>
                                    @else
                                        <a class="ps-page" href="{{ $siswaPaginator->appends(request()->query())->previousPageUrl() }}">Sebelumnya</a>
                                    @endif
                                    <span class="ps-page active">{{ $siswaPaginator->currentPage() }}</span>
                                    @if ($siswaPaginator->hasMorePages())
                                        <a class="ps-page" href="{{ $siswaPaginator->appends(request()->query())->nextPageUrl() }}">Selanjutnya</a>
                                    @else
                                        <span class="ps-page disabled">Selanjutnya</span>
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
        .sc-page-wide { max-width: 1320px; }
        .ps-keterangan {
            display: flex; align-items: flex-start; gap: 10px;
            padding: 12px 16px; margin-bottom: 16px;
            background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 10px;
            font-size: 14px; color: #1e40af;
        }
        .ps-keterangan i { margin-top: 2px; }
        .ps-builder-title {
            font-family: 'Sora', sans-serif; font-size: 18px; font-weight: 800; color: #5b21b6;
            text-align: center; margin-bottom: 20px; letter-spacing: 0.04em; text-transform: uppercase;
        }
        .ps-builder-panel {
            border: 1px solid #c4b5fd; border-radius: 14px; overflow: hidden;
            margin-bottom: 20px; background: #fff; box-shadow: 0 4px 20px rgba(109, 40, 217, 0.08);
        }
        .ps-builder-scroll { overflow-x: auto; }
        .ps-builder-grid {
            display: grid;
            grid-template-columns: minmax(120px,1fr) minmax(140px,1.3fr) minmax(100px,0.9fr) minmax(110px,1fr) minmax(100px,0.9fr) minmax(120px,1fr) minmax(130px,1.1fr);
            min-width: 960px;
        }
        .ps-cell { display: flex; flex-direction: column; border-right: 1px solid #e9d5ff; }
        .ps-cell:last-child { border-right: 0; }
        .ps-cell-label {
            font-size: 12px; font-weight: 800; color: #5b21b6; letter-spacing: 0.06em; text-transform: uppercase;
            padding: 10px 12px; background: linear-gradient(180deg, #ede9fe 0%, #e9d5ff 100%);
            border-bottom: 1px solid #ddd6fe;
        }
        .ps-cell-input { flex: 1; min-height: 48px; display: flex; align-items: center; background: #fff; position: relative; }
        .ps-cell-input input { width: 100%; height: 48px; border: 0; padding: 0 12px; font-size: 14px; background: transparent; outline: none; }
        .ps-cell-readonly input { background: #faf5ff; color: #4b5563; }
        .ps-cell-saldo-spp input { background: #eff6ff; font-weight: 700; color: #1d4ed8; text-align: right; }
        .ps-cell-saldo-cashless input { background: #f0fdf4; font-weight: 700; color: #047857; text-align: right; }
        .ps-cell-highlight input { background: #fffbeb; font-weight: 700; color: #92400e; text-align: right; }
        .ps-summary-bar {
            display: flex; flex-wrap: wrap; align-items: center; gap: 16px 24px;
            padding: 12px 18px; background: linear-gradient(90deg, #faf5ff 0%, #f5f3ff 100%); border-top: 1px solid #e9d5ff;
        }
        .ps-summary-item { display: flex; align-items: center; gap: 8px; font-size: 13px; }
        .ps-summary-label { color: #6b7280; font-weight: 600; }
        .ps-summary-item strong { color: #5b21b6; font-size: 14px; }
        .ps-summary-total strong { color: #b45309; font-size: 15px; }
        .ps-summary-divider { width: 1px; height: 24px; background: #ddd6fe; }
        .ps-actions { display: flex; flex-wrap: wrap; justify-content: center; gap: 12px; margin-bottom: 24px; }
        .ps-btn { min-width: 140px; display: inline-flex; align-items: center; justify-content: center; gap: 8px; }
        .ps-hidden-form { display: none; }
        .ps-table-head { display: flex; align-items: center; gap: 10px; margin-bottom: 12px; }
        .ps-count-badge { font-size: 12px; font-weight: 700; color: #6d28d9; background: #ede9fe; padding: 4px 10px; border-radius: 999px; }
        .ps-table-wrap { max-height: 400px; overflow: auto; border: 1px solid #e9d5ff; border-radius: 12px; }
        .ps-row-pick { cursor: pointer; }
        .ps-row-pick:hover td { background: #faf5ff; }
        .ps-row-active td { background: #ede9fe !important; box-shadow: inset 3px 0 0 #7c3aed; }
        .ps-nis { font-family: ui-monospace, monospace; color: #5b21b6; font-weight: 600; }
        .ps-saldo-spp { font-weight: 700; color: #1d4ed8; }
        .ps-saldo-cashless { font-weight: 700; color: #047857; }
        .ps-table-footer {
            padding: 12px 4px 0; display: flex; justify-content: space-between; align-items: center;
            flex-wrap: wrap; gap: 8px; font-size: 12px; color: #6b7280;
        }
        .ps-table-pages { display: flex; gap: 6px; }
        .ps-page {
            min-width: 30px; height: 30px; border: 1px solid #c4b5fd; border-radius: 999px; padding: 0 10px;
            display: inline-flex; align-items: center; justify-content: center; text-decoration: none;
            color: #5b21b6; font-weight: 700; background: #fff;
        }
        .ps-page.active { background: #7c3aed; color: #fff; border-color: #7c3aed; }
        .ps-page.disabled { pointer-events: none; opacity: 0.45; }
        .sc-field-nis { position: relative; z-index: 1; }
        .sc-field-nis.sc-dropdown-open { z-index: 50; }
        #siswaAutoList {
            display: none; position: absolute; left: 0; right: 0; top: calc(100% + 4px); z-index: 200;
            background: #fff; border: 1px solid #c4b5fd; border-radius: 10px; max-height: 220px; overflow: auto;
            box-shadow: 0 12px 32px rgba(109, 40, 217, 0.15);
        }
        .ps-auto-item {
            width: 100%; text-align: left; padding: 10px 14px; border: 0; background: #fff;
            cursor: pointer; border-bottom: 1px solid #f3f4f6; font-size: 13px;
        }
        .ps-auto-item:hover { background: #f5f3ff; }
    </style>

    <script>
        (function () {
            const siswaSearchUrl = @json(route('keu.manual.siswa_search'));
            const adminFee = {{ (int) ($adminFee ?? 1000) }};
            const siswaInput = document.getElementById('siswaSearchInput');
            const custidHidden = document.getElementById('custidHidden');
            const custidStore = document.getElementById('custidStore');
            const namaSiswa = document.getElementById('namaSiswa');
            const saldoSppDisplay = document.getElementById('saldoSppDisplay');
            const saldoCashlessDisplay = document.getElementById('saldoCashlessDisplay');
            const nominalPindah = document.getElementById('nominalPindah');
            const totalPotongDisplay = document.getElementById('totalPotongDisplay');
            const tanggalManual = document.getElementById('tanggalManual');
            const tanggalManualStore = document.getElementById('tanggalManualStore');
            const noteInput = document.getElementById('note');
            const noteStore = document.getElementById('noteStore');
            const formStore = document.getElementById('psFormStore');
            const btnPindah = document.getElementById('btnPindah');
            const siswaList = document.getElementById('siswaAutoList');
            const siswaWrap = document.getElementById('siswaAutoWrap');
            const nisField = document.querySelector('.sc-field-nis');
            let searchTimer = null;
            let searchSeq = 0;

            const parseNum = function (v) { return parseInt(String(v || '').replace(/\D/g, ''), 10) || 0; };
            const formatRp = function (n) { return String(Math.max(0, parseInt(n, 10) || 0)).replace(/\B(?=(\d{3})+(?!\d))/g, '.'); };

            const updateTotalPotong = function () {
                const nominal = parseNum(nominalPindah ? nominalPindah.value : 0);
                if (totalPotongDisplay) totalPotongDisplay.textContent = 'Rp ' + formatRp(nominal + adminFee);
            };

            const syncHidden = function () {
                if (custidStore && custidHidden) custidStore.value = custidHidden.value || '';
                if (tanggalManualStore && tanggalManual) tanggalManualStore.value = tanggalManual.value || '';
                if (noteStore && noteInput) noteStore.value = noteInput.value || '';
                updateTotalPotong();
            };

            const pickSiswa = function (custid, nis, nama, saldoSpp, saldoCashless) {
                if (custidHidden) custidHidden.value = custid || '';
                if (siswaInput) siswaInput.value = nis || '';
                if (namaSiswa) namaSiswa.value = nama || '';
                if (saldoSppDisplay) saldoSppDisplay.value = formatRp(saldoSpp || 0);
                if (saldoCashlessDisplay) saldoCashlessDisplay.value = formatRp(saldoCashless || 0);
                if (nominalPindah) nominalPindah.value = '';
                if (btnPindah) btnPindah.disabled = parseInt(custid || '0', 10) <= 0;
                syncHidden();
            };

            if (nominalPindah) {
                nominalPindah.addEventListener('input', function () {
                    const raw = parseNum(nominalPindah.value);
                    nominalPindah.value = raw > 0 ? formatRp(raw) : '';
                    updateTotalPotong();
                });
            }

            if (formStore) {
                formStore.addEventListener('submit', function (e) {
                    syncHidden();
                    const cid = parseInt(custidStore ? custidStore.value : '0', 10);
                    const nominal = parseNum(nominalPindah ? nominalPindah.value : 0);
                    const saldoSpp = parseNum(saldoSppDisplay ? saldoSppDisplay.value : 0);
                    const total = nominal + adminFee;

                    if (cid <= 0) { e.preventDefault(); alert('Pilih siswa terlebih dahulu.'); return; }
                    if (!nominal) { e.preventDefault(); alert('Isi nominal PINDAH.'); nominalPindah?.focus(); return; }
                    if (total > saldoSpp) {
                        e.preventDefault();
                        alert('Saldo SPP tidak cukup.\nDibutuhkan: Rp ' + formatRp(total) + ' (pindah + admin).\nSaldo SPP: Rp ' + formatRp(saldoSpp));
                        return;
                    }

                    const hiddenNominal = document.createElement('input');
                    hiddenNominal.type = 'hidden';
                    hiddenNominal.name = 'nominal';
                    hiddenNominal.value = String(nominal);
                    formStore.appendChild(hiddenNominal);

                    if (!confirm('Pindah Rp ' + formatRp(nominal) + ' ke uang saku?\nPotong SPP: Rp ' + formatRp(total) + ' (termasuk admin Rp ' + formatRp(adminFee) + ')')) {
                        e.preventDefault();
                    }
                });
            }

            const tableBody = document.querySelector('#psTableSiswa tbody');
            if (tableBody) {
                tableBody.addEventListener('click', function (e) {
                    const row = e.target.closest('.ps-row-pick');
                    if (!row) return;
                    document.querySelectorAll('.ps-row-pick').forEach(function (r) { r.classList.remove('ps-row-active'); });
                    row.classList.add('ps-row-active');
                    pickSiswa(
                        row.getAttribute('data-custid'),
                        row.getAttribute('data-nis'),
                        row.getAttribute('data-nama'),
                        row.getAttribute('data-saldo-spp'),
                        row.getAttribute('data-saldo-cashless')
                    );
                    nominalPindah?.focus();
                });
            }

            if (siswaInput && siswaList && siswaWrap) {
                const closeList = function () {
                    siswaList.style.display = 'none';
                    siswaList.innerHTML = '';
                    if (nisField) nisField.classList.remove('sc-dropdown-open');
                };
                const openDropdown = function () { if (nisField) nisField.classList.add('sc-dropdown-open'); };

                siswaList.addEventListener('click', function (e) {
                    const btn = e.target.closest('button[data-cid]');
                    if (!btn) return;
                    pickSiswa(btn.getAttribute('data-cid'), btn.getAttribute('data-nis'), btn.getAttribute('data-nmcust'), 0, 0);
                    closeList();
                });

                siswaInput.addEventListener('input', function () {
                    custidHidden.value = '';
                    if (namaSiswa) namaSiswa.value = '';
                    if (saldoSppDisplay) saldoSppDisplay.value = '0';
                    if (saldoCashlessDisplay) saldoCashlessDisplay.value = '0';
                    if (btnPindah) btnPindah.disabled = true;
                    syncHidden();
                    clearTimeout(searchTimer);
                    const q = String(siswaInput.value || '').trim();
                    if (q.length < 2) { closeList(); return; }
                    searchTimer = setTimeout(function () {
                        const seq = ++searchSeq;
                        openDropdown();
                        fetch(siswaSearchUrl + '?mode=nis&q=' + encodeURIComponent(q), {
                            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                            credentials: 'same-origin',
                        })
                            .then(function (res) { return res.json(); })
                            .then(function (json) {
                                if (seq !== searchSeq) return;
                                const rows = Array.isArray(json.rows) ? json.rows : [];
                                if (!rows.length) {
                                    siswaList.innerHTML = '<div style="padding:10px 14px;color:#6b7280;font-size:13px;">Siswa tidak ditemukan.</div>';
                                } else {
                                    siswaList.innerHTML = rows.map(function (r) {
                                        return '<button type="button" class="ps-auto-item" data-cid="' + r.cid + '" data-nis="' + (r.nocust || '') + '" data-nmcust="' + (r.nmcust || '').replace(/"/g, '&quot;') + '">' + (r.label || '').replace(/</g, '&lt;') + '</button>';
                                    }).join('');
                                }
                                siswaList.style.display = 'block';
                            });
                    }, 400);
                });

                document.addEventListener('click', function (e) {
                    if (!siswaWrap.contains(e.target)) closeList();
                });
            }

            if (tanggalManual) tanggalManual.addEventListener('change', syncHidden);
            if (noteInput) noteInput.addEventListener('input', syncHidden);
            syncHidden();
        })();
    </script>
@endsection
