@extends('layouts.app')

@section('content')
    <div class="sc-page">
        <div class="page-heading sc-page-heading">
            <h2>Setting Blokir Kartu</h2>
            <p>Smartcard / Blokir dan buka blokir kartu siswa</p>
        </div>

        <div class="card sc-card">
            <div class="sc-card-body">
                @if (session('smartcard_success'))
                    <div class="sc-alert sc-alert-success">{{ session('smartcard_success') }}</div>
                @endif
                @if (session('smartcard_error'))
                    <div class="sc-alert sc-alert-error">{{ session('smartcard_error') }}</div>
                @endif
                @if (($searchError ?? '') !== '')
                    <div class="sc-alert sc-alert-error">{{ $searchError }}</div>
                @endif

                <form id="formSearch" method="GET" action="{{ route('smartcard.blokir_kartu') }}">
                    <input type="hidden" name="search" value="1">
                    <input type="hidden" id="custidHidden" name="custid" value="{{ (int) ($custid ?? 0) }}">

                    <div class="sc-form-grid sc-form-grid-2">
                        <div class="sc-field sc-field-nis">
                            <label for="siswaSearchInput">No Induk Siswa</label>
                            <div id="siswaAutoWrap" class="sc-siswa-wrap">
                                <div class="sc-control-wrap">
                                    <input type="text" id="siswaSearchInput" name="siswa_search" autocomplete="off"
                                           value="{{ $siswaLabel ?? '' }}" placeholder="Ketik NIS / nama, pilih dari dropdown">
                                </div>
                                <div id="siswaAutoList"></div>
                            </div>
                        </div>
                        <div class="sc-field">
                            <label for="namaSiswa">Nama</label>
                            <div class="sc-control-wrap sc-control-readonly">
                                <input type="text" id="namaSiswa" value="{{ $nama ?? '' }}" readonly tabindex="-1" placeholder="Otomatis dari NIS">
                            </div>
                        </div>
                    </div>

                    <div class="sc-actions">
                        <button type="submit" class="sc-btn sc-btn-primary">Lihat</button>
                    </div>
                </form>

                <div class="sc-table-section">
                    <div class="sc-table-title">Daftar Kartu</div>
                    <div class="sc-table-wrap">
                        <table class="sc-table">
                            <thead>
                                <tr>
                                    <th style="width:64px;">No</th>
                                    <th>No Kartu</th>
                                    <th>PIN</th>
                                    <th style="width:140px;">Status</th>
                                    <th style="width:180px;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse (($kartuRows ?? collect()) as $index => $row)
                                    @php $isBlocked = (int) ($row->blokir ?? 0) === 1; @endphp
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td>{{ $row->no_kartu ?? '—' }}</td>
                                        <td>{{ $row->pin ?? '—' }}</td>
                                        <td>
                                            @if ($isBlocked)
                                                <span class="sc-badge sc-badge-danger">Diblokir</span>
                                            @else
                                                <span class="sc-badge sc-badge-success">Aktif</span>
                                            @endif
                                        </td>
                                        <td>
                                            <form method="POST" action="{{ route('smartcard.blokir_kartu.update') }}" class="sc-inline-form">
                                                @csrf
                                                <input type="hidden" name="pid" value="{{ $row->no_kartu }}">
                                                <input type="hidden" name="custid" value="{{ (int) ($custid ?? 0) }}">
                                                <input type="hidden" name="siswa_search" value="{{ $siswaLabel ?? '' }}">
                                                @if ($isBlocked)
                                                    <input type="hidden" name="blokir" value="0">
                                                    <button type="submit" class="sc-btn sc-btn-sm sc-btn-success">Buka Blokir</button>
                                                @else
                                                    <input type="hidden" name="blokir" value="1">
                                                    <button type="submit" class="sc-btn sc-btn-sm sc-btn-danger" onclick="return confirm('Blokir kartu ini?')">Blokir</button>
                                                @endif
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="sc-empty">
                                            @if ($isSearch ?? false)
                                                @if ((int) ($custid ?? 0) > 0)
                                                    Siswa ini belum memiliki kartu.
                                                @else
                                                    Pilih siswa dari dropdown lalu klik <b>Lihat</b>.
                                                @endif
                                            @else
                                                Pilih siswa (NIS) lalu klik <b>Lihat</b> untuk menampilkan kartu.
                                            @endif
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @include('smartcard.partials.styles')

    <script>
        (function () {
            const siswaSearchUrl = @json(route('keu.manual.siswa_search'));
            const siswaInput = document.getElementById('siswaSearchInput');
            const custidHidden = document.getElementById('custidHidden');
            const namaSiswa = document.getElementById('namaSiswa');
            const siswaList = document.getElementById('siswaAutoList');
            const siswaWrap = document.getElementById('siswaAutoWrap');
            const nisField = document.querySelector('.sc-field-nis');
            const searchForm = document.getElementById('formSearch');
            let searchTimer = null;
            let searchSeq = 0;

            if (searchForm) {
                searchForm.addEventListener('submit', function (e) {
                    if (!custidHidden || parseInt(custidHidden.value, 10) <= 0) {
                        e.preventDefault();
                        alert('Pilih siswa (NIS) terlebih dahulu dari dropdown.');
                    }
                });
            }

            if (!siswaInput || !custidHidden || !siswaList || !siswaWrap) {
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
        })();
    </script>
@endsection
