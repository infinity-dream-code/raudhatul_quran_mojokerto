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

                <div class="tc-builder-title">TOPUP CASH SALDO</div>

                <form method="GET" action="{{ route('smartcard.topup_cash') }}" id="tcFormSearch">
                    <input type="hidden" name="search" value="1">
                    <input type="hidden" id="custidHidden" name="custid" value="{{ (int) ($custid ?? 0) }}">

                    <div class="tc-form-grid">
                        <div class="sc-field sc-field-nis">
                            <label for="siswaSearchInput">NIS</label>
                            <div id="siswaAutoWrap" class="sc-siswa-wrap">
                                <div class="sc-control-wrap">
                                    <input type="text" id="siswaSearchInput" name="siswa_search" autocomplete="off"
                                           value="{{ $siswaLabel ?? '' }}" placeholder="Ketik NIS / nama">
                                </div>
                                <div id="siswaAutoList"></div>
                            </div>
                        </div>
                        <div class="sc-field">
                            <label for="namaSiswa">NAMA</label>
                            <div class="sc-control-wrap sc-control-readonly">
                                <input type="text" id="namaSiswa" value="{{ $nama ?? '' }}" readonly tabindex="-1">
                            </div>
                        </div>
                        <div class="sc-field">
                            <label for="metode">Metode</label>
                            <div class="sc-control-wrap sc-control-select">
                                <select id="metode" name="metode">
                                    <option value="Cash" @selected(($metode ?? 'Cash') === 'Cash')>Cash</option>
                                </select>
                            </div>
                        </div>
                        <div class="sc-field">
                            <label for="saldoDisplay">SALDO</label>
                            <div class="sc-control-wrap sc-control-readonly">
                                <input type="text" id="saldoDisplay" value="{{ number_format((int) ($saldo ?? 0), 0, ',', '.') }}" readonly tabindex="-1">
                            </div>
                        </div>
                        <div class="sc-field">
                            <label for="tanggalManual">Tanggal Manual</label>
                            <div class="sc-control-wrap">
                                <input type="date" id="tanggalManual" name="tanggal_manual"
                                       value="{{ ($tanggalManual ?? '') !== '0000-00-00' ? ($tanggalManual ?? '') : '' }}">
                            </div>
                        </div>
                        <div class="sc-field">
                            <label for="note">Note</label>
                            <div class="sc-control-wrap">
                                <input type="text" id="note" name="note" value="{{ $note ?? '' }}" placeholder="Catatan (opsional)">
                            </div>
                        </div>
                    </div>

                    <div class="tc-form-grid tc-form-grid-topup">
                        <div class="sc-field">
                            <label for="nominalTopup">TOP UP</label>
                            <div class="sc-control-wrap sc-control-kartu">
                                <input type="number" id="nominalTopup" name="nominal_preview" value="" min="1" step="1" placeholder="0" form="tcFormTopup">
                            </div>
                        </div>
                        <div class="sc-field" id="feeInfoWrap">
                            <label>Biaya Cash</label>
                            <div class="sc-control-wrap sc-control-readonly">
                                <input type="text" id="feeInfo" value="Rp {{ number_format((int) ($cashFee ?? 2000), 0, ',', '.') }}" readonly tabindex="-1">
                            </div>
                        </div>
                    </div>

                    <div class="sc-actions tc-actions-row">
                        <button type="submit" class="sc-btn sc-btn-primary">Cari</button>
                        <button type="submit" form="tcFormTopup" class="sc-btn sc-btn-success" id="btnTopup">TOPUP</button>
                        <button type="submit" form="tcFormKuitansi" class="sc-btn" id="btnKuitansi" @disabled(empty($lastTransNo))>Cetak Kuitansi</button>
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
                    <input type="hidden" name="custid" id="custidKuitansi" value="{{ (int) ($lastCustid ?? $custid ?? 0) }}">
                    <input type="hidden" name="transno" id="transnoKuitansi" value="{{ $lastTransNo ?? '' }}">
                </form>

                <div class="sc-table-section">
                    <div class="sc-table-wrap">
                        <table class="sc-table" id="tcTableSiswa">
                            <thead>
                                <tr>
                                    <th>NIS</th>
                                    <th>Nama Siswa</th>
                                    <th style="text-align:right;">SALDO</th>
                                    <th>Kelas</th>
                                    <th>Kelompok</th>
                                    <th>Jenjang</th>
                                </tr>
                            </thead>
                            <tbody>
                                @if ($isSearch ?? false)
                                    @forelse (($siswaRows ?? collect()) as $row)
                                        <tr class="tc-row-pick" tabindex="0"
                                            data-custid="{{ (int) ($row->custid ?? 0) }}"
                                            data-nis="{{ $row->nis ?? '' }}"
                                            data-nama="{{ $row->nama ?? '' }}"
                                            data-saldo="{{ (int) ($row->saldo ?? 0) }}"
                                            data-label="{{ trim(($row->nis ?? '') . ' - ' . ($row->nama ?? '')) }}">
                                            <td>{{ $row->nis ?? '—' }}</td>
                                            <td>{{ $row->nama ?? '—' }}</td>
                                            <td style="text-align:right;">{{ number_format((int) ($row->saldo ?? 0), 0, ',', '.') }}</td>
                                            <td>{{ $row->kelas ?? '—' }}</td>
                                            <td>{{ $row->kelompok ?? '—' }}</td>
                                            <td>{{ $row->jenjang ?? '—' }}</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="6" class="sc-empty">Tidak ada data siswa.</td></tr>
                                    @endforelse
                                @else
                                    <tr><td colspan="6" class="sc-empty">Klik Cari untuk menampilkan daftar siswa.</td></tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @include('smartcard.partials.styles')

    <style>
        .sc-page-wide { max-width: 1280px; }
        .tc-builder-title {
            font-family: 'Sora', sans-serif;
            font-size: 17px;
            font-weight: 800;
            color: #6d28d9;
            text-align: center;
            margin-bottom: 18px;
            letter-spacing: 0.02em;
        }
        .tc-form-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 16px 20px;
            margin-bottom: 16px;
        }
        .tc-form-grid-topup {
            grid-template-columns: repeat(2, minmax(0, 1fr));
            max-width: 520px;
        }
        @media (max-width: 992px) {
            .tc-form-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }
        @media (max-width: 640px) {
            .tc-form-grid, .tc-form-grid-topup { grid-template-columns: 1fr; max-width: none; }
        }
        .sc-control-select select {
            width: 100%;
            height: 44px;
            border: 0;
            padding: 0 14px;
            font-size: 14px;
            background: transparent;
        }
        .tc-hidden-form { display: none; }
        .tc-actions-row { margin-top: 0; }
        .tc-row-pick { cursor: pointer; }
        .tc-row-pick:hover, .tc-row-pick.tc-row-active { background: #eef2ff; }
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
            const metodeSelect = document.getElementById('metode');
            const metodeTopup = document.getElementById('metodeTopup');
            const tanggalManual = document.getElementById('tanggalManual');
            const tanggalManualTopup = document.getElementById('tanggalManualTopup');
            const noteInput = document.getElementById('note');
            const noteTopup = document.getElementById('noteTopup');
            const formTopup = document.getElementById('tcFormTopup');
            const formKuitansi = document.getElementById('tcFormKuitansi');
            let searchTimer = null;
            let searchSeq = 0;

            const formatRp = function (n) {
                return String(Math.max(0, parseInt(n, 10) || 0)).replace(/\B(?=(\d{3})+(?!\d))/g, '.');
            };

            const syncHiddenFields = function () {
                if (custidTopup && custidHidden) custidTopup.value = custidHidden.value || '';
                if (custidKuitansi && custidHidden) custidKuitansi.value = custidHidden.value || custidKuitansi.value || '';
                if (metodeTopup && metodeSelect) metodeTopup.value = metodeSelect.value || 'Cash';
                if (tanggalManualTopup && tanggalManual) tanggalManualTopup.value = tanggalManual.value || '';
                if (noteTopup && noteInput) noteTopup.value = noteInput.value || '';
            };

            const pickSiswa = function (custid, label, nama, saldo) {
                if (custidHidden) custidHidden.value = custid || '';
                if (siswaInput) siswaInput.value = label || '';
                if (namaSiswa) namaSiswa.value = nama || '';
                if (saldoDisplay) saldoDisplay.value = formatRp(saldo || 0);
                syncHiddenFields();
            };

            if (formTopup) {
                formTopup.addEventListener('submit', function (e) {
                    syncHiddenFields();
                    const cid = parseInt(custidTopup ? custidTopup.value : '0', 10);
                    const nominal = parseInt(nominalTopup ? nominalTopup.value : '0', 10);
                    if (cid <= 0) {
                        e.preventDefault();
                        alert('Pilih siswa terlebih dahulu.');
                        return;
                    }
                    if (!nominal || nominal < 1) {
                        e.preventDefault();
                        alert('Isi nominal TOP UP.');
                        return;
                    }
                    const hiddenNominal = document.createElement('input');
                    hiddenNominal.type = 'hidden';
                    hiddenNominal.name = 'nominal';
                    hiddenNominal.value = String(nominal);
                    formTopup.appendChild(hiddenNominal);

                    const metode = (metodeSelect ? metodeSelect.value : 'Cash');
                    if (metode === 'Cash') {
                        const totalBayar = nominal + cashFee;
                        if (!confirm('Top up Rp ' + formatRp(nominal) + ' + biaya cash Rp ' + formatRp(cashFee) + ' = Rp ' + formatRp(totalBayar) + ' ?')) {
                            e.preventDefault();
                        }
                    }
                });
            }

            if (formKuitansi) {
                formKuitansi.addEventListener('submit', function (e) {
                    const transno = document.getElementById('transnoKuitansi');
                    if (!transno || !transno.value) {
                        e.preventDefault();
                        alert('Lakukan TOPUP terlebih dahulu untuk mencetak kuitansi.');
                    }
                });
            }

            document.querySelectorAll('.tc-row-pick').forEach(function (row) {
                const activate = function () {
                    document.querySelectorAll('.tc-row-pick').forEach(function (r) { r.classList.remove('tc-row-active'); });
                    row.classList.add('tc-row-active');
                    pickSiswa(
                        row.getAttribute('data-custid'),
                        row.getAttribute('data-label'),
                        row.getAttribute('data-nama'),
                        row.getAttribute('data-saldo')
                    );
                };
                row.addEventListener('click', activate);
                row.addEventListener('keydown', function (ev) {
                    if (ev.key === 'Enter' || ev.key === ' ') {
                        ev.preventDefault();
                        activate();
                    }
                });
            });

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
                    const label = (r.label || '').replace(/"/g, '&quot;');
                    const nmcust = (r.nmcust || '').replace(/"/g, '&quot;');
                    return '<button type="button" data-cid="' + r.cid + '" data-label="' + label + '" data-nmcust="' + nmcust + '" style="width:100%;text-align:left;padding:10px 14px;border:0;background:#fff;cursor:pointer;border-bottom:1px solid #f3f4f6;">' + (r.label || '—') + '</button>';
                }).join('');
                siswaList.style.display = 'block';
                Array.from(siswaList.querySelectorAll('button[data-cid]')).forEach(function (btn) {
                    btn.addEventListener('mouseenter', function () { btn.style.background = '#eef2ff'; });
                    btn.addEventListener('mouseleave', function () { btn.style.background = '#fff'; });
                    btn.addEventListener('click', function () {
                        siswaInput.value = btn.getAttribute('data-label') || '';
                        custidHidden.value = btn.getAttribute('data-cid') || '';
                        if (namaSiswa) namaSiswa.value = btn.getAttribute('data-nmcust') || '';
                        syncHiddenFields();
                        closeList();
                    });
                });
            };

            const fetchSiswa = function (q) {
                const query = String(q || '').trim();
                if (query.length < 1) {
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
                syncHiddenFields();
                clearTimeout(searchTimer);
                searchTimer = setTimeout(function () { fetchSiswa(siswaInput.value); }, 280);
            });

            siswaInput.addEventListener('focus', function () {
                if (String(siswaInput.value || '').trim() !== '') {
                    fetchSiswa(siswaInput.value);
                }
            });

            document.addEventListener('click', function (e) {
                if (!siswaWrap.contains(e.target)) closeList();
            });

            if (metodeSelect) {
                metodeSelect.addEventListener('change', syncHiddenFields);
            }
            if (tanggalManual) {
                tanggalManual.addEventListener('change', syncHiddenFields);
            }
            if (noteInput) {
                noteInput.addEventListener('input', syncHiddenFields);
            }

            syncHiddenFields();
        })();
    </script>
@endsection
