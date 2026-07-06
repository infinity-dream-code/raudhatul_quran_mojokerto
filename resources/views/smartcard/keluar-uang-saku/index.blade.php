@extends('layouts.app')

@section('content')
    <div class="sc-page sc-page-wide">
        <div class="page-heading sc-page-heading">
            <h2>Keluar Uang Saku</h2>
            <p>Smartcard / Pengeluaran saldo uang saku (cash)</p>
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

                <div class="ku-builder-title">PENGELUARAN UANG SAKU</div>

                <form method="GET" action="{{ route('smartcard.keluar_uang_saku') }}" id="kuFormSearch">
                    <input type="hidden" name="search" value="1">
                    <input type="hidden" id="custidHidden" name="custid" value="{{ (int) ($custid ?? 0) }}">

                    <div class="ku-builder-panel">
                        <div class="ku-builder-scroll">
                            <div class="ku-builder-grid">
                                <div class="ku-cell">
                                    <div class="ku-cell-label">NIS</div>
                                    <div class="ku-cell-input">
                                        <input type="text" id="nisInput" name="nis" value="{{ $nisFilter ?? $nis ?? '' }}"
                                               placeholder="Nomor induk siswa" autocomplete="off">
                                    </div>
                                </div>
                                <div class="ku-cell">
                                    <div class="ku-cell-label">NAMA</div>
                                    <div class="ku-cell-input ku-cell-readonly">
                                        <input type="text" id="namaDisplay" value="{{ $nama ?? '' }}" readonly tabindex="-1" placeholder="—">
                                    </div>
                                </div>
                                <div class="ku-cell">
                                    <div class="ku-cell-label">SALDO</div>
                                    <div class="ku-cell-input ku-cell-saldo">
                                        <input type="text" id="saldoDisplay" value="{{ number_format((int) ($saldo ?? 0), 0, ',', '.') }}" readonly tabindex="-1">
                                    </div>
                                </div>
                                <div class="ku-cell ku-cell-nominal">
                                    <div class="ku-cell-label">NOMINAL</div>
                                    <div class="ku-cell-input ku-cell-highlight">
                                        <input type="text" id="nominalInput" inputmode="numeric" autocomplete="off"
                                               placeholder="0" form="kuFormStore">
                                    </div>
                                </div>
                                <div class="ku-cell ku-cell-end">
                                    <div class="ku-cell-label">Tgl Manual</div>
                                    <div class="ku-cell-input">
                                        <input type="date" id="tanggalManual" name="tanggal_manual"
                                               value="{{ ($tanggalManual ?? '') !== '0000-00-00' ? ($tanggalManual ?? '') : '' }}"
                                               form="kuFormStore">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="ku-actions">
                        <button type="submit" class="sc-btn sc-btn-primary ku-btn">
                            <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i> Cari
                        </button>
                        <button type="submit" form="kuFormStore" class="sc-btn sc-btn-success ku-btn" id="btnKeluar"
                                @disabled((int)($custid ?? 0) <= 0 || !($hasActiveCard ?? false))>
                            <i class="fa-solid fa-money-bill-transfer" aria-hidden="true"></i> Cash Keluar
                        </button>
                    </div>
                </form>

                <form method="POST" action="{{ route('smartcard.keluar_uang_saku.store') }}" id="kuFormStore" class="ku-hidden-form">
                    @csrf
                    <input type="hidden" name="custid" id="custidStore" value="{{ (int) ($custid ?? 0) }}">
                    <input type="hidden" name="nis" id="nisStore" value="{{ $nisFilter ?? $nis ?? '' }}">
                    <input type="hidden" name="tanggal_manual" id="tanggalManualStore"
                           value="{{ ($tanggalManual ?? '') !== '0000-00-00' ? ($tanggalManual ?? '') : '' }}">
                </form>

                @if ((int) ($custid ?? 0) > 0 && !empty($activeCards))
                    <div class="ku-kartu-info">
                        <i class="fa-solid fa-id-card" aria-hidden="true"></i>
                        Kartu aktif:
                        @foreach ($activeCards as $card)
                            <span class="ku-kartu-badge">{{ $card->no_kartu ?? '—' }}</span>
                        @endforeach
                    </div>
                @elseif ((int) ($custid ?? 0) > 0 && !($hasActiveCard ?? false))
                    <div class="ku-kartu-warn">
                        <i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i>
                        Tidak ada kartu aktif — pengeluaran tidak dapat diproses.
                    </div>
                @endif

                <div class="sc-table-section">
                    <div class="ku-table-head">
                        <div class="sc-table-title">Daftar Siswa</div>
                        @if ($isSearch ?? false)
                            <span class="ku-count-badge">{{ $siswaPaginator->total() ?? 0 }} siswa</span>
                        @endif
                    </div>
                    <div class="sc-table-wrap ku-table-wrap">
                        <table class="sc-table" id="kuTableSiswa">
                            <thead>
                                <tr>
                                    <th class="ku-col-expand"></th>
                                    <th>NIS</th>
                                    <th>Nama Siswa</th>
                                    <th style="text-align:right;">SALDO</th>
                                    <th>Kelas</th>
                                    <th>Kelompok</th>
                                </tr>
                            </thead>
                            <tbody>
                                @if ($isSearch ?? false)
                                    @forelse (($siswaPaginator ?? null)?->items() ?? [] as $row)
                                        @php $cid = (int) ($row->custid ?? 0); @endphp
                                        <tr class="ku-row-pick @if((int)($custid ?? 0) === $cid) ku-row-active @endif"
                                            data-custid="{{ $cid }}"
                                            data-nis="{{ $row->nis ?? '' }}"
                                            data-nama="{{ $row->nama ?? '' }}"
                                            data-saldo="{{ (int) ($row->saldo ?? 0) }}">
                                            <td class="ku-col-expand">
                                                <button type="button" class="ku-expand-btn" data-custid="{{ $cid }}" title="Lihat history" aria-expanded="false">
                                                    <i class="fa-solid fa-plus" aria-hidden="true"></i>
                                                </button>
                                            </td>
                                            <td><span class="ku-nis">{{ $row->nis ?? '—' }}</span></td>
                                            <td>{{ $row->nama ?? '—' }}</td>
                                            <td style="text-align:right;"><span class="ku-saldo-num">{{ number_format((int) ($row->saldo ?? 0), 0, ',', '.') }}</span></td>
                                            <td>{{ $row->kelas ?? '—' }}</td>
                                            <td>{{ $row->kelompok ?? '—' }}</td>
                                        </tr>
                                        <tr class="ku-detail-row" data-detail-for="{{ $cid }}" hidden>
                                            <td colspan="6">
                                                <div class="ku-detail-inner" data-history-panel="{{ $cid }}">
                                                    <div class="ku-detail-loading">Memuat history…</div>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="sc-empty">
                                                @if (($nisFilter ?? '') !== '')
                                                    Siswa tidak ditemukan untuk pencarian tersebut.
                                                @else
                                                    Tidak ada data siswa di unit ini.
                                                @endif
                                            </td>
                                        </tr>
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
                        <div class="ku-table-footer">
                            <div>Menampilkan {{ $siswaPaginator->firstItem() }}–{{ $siswaPaginator->lastItem() }} dari {{ $siswaPaginator->total() }} siswa</div>
                            @if ($siswaPaginator->hasPages())
                            <div class="ku-table-pages">
                                @if ($siswaPaginator->onFirstPage())
                                    <span class="ku-page disabled">Sebelumnya</span>
                                @else
                                    <a class="ku-page" href="{{ $siswaPaginator->previousPageUrl() }}">Sebelumnya</a>
                                @endif
                                <span class="ku-page active">{{ $siswaPaginator->currentPage() }}</span>
                                @if ($siswaPaginator->hasMorePages())
                                    <a class="ku-page" href="{{ $siswaPaginator->nextPageUrl() }}">Selanjutnya</a>
                                @else
                                    <span class="ku-page disabled">Selanjutnya</span>
                                @endif
                            </div>
                            @endif
                        </div>
                    @endif
                </div>

                @if ((int) ($custid ?? 0) > 0)
                    <div class="sc-table-section ku-history-section">
                        <div class="ku-table-head">
                            <div class="sc-table-title">History Transaksi — {{ $nama ?? '' }}</div>
                            <span class="ku-count-badge">{{ $historyPaginator->total() ?? 0 }} baris</span>
                        </div>
                        <div class="sc-table-wrap ku-table-wrap">
                            <table class="sc-table">
                                <thead>
                                    <tr>
                                        <th>NIS</th>
                                        <th>NAMA</th>
                                        <th>Tanggal</th>
                                        <th>METODE</th>
                                        <th style="text-align:right;">MASUK</th>
                                        <th style="text-align:right;">KELUAR</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse (($historyPaginator ?? null)?->items() ?? [] as $h)
                                        <tr>
                                            <td>{{ $nis ?? '—' }}</td>
                                            <td>{{ $nama ?? '—' }}</td>
                                            <td>
                                                @if (!empty($h->tgl_transaksi))
                                                    {{ \Illuminate\Support\Carbon::parse($h->tgl_transaksi)->format('Y-m-d H:i:s') }}
                                                @else — @endif
                                            </td>
                                            <td>{{ $h->metode ?? '—' }}</td>
                                            <td style="text-align:right;color:#047857;font-weight:600;">{{ number_format((int)($h->masuk ?? 0), 0, ',', '.') }}</td>
                                            <td style="text-align:right;color:#b91c1c;font-weight:600;">{{ number_format((int)($h->keluar ?? 0), 0, ',', '.') }}</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="6" class="sc-empty">Belum ada transaksi.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        @if (($historyPaginator ?? null) && $historyPaginator->hasPages())
                            <div class="ku-table-footer">
                                <div>Halaman {{ $historyPaginator->currentPage() }} / {{ $historyPaginator->lastPage() }}</div>
                                <div class="ku-table-pages">
                                    @if ($historyPaginator->onFirstPage())
                                        <span class="ku-page disabled">Sebelumnya</span>
                                    @else
                                        <a class="ku-page" href="{{ $historyPaginator->previousPageUrl() }}">Sebelumnya</a>
                                    @endif
                                    @if ($historyPaginator->hasMorePages())
                                        <a class="ku-page" href="{{ $historyPaginator->nextPageUrl() }}">Selanjutnya</a>
                                    @else
                                        <span class="ku-page disabled">Selanjutnya</span>
                                    @endif
                                </div>
                            </div>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </div>

    @include('smartcard.partials.styles')

    <style>
        .sc-page-wide { max-width: 1320px; }
        .ku-builder-title {
            font-family: 'Sora', sans-serif;
            font-size: 18px;
            font-weight: 800;
            color: #5b21b6;
            text-align: center;
            margin-bottom: 20px;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }
        .ku-builder-panel {
            border: 1px solid #c4b5fd;
            border-radius: 14px;
            overflow: hidden;
            margin-bottom: 16px;
            background: #fff;
            box-shadow: 0 4px 20px rgba(109, 40, 217, 0.08);
        }
        .ku-builder-scroll { overflow-x: auto; }
        .ku-builder-grid {
            display: grid;
            grid-template-columns: minmax(120px,1fr) minmax(150px,1.4fr) minmax(110px,1fr) minmax(120px,1fr) minmax(130px,1fr);
            min-width: 760px;
        }
        .ku-cell { display: flex; flex-direction: column; border-right: 1px solid #e9d5ff; }
        .ku-cell-end { border-right: 0; }
        .ku-cell-label {
            font-size: 12px; font-weight: 800; color: #5b21b6;
            letter-spacing: 0.06em; text-transform: uppercase;
            padding: 10px 12px;
            background: linear-gradient(180deg, #ede9fe 0%, #e9d5ff 100%);
            border-bottom: 1px solid #ddd6fe;
        }
        .ku-cell-input { min-height: 48px; display: flex; align-items: center; background: #fff; }
        .ku-cell-input input, .ku-cell-input select {
            width: 100%; height: 48px; border: 0; padding: 0 12px; font-size: 14px; background: transparent; outline: none;
        }
        .ku-cell-readonly input { background: #faf5ff; color: #4b5563; }
        .ku-cell-saldo input { background: #f0fdf4; font-weight: 700; color: #047857; text-align: right; }
        .ku-cell-highlight input { background: #fffbeb; font-weight: 700; color: #92400e; text-align: right; }
        .ku-actions { display: flex; flex-wrap: wrap; gap: 10px; justify-content: center; margin-bottom: 16px; }
        .ku-btn { min-width: 140px; display: inline-flex; align-items: center; justify-content: center; gap: 6px; }
        .ku-hidden-form { display: none; }
        .ku-kartu-info, .ku-kartu-warn {
            font-size: 13px; padding: 10px 14px; border-radius: 10px; margin-bottom: 16px;
            display: flex; flex-wrap: wrap; align-items: center; gap: 8px;
        }
        .ku-kartu-info { background: #ecfdf5; border: 1px solid #a7f3d0; color: #047857; }
        .ku-kartu-warn { background: #fef2f2; border: 1px solid #fecaca; color: #b91c1c; }
        .ku-kartu-badge {
            background: #fff; border: 1px solid #6ee7b7; border-radius: 6px;
            padding: 2px 8px; font-family: ui-monospace, monospace; font-size: 12px;
        }
        .ku-table-head { display: flex; align-items: center; justify-content: space-between; margin-bottom: 10px; flex-wrap: wrap; gap: 8px; }
        .ku-count-badge {
            font-size: 12px; font-weight: 700; color: #5b21b6;
            background: #ede9fe; border: 1px solid #c4b5fd; border-radius: 999px; padding: 4px 12px;
        }
        .ku-table-wrap { border: 1px solid #e9d5ff; border-radius: 12px; overflow: hidden; }
        .ku-col-expand { width: 44px; text-align: center; }
        .ku-expand-btn {
            width: 28px; height: 28px; border: 1px solid #c4b5fd; border-radius: 6px;
            background: #fff; color: #5b21b6; cursor: pointer; display: inline-flex;
            align-items: center; justify-content: center; font-size: 12px;
        }
        .ku-expand-btn:hover { background: #f5f3ff; }
        .ku-expand-btn[aria-expanded="true"] .fa-plus::before { content: "\f068"; }
        .ku-row-pick { cursor: pointer; }
        .ku-row-pick:hover td { background: #faf5ff; }
        .ku-row-active td { background: #ede9fe !important; }
        .ku-nis { font-family: ui-monospace, monospace; color: #5b21b6; font-weight: 600; }
        .ku-saldo-num { font-weight: 700; color: #047857; }
        .ku-detail-inner { padding: 12px 16px; background: #faf5ff; }
        .ku-detail-loading { color: #6b7280; font-size: 13px; }
        .ku-mini-table { width: 100%; border-collapse: collapse; font-size: 13px; }
        .ku-mini-table th, .ku-mini-table td { border: 1px solid #e9d5ff; padding: 6px 8px; }
        .ku-mini-table th { background: #ede9fe; color: #5b21b6; font-weight: 700; }
        .ku-history-section { margin-top: 24px; }
        .ku-table-footer {
            padding: 12px 4px 0; display: flex; justify-content: space-between; align-items: center;
            flex-wrap: wrap; gap: 8px; font-size: 12px; color: #6b7280;
        }
        .ku-table-pages { display: flex; gap: 6px; align-items: center; }
        .ku-page {
            min-width: 30px; height: 30px; border: 1px solid #c4b5fd; border-radius: 999px;
            padding: 0 10px; display: inline-flex; align-items: center; justify-content: center;
            text-decoration: none; color: #5b21b6; font-weight: 700; background: #fff;
        }
        .ku-page.active { background: #7c3aed; color: #fff; border-color: #7c3aed; }
        .ku-page.disabled { pointer-events: none; opacity: 0.45; }
    </style>

    <script>
        (function () {
            const historyUrl = @json(route('smartcard.keluar_uang_saku.history'));
            const custidHidden = document.getElementById('custidHidden');
            const custidStore = document.getElementById('custidStore');
            const nisInput = document.getElementById('nisInput');
            const nisStore = document.getElementById('nisStore');
            const namaDisplay = document.getElementById('namaDisplay');
            const saldoDisplay = document.getElementById('saldoDisplay');
            const nominalInput = document.getElementById('nominalInput');
            const tanggalManual = document.getElementById('tanggalManual');
            const tanggalManualStore = document.getElementById('tanggalManualStore');
            const btnKeluar = document.getElementById('btnKeluar');
            const formStore = document.getElementById('kuFormStore');

            const parseNum = function (v) {
                return parseInt(String(v || '').replace(/\D/g, ''), 10) || 0;
            };
            const formatRp = function (n) {
                return String(Math.max(0, parseInt(n, 10) || 0)).replace(/\B(?=(\d{3})+(?!\d))/g, '.');
            };

            const pickSiswaNavigate = function (custid) {
                const url = new URL(window.location.pathname, window.location.origin);
                url.searchParams.set('search', '1');
                url.searchParams.set('custid', String(custid || ''));
                url.searchParams.set('nis', nisInput ? nisInput.value.trim() : '');
                if (tanggalManual && tanggalManual.value) {
                    url.searchParams.set('tanggal_manual', tanggalManual.value);
                }
                window.location.href = url.toString();
            };

            document.querySelectorAll('.ku-row-pick').forEach(function (row) {
                row.addEventListener('click', function (e) {
                    if (e.target.closest('.ku-expand-btn')) return;
                    pickSiswaNavigate(row.getAttribute('data-custid'));
                });
            });

            document.querySelectorAll('.ku-expand-btn').forEach(function (btn) {
                btn.addEventListener('click', function (e) {
                    e.stopPropagation();
                    const cid = btn.getAttribute('data-custid');
                    const detailRow = document.querySelector('.ku-detail-row[data-detail-for="' + cid + '"]');
                    const panel = document.querySelector('[data-history-panel="' + cid + '"]');
                    if (!detailRow || !panel) return;

                    const isOpen = !detailRow.hidden;
                    document.querySelectorAll('.ku-detail-row').forEach(function (r) { r.hidden = true; });
                    document.querySelectorAll('.ku-expand-btn').forEach(function (b) { b.setAttribute('aria-expanded', 'false'); });

                    if (isOpen) return;

                    detailRow.hidden = false;
                    btn.setAttribute('aria-expanded', 'true');
                    panel.innerHTML = '<div class="ku-detail-loading">Memuat history…</div>';

                    fetch(historyUrl + '?custid=' + encodeURIComponent(cid) + '&page=1', {
                        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                        credentials: 'same-origin',
                    })
                        .then(function (res) { return res.json(); })
                        .then(function (json) {
                            if (!json.ok || !json.rows || !json.rows.length) {
                                panel.innerHTML = '<div class="ku-detail-loading">Belum ada transaksi.</div>';
                                return;
                            }
                            let html = '<table class="ku-mini-table"><thead><tr><th>Tanggal</th><th>Metode</th><th style="text-align:right">Masuk</th><th style="text-align:right">Keluar</th></tr></thead><tbody>';
                            json.rows.forEach(function (r) {
                                html += '<tr><td>' + r.tgl + '</td><td>' + r.metode + '</td>';
                                html += '<td style="text-align:right;color:#047857">' + formatRp(r.masuk) + '</td>';
                                html += '<td style="text-align:right;color:#b91c1c">' + formatRp(r.keluar) + '</td></tr>';
                            });
                            html += '</tbody></table>';
                            panel.innerHTML = html;
                        })
                        .catch(function () {
                            panel.innerHTML = '<div class="ku-detail-loading" style="color:#b91c1c">Gagal memuat history.</div>';
                        });
                });
            });

            if (nominalInput) {
                nominalInput.addEventListener('input', function () {
                    const raw = parseNum(nominalInput.value);
                    nominalInput.value = raw > 0 ? formatRp(raw) : '';
                });
            }

            if (formStore) {
                formStore.addEventListener('submit', function (e) {
                    const cid = parseInt(custidStore ? custidStore.value : '0', 10);
                    const nominal = parseNum(nominalInput ? nominalInput.value : 0);
                    const saldo = parseNum(saldoDisplay ? saldoDisplay.value : 0);

                    if (tanggalManualStore && tanggalManual) {
                        tanggalManualStore.value = tanggalManual.value || '';
                    }

                    if (cid <= 0) {
                        e.preventDefault();
                        alert('Pilih siswa terlebih dahulu.');
                        return;
                    }
                    if (!nominal || nominal < 1) {
                        e.preventDefault();
                        alert('Isi nominal pengeluaran.');
                        nominalInput?.focus();
                        return;
                    }
                    if (nominal > saldo) {
                        e.preventDefault();
                        alert('Saldo tidak mencukupi. Saldo: Rp ' + formatRp(saldo));
                        return;
                    }

                    const hiddenNominal = document.createElement('input');
                    hiddenNominal.type = 'hidden';
                    hiddenNominal.name = 'nominal';
                    hiddenNominal.value = String(nominal);
                    formStore.appendChild(hiddenNominal);

                    if (!confirm('Keluarkan uang saku Rp ' + formatRp(nominal) + ' ?\nSaldo setelah: Rp ' + formatRp(saldo - nominal))) {
                        e.preventDefault();
                    }
                });
            }

            if (tanggalManual && tanggalManualStore) {
                tanggalManual.addEventListener('change', function () {
                    tanggalManualStore.value = tanggalManual.value || '';
                });
            }
        })();
    </script>
@endsection
