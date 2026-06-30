@extends('layouts.app')

@section('content')
    <div class="page-heading">
        <h2>Data Kartu Siswa</h2>
        <p>Smartcard / Setting daftar kartu siswa</p>
    </div>

    <div class="card">
        <div class="card-body-pad">
            @if (session('smartcard_success'))
                <div style="margin-bottom:12px;color:#047857;font-weight:600;">{{ session('smartcard_success') }}</div>
            @endif
            @if (session('smartcard_error'))
                <div style="margin-bottom:12px;color:#b91c1c;font-weight:600;">{{ session('smartcard_error') }}</div>
            @endif

            <style>
                .sc-form-grid {
                    display: grid;
                    grid-template-columns: repeat(2, minmax(240px, 1fr));
                    gap: 16px 20px;
                    margin-bottom: 16px;
                }
                @media (max-width: 768px) {
                    .sc-form-grid { grid-template-columns: 1fr; }
                }
                .sc-field label {
                    display: block;
                    font-size: 13px;
                    font-weight: 700;
                    color: #5b21b6;
                    background: #ede9fe;
                    padding: 8px 12px;
                    border-radius: 8px 8px 0 0;
                    margin: 0;
                }
                .sc-field .sc-control-wrap {
                    border: 1px solid #d8b4fe;
                    border-top: 0;
                    border-radius: 0 0 8px 8px;
                    background: #fff;
                }
                .sc-field input[type="text"],
                .sc-field input[readonly] {
                    width: 100%;
                    height: 42px;
                    border: 0;
                    padding: 0 12px;
                    font-size: 14px;
                    background: transparent;
                }
                .sc-field input#namaSiswa,
                .sc-field input#pinInput {
                    background: #eff6ff;
                }
                .sc-field input#noKartuInput {
                    background: #fef9c3;
                }
                #siswaAutoWrap { position: relative; }
                #siswaAutoList {
                    display: none;
                    position: absolute;
                    left: 0;
                    right: 0;
                    top: calc(100% + 4px);
                    z-index: 50;
                    background: #fff;
                    border: 1px solid #d1d5db;
                    border-radius: 10px;
                    max-height: 220px;
                    overflow: auto;
                    box-shadow: 0 8px 24px rgba(0,0,0,.12);
                }
                .sc-actions {
                    display: flex;
                    gap: 10px;
                    flex-wrap: wrap;
                    margin-bottom: 18px;
                }
                .sc-btn {
                    min-width: 110px;
                    height: 40px;
                    padding: 0 18px;
                    border-radius: 8px;
                    font-weight: 700;
                    font-size: 13px;
                    cursor: pointer;
                    border: 1px solid #d1d5db;
                    background: #fff;
                    color: #374151;
                }
                .sc-btn-primary {
                    background: #2563eb;
                    border-color: #2563eb;
                    color: #fff;
                }
                .sc-table-wrap {
                    overflow: auto;
                    border: 1px solid #e5e7eb;
                    border-radius: 10px;
                    max-height: 420px;
                }
                .sc-table {
                    width: 100%;
                    min-width: 480px;
                    border-collapse: collapse;
                    font-size: 13px;
                }
                .sc-table thead th {
                    background: #7c3aed;
                    color: #fff;
                    font-weight: 700;
                    padding: 10px 12px;
                    text-align: left;
                    position: sticky;
                    top: 0;
                    z-index: 1;
                }
                .sc-table tbody td {
                    padding: 10px 12px;
                    border-bottom: 1px solid #eef2f7;
                }
                .sc-table tbody tr:nth-child(even) { background: #eff6ff; }
                .sc-table tbody tr:nth-child(odd) { background: #fff; }
                .sc-empty {
                    text-align: center;
                    color: #6b7280;
                    padding: 24px 12px !important;
                }
            </style>

            <form id="formSearch" method="GET" action="{{ route('smartcard.data_kartu') }}">
                <input type="hidden" name="search" value="1">
                <input type="hidden" id="custidHidden" name="custid" value="{{ (int) ($custid ?? 0) }}">

                <div class="sc-form-grid">
                    <div class="sc-field">
                        <label for="siswaSearchInput">No Induk Siswa</label>
                        <div class="sc-control-wrap">
                            <div id="siswaAutoWrap">
                                <input type="text" id="siswaSearchInput" name="siswa_search" autocomplete="off"
                                       value="{{ $siswaLabel ?? '' }}" placeholder="Ketik NIS / nama, pilih dari dropdown">
                                <div id="siswaAutoList"></div>
                            </div>
                        </div>
                    </div>
                    <div class="sc-field">
                        <label for="noKartuInput">No Kartu</label>
                        <div class="sc-control-wrap">
                            <input type="text" id="noKartuInput" name="no_kartu" value="{{ $noKartu ?? '' }}" placeholder="Isi manual">
                        </div>
                    </div>
                    <div class="sc-field">
                        <label for="namaSiswa">Nama</label>
                        <div class="sc-control-wrap">
                            <input type="text" id="namaSiswa" value="{{ $nama ?? '' }}" readonly tabindex="-1" placeholder="Otomatis dari NIS">
                        </div>
                    </div>
                    <div class="sc-field">
                        <label for="pinInput">PIN</label>
                        <div class="sc-control-wrap">
                            <input type="text" id="pinInput" name="pin" value="{{ $pin ?? '123' }}" placeholder="123">
                        </div>
                    </div>
                </div>

                <div class="sc-actions">
                    <button type="submit" class="sc-btn">Lihat</button>
                </div>
            </form>

            <form id="formSave" method="POST" action="{{ route('smartcard.data_kartu.store') }}">
                @csrf
                <input type="hidden" name="custid" id="custidSave" value="{{ (int) ($custid ?? 0) }}">
                <input type="hidden" name="no_kartu" id="noKartuSave" value="{{ $noKartu ?? '' }}">
                <input type="hidden" name="pin" id="pinSave" value="{{ $pin ?? '123' }}">
                <div class="sc-actions">
                    <button type="submit" class="sc-btn sc-btn-primary">Simpan</button>
                </div>
            </form>

            <div class="sc-table-wrap">
                <table class="sc-table">
                    <thead>
                        <tr>
                            <th style="width:56px;">No</th>
                            <th>NIS</th>
                            <th>Nama</th>
                            <th>No Kartu</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse (($rows ?? collect()) as $i => $row)
                            <tr>
                                <td>{{ $i + 1 }}</td>
                                <td>{{ $row->nis ?? '—' }}</td>
                                <td>{{ $row->nama ?? '—' }}</td>
                                <td>{{ $row->no_kartu ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="sc-empty">Data kartu tidak ditemukan.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        (function () {
            const siswaSearchUrl = @json(route('keu.manual.siswa_search'));
            const siswaInput = document.getElementById('siswaSearchInput');
            const custidHidden = document.getElementById('custidHidden');
            const custidSave = document.getElementById('custidSave');
            const namaSiswa = document.getElementById('namaSiswa');
            const noKartuInput = document.getElementById('noKartuInput');
            const noKartuSave = document.getElementById('noKartuSave');
            const pinInput = document.getElementById('pinInput');
            const pinSave = document.getElementById('pinSave');
            const siswaList = document.getElementById('siswaAutoList');
            let searchTimer = null;
            let searchSeq = 0;

            const syncSaveFields = function () {
                if (custidSave) custidSave.value = custidHidden ? custidHidden.value : '';
                if (noKartuSave && noKartuInput) noKartuSave.value = noKartuInput.value;
                if (pinSave && pinInput) pinSave.value = pinInput.value || '123';
            };

            if (noKartuInput) {
                noKartuInput.addEventListener('input', syncSaveFields);
            }
            if (pinInput) {
                pinInput.addEventListener('input', syncSaveFields);
            }

            document.getElementById('formSave')?.addEventListener('submit', function (e) {
                syncSaveFields();
                if (!custidSave || parseInt(custidSave.value, 10) <= 0) {
                    e.preventDefault();
                    alert('Pilih siswa (NIS) terlebih dahulu dari dropdown.');
                    return;
                }
                if (!noKartuSave || noKartuSave.value.trim() === '') {
                    e.preventDefault();
                    alert('Nomor kartu wajib diisi.');
                }
            });

            if (!siswaInput || !custidHidden || !siswaList) {
                return;
            }

            const closeList = function () {
                siswaList.style.display = 'none';
                siswaList.innerHTML = '';
            };

            const renderRows = function (matched) {
                if (!matched.length) {
                    siswaList.innerHTML = '<div style="padding:10px 12px;color:#6b7280;font-size:13px;">Siswa tidak ditemukan.</div>';
                    siswaList.style.display = 'block';
                    return;
                }
                siswaList.innerHTML = matched.map(function (r) {
                    const label = (r.label || '').replace(/"/g, '&quot;');
                    const nmcust = (r.nmcust || '').replace(/"/g, '&quot;');
                    return '<button type="button" data-cid="' + r.cid + '" data-label="' + label + '" data-nmcust="' + nmcust + '" style="width:100%;text-align:left;padding:8px 10px;border:0;background:#fff;cursor:pointer;border-bottom:1px solid #f3f4f6;">' + (r.label || '—') + '</button>';
                }).join('');
                siswaList.style.display = 'block';
                Array.from(siswaList.querySelectorAll('button[data-cid]')).forEach(function (btn) {
                    btn.addEventListener('mouseenter', function () { btn.style.background = '#eef2ff'; });
                    btn.addEventListener('mouseleave', function () { btn.style.background = '#fff'; });
                    btn.addEventListener('click', function () {
                        siswaInput.value = btn.getAttribute('data-label') || '';
                        custidHidden.value = btn.getAttribute('data-cid') || '';
                        if (namaSiswa) {
                            namaSiswa.value = btn.getAttribute('data-nmcust') || '';
                        }
                        syncSaveFields();
                        closeList();
                    });
                });
            };

            siswaInput.addEventListener('input', function () {
                const q = siswaInput.value.trim();
                if (q === '') {
                    custidHidden.value = '';
                    if (namaSiswa) namaSiswa.value = '';
                    syncSaveFields();
                    closeList();
                    return;
                }
                clearTimeout(searchTimer);
                const seq = ++searchSeq;
                searchTimer = setTimeout(function () {
                    fetch(siswaSearchUrl + '?q=' + encodeURIComponent(q) + '&mode=nis', {
                        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                    })
                        .then(function (res) { return res.json(); })
                        .then(function (data) {
                            if (seq !== searchSeq) return;
                            renderRows(Array.isArray(data.rows) ? data.rows : []);
                        })
                        .catch(function () {
                            if (seq !== searchSeq) return;
                            closeList();
                        });
                }, 280);
            });

            document.addEventListener('click', function (e) {
                if (!document.getElementById('siswaAutoWrap')?.contains(e.target)) {
                    closeList();
                }
            });

            syncSaveFields();
        })();
    </script>
@endsection
