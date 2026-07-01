@extends('layouts.app')

@section('content')
    @include('partials.table-sort-vars')
    <style>
        .ds-wrap { margin-top: 16px; display: flex; flex-direction: column; gap: 16px; }
        .ds-card {
            background: #fff;
            border: 1px solid #e4eaf0;
            border-radius: 14px;
            box-shadow: 0 6px 18px rgba(15, 23, 42, 0.06);
        }
        .ds-card-h {
            padding: 14px 18px;
            border-bottom: 1px solid #eef2f7;
            font-weight: 700;
            font-size: 14px;
            font-family: 'Sora', sans-serif;
        }
        .ds-filter-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 14px;
            padding: 16px 18px;
            align-items: end;
        }
        .ds-fld label {
            display: block;
            font-size: 12px;
            font-weight: 600;
            color: #4b5563;
            margin-bottom: 6px;
        }
        .ds-fld select, .ds-fld input[type="text"] {
            width: 100%;
            height: 38px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            padding: 0 10px;
            font-size: 13px;
        }
        .ds-filter-actions { display: flex; gap: 10px; flex-wrap: wrap; padding: 0 18px 16px; }
        .ds-btn {
            height: 38px;
            padding: 0 16px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            border: 1px solid #d1d5db;
            background: #fff;
            color: #374151;
        }
        .ds-btn-primary { background: #4f6ef7; color: #fff; border-color: #4f6ef7; }
        .ds-toolbar {
            padding: 12px 18px;
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            border-bottom: 1px solid #eef2f7;
        }
        .ds-search {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            color: #6b7280;
        }
        .ds-search input {
            width: 220px;
            height: 34px;
            border: 1px solid #e5e7eb;
            border-radius: 7px;
            padding: 0 10px;
            font-size: 12px;
        }
        .ds-table-wrap { overflow-x: auto; }
        .ds-table { width: 100%; border-collapse: collapse; min-width: 1400px; font-size: 13px; }
        .ds-table th, .ds-table td {
            border-bottom: 1px solid #eef2f7;
            padding: 10px 10px;
            text-align: left;
            vertical-align: middle;
        }
        .ds-table th { background: #fafbfd; color: #4b5563; font-weight: 700; white-space: nowrap; }
        .ds-th-sort a {
            color: inherit;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            cursor: pointer;
            white-space: nowrap;
        }
        .ds-th-sort a:hover { color: #4f46e5; }
        .ds-th-sort.is-active a { color: #4f46e5; }
        .ds-th-sort i { font-size: 11px; opacity: 0.75; }
        .ds-th-sort.is-active i { opacity: 1; }
        .ds-col-no { width: 48px; text-align: center; }
        .ds-center { text-align: center; }
        .ds-col-act { width: 100px; text-align: center; }
        .ds-pill {
            display: inline-block;
            border-radius: 999px;
            padding: 2px 8px;
            font-size: 11px;
            font-weight: 700;
        }
        .ds-pill-ok { background: #dcfce7; color: #15803d; }
        .ds-pill-no { background: #fee2e2; color: #b91c1c; }
        .ds-btn-reset-login {
            font-size: 11px;
            padding: 6px 10px;
            border-radius: 6px;
            border: 1px solid #d1d5db;
            background: #f9fafb;
            color: #4b5563;
            cursor: pointer;
        }
        .ds-btn-reset-login:disabled { opacity: 0.55; cursor: not-allowed; }
        .ds-btn-reset-login:not(:disabled):hover { background: #ecfdf5; border-color: #10b981; color: #047857; }
        .ds-btn-bulk-reset {
            height: 34px;
            padding: 0 12px;
            border-radius: 8px;
            background: #22c55e;
            color: #fff;
            border: 1px solid #16a34a;
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
        }
        .ds-btn-bulk-reset:disabled { opacity: 0.5; cursor: not-allowed; }
        .ds-col-chk { width: 36px; text-align: center; }
        .ds-modal {
            position: fixed;
            inset: 0;
            background: rgba(17, 24, 39, 0.4);
            display: none;
            align-items: center;
            justify-content: center;
            padding: 14px;
            z-index: 1200;
        }
        .ds-modal.open { display: flex; }
        .ds-modal-box {
            width: 100%;
            max-width: 460px;
            background: #fff;
            border-radius: 12px;
            border: 1px solid #e5e7eb;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.18);
        }
        .ds-modal-h {
            padding: 14px 18px 8px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .ds-modal-h h3 { margin: 0; font-size: 18px; font-weight: 700; color: #111827; }
        .ds-modal-close {
            border: none;
            background: transparent;
            font-size: 22px;
            line-height: 1;
            color: #6b7280;
            cursor: pointer;
        }
        .ds-modal-b { padding: 0 18px 14px; }
        .ds-modal-fields { display: grid; gap: 8px; margin-top: 8px; }
        .ds-modal-row { display: grid; grid-template-columns: 110px 1fr; gap: 8px; font-size: 13px; align-items: center; }
        .ds-modal-row label { color: #6b7280; font-weight: 600; }
        .ds-modal-row input {
            width: 100%;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            padding: 7px 10px;
            background: #f9fafb;
            color: #111827;
        }
        .ds-modal-f {
            padding: 12px 18px 18px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }
        .ds-modal-f button {
            height: 38px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
        }
        .ds-modal-cancel { border: 1px solid #d1d5db; background: #fff; color: #374151; }
        .ds-modal-submit { border: 1px solid #16a34a; background: #22c55e; color: #fff; }
        .ds-modal-submit:disabled { opacity: 0.6; cursor: wait; }
        .ds-alert-error {
            margin: 0 18px 12px;
            padding: 10px 12px;
            border-radius: 8px;
            background: #fef2f2;
            color: #b91c1c;
            font-size: 13px;
            font-weight: 600;
        }
        .ds-empty { text-align: center; color: #6b7280; padding: 24px; }
        .ds-alert {
            margin: 0 18px 12px;
            padding: 10px 12px;
            border-radius: 8px;
            background: #ecfdf5;
            color: #047857;
            font-size: 13px;
            font-weight: 600;
        }
        .ds-pagination-wrap {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            padding: 14px 18px 18px;
            flex-wrap: wrap;
        }
        .ds-pagination-info { font-size: 12px; color: #6b7280; }
        .ds-pagination { display: flex; align-items: center; gap: 6px; flex-wrap: wrap; }
        .ds-page-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 30px;
            height: 30px;
            padding: 0 10px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            text-decoration: none;
            color: #374151;
            font-size: 12px;
            font-weight: 600;
            background: #fff;
        }
        .ds-page-link.active { background: #4f6ef7; color: #fff; border-color: #4f6ef7; }
        .ds-page-link.disabled { color: #9ca3af; border-color: #e5e7eb; pointer-events: none; background: #f9fafb; }
        .ds-export {
            position: relative;
        }
        .ds-export-btn {
            height: 34px;
            padding: 0 12px;
            border-radius: 8px;
            background: #0ea5e9;
            color: #fff;
            border: 1px solid #0ea5e9;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
        }
        .ds-export-menu {
            position: absolute;
            right: 0;
            top: 40px;
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            box-shadow: 0 12px 24px rgba(0, 0, 0, 0.12);
            min-width: 130px;
            z-index: 20;
            display: none;
            padding: 6px;
        }
        .ds-export.open .ds-export-menu { display: block; }
        .ds-export-item {
            width: 100%;
            display: block;
            text-align: left;
            text-decoration: none;
            border: 0;
            background: transparent;
            color: #374151;
            padding: 8px 10px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
        }
        .ds-export-item:hover { background: #f3f4f6; }
        .ds-copy-toast {
            position: fixed;
            bottom: 18px;
            right: 18px;
            background: #111827;
            color: #fff;
            font-size: 12px;
            padding: 8px 12px;
            border-radius: 8px;
            opacity: 0;
            transform: translateY(8px);
            transition: all 0.2s ease;
            pointer-events: none;
            z-index: 40;
        }
        .ds-copy-toast.show {
            opacity: 1;
            transform: translateY(0);
        }
    </style>

    <div class="page-heading">
        <h2>Data Siswa</h2>
        <p>Daftar siswa dari web service — filter, cari, dan pagination.</p>
    </div>

    <div class="ds-wrap">
        <div class="ds-card">
            <div class="ds-card-h">Filter</div>
            <form method="GET" action="{{ route('master.data_siswa') }}">
                <input type="hidden" name="sort_by" value="{{ $sortBy }}">
                <input type="hidden" name="sort_dir" value="{{ $sortDir }}">
                @if (($keyword ?? '') !== '')
                    <input type="hidden" name="q" value="{{ $keyword }}">
                @endif
                @if (($perPage ?? 10) !== 10)
                    <input type="hidden" name="per_page" value="{{ (int) ($perPage ?? 10) }}">
                @endif
                <div class="ds-filter-grid">
                    <div class="ds-fld">
                        <label for="angkatan">Angkatan Siswa</label>
                        <select id="angkatan" name="angkatan">
                            <option value="">Semua</option>
                            @foreach (($filterOptions['angkatan'] ?? []) as $opt)
                                <option value="{{ $opt }}" {{ ($angkatan ?? '') === $opt ? 'selected' : '' }}>{{ $opt }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="ds-fld">
                        <label for="sekolah">Sekolah</label>
                        <select id="sekolah" name="sekolah">
                            <option value="">Semua</option>
                            @foreach (($filterOptions['sekolah'] ?? []) as $opt)
                                @php
                                    if (is_array($opt)) {
                                        $sekVal = (string) ($opt['code01'] ?? $opt['CODE01'] ?? '');
                                        $sekLab = (string) ($opt['label'] ?? $opt['LABEL'] ?? $sekVal);
                                    } else {
                                        $sekVal = (string) $opt;
                                        $sekLab = $sekVal;
                                    }
                                @endphp
                                @if ($sekVal !== '')
                                    <option value="{{ $sekVal }}" {{ ($sekolah ?? '') === $sekVal ? 'selected' : '' }}>{{ $sekLab }}</option>
                                @endif
                            @endforeach
                        </select>
                    </div>
                    <div class="ds-fld">
                        <label for="kelas">Kelas</label>
                        <select id="kelas" name="kelas">
                            <option value="">Semua</option>
                            @foreach (($filterOptions['kelas'] ?? []) as $kr)
                                @php
                                    $d02 = $kr['DESC02'] ?? $kr['desc02'] ?? '';
                                    $c02 = $kr['CODE02'] ?? $kr['code02'] ?? '';
                                @endphp
                                @if ($d02 !== '')
                                    <option value="{{ $d02 }}" {{ ($kelas ?? '') === $d02 ? 'selected' : '' }}>
                                        {{ $c02 !== '' ? $c02.' — ' : '' }}{{ $d02 }}
                                    </option>
                                @endif
                            @endforeach
                        </select>
                    </div>
                    <div class="ds-fld">
                        <label for="kelompok">Kelompok</label>
                        <select id="kelompok" name="kelompok">
                            <option value="">Semua</option>
                            @foreach (($filterOptions['kelompok'] ?? []) as $kl)
                                @php $klVal = is_array($kl) ? trim((string) ($kl['desc03'] ?? $kl['DESC03'] ?? '')) : trim((string) $kl); @endphp
                                @if ($klVal !== '')
                                    <option value="{{ $klVal }}" {{ ($kelompok ?? '') === $klVal ? 'selected' : '' }}>{{ $klVal }}</option>
                                @endif
                            @endforeach
                        </select>
                    </div>
                    <div class="ds-fld">
                        <label for="siswa">Siswa (NIS / Nama)</label>
                        <input id="siswa" type="text" name="siswa" value="{{ $siswa ?? '' }}" placeholder="Masukkan NIS/NAMA Siswa">
                    </div>
                </div>
                <div class="ds-filter-actions">
                    <a class="ds-btn" href="{{ route('master.data_siswa') }}">Reset</a>
                    <button type="submit" class="ds-btn ds-btn-primary">Cari</button>
                </div>
            </form>
        </div>

        <div class="ds-card">
            @if (session('status'))
                <div class="ds-alert">{{ session('status') }}</div>
            @endif

            <div class="ds-toolbar">
                <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                    <button type="button" class="ds-btn-bulk-reset" id="dsBulkResetBtn" disabled>Reset Login Android</button>
                    <div class="ds-export" id="dsExport">
                    <button type="button" class="ds-export-btn" id="dsExportToggle">Export ▾</button>
                    <div class="ds-export-menu" id="dsExportMenu">
                        <button type="button" class="ds-export-item" id="dsCopyBtn">Copy</button>
                        <a class="ds-export-item" href="{{ route('master.data_siswa.export_excel', request()->query()) }}">Excel</a>
                        <a class="ds-export-item" href="{{ route('master.data_siswa.export_pdf', request()->query()) }}">Pdf</a>
                    </div>
                    </div>
                </div>
                <form method="GET" action="{{ route('master.data_siswa') }}" style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin:0;">
                    <input type="hidden" name="sort_by" value="{{ $sortBy }}">
                    <input type="hidden" name="sort_dir" value="{{ $sortDir }}">
                    @if (($angkatan ?? '') !== '')<input type="hidden" name="angkatan" value="{{ $angkatan }}">@endif
                    @if (($sekolah ?? '') !== '')<input type="hidden" name="sekolah" value="{{ $sekolah }}">@endif
                    @if (($kelas ?? '') !== '')<input type="hidden" name="kelas" value="{{ $kelas }}">@endif
                    @if (($kelompok ?? '') !== '')<input type="hidden" name="kelompok" value="{{ $kelompok }}">@endif
                    @if (($siswa ?? '') !== '')<input type="hidden" name="siswa" value="{{ $siswa }}">@endif
                    @if (($keyword ?? '') !== '')<input type="hidden" name="q" value="{{ $keyword }}">@endif
                    <span>Tampilkan</span>
                    <select name="per_page" onchange="this.form.submit()" style="height:34px;border:1px solid #d1d5db;border-radius:8px;padding:0 8px;font-size:12px;">
                        @foreach ([10, 25, 50, 100] as $pp)
                            <option value="{{ $pp }}" {{ (int) ($perPage ?? 10) === $pp ? 'selected' : '' }}>{{ $pp }}</option>
                        @endforeach
                    </select>
                    <span>entri</span>
                </form>
                <form method="GET" action="{{ route('master.data_siswa') }}" class="ds-search">
                    <input type="hidden" name="sort_by" value="{{ $sortBy }}">
                    <input type="hidden" name="sort_dir" value="{{ $sortDir }}">
                    @if (($angkatan ?? '') !== '')<input type="hidden" name="angkatan" value="{{ $angkatan }}">@endif
                    @if (($sekolah ?? '') !== '')<input type="hidden" name="sekolah" value="{{ $sekolah }}">@endif
                    @if (($kelas ?? '') !== '')<input type="hidden" name="kelas" value="{{ $kelas }}">@endif
                    @if (($kelompok ?? '') !== '')<input type="hidden" name="kelompok" value="{{ $kelompok }}">@endif
                    @if (($siswa ?? '') !== '')<input type="hidden" name="siswa" value="{{ $siswa }}">@endif
                    <input type="hidden" name="per_page" value="{{ (int) ($perPage ?? 10) }}">
                    <span>Cari:</span>
                    <input type="text" name="q" value="{{ $keyword ?? '' }}" placeholder="kata kunci pencarian">
                </form>
            </div>

            <div class="ds-table-wrap">
                <table class="ds-table">
                    <thead>
                        <tr>
                            <th class="ds-col-chk"><input type="checkbox" id="dsSelectAllReset" title="Pilih semua"></th>
                            <th class="ds-col-no">No</th>
                            <th class="ds-th-sort{{ $sortActive('nocust') }}"><a href="{{ $sortLink('nocust') }}">NIS <i class="{{ $sortIcon('nocust') }}"></i></a></th>
                            <th class="ds-th-sort{{ $sortActive('no_va') }}"><a href="{{ $sortLink('no_va') }}">NO VA <i class="{{ $sortIcon('no_va') }}"></i></a></th>
                            <th class="ds-th-sort{{ $sortActive('nmcust') }}"><a href="{{ $sortLink('nmcust') }}">NAMA <i class="{{ $sortIcon('nmcust') }}"></i></a></th>
                            <th class="ds-th-sort{{ $sortActive('num2nd') }}"><a href="{{ $sortLink('num2nd') }}">No Pendaftaran <i class="{{ $sortIcon('num2nd') }}"></i></a></th>
                            <th class="ds-th-sort{{ $sortActive('unit') }}"><a href="{{ $sortLink('unit') }}">Unit <i class="{{ $sortIcon('unit') }}"></i></a></th>
                            <th class="ds-th-sort{{ $sortActive('kelas') }}"><a href="{{ $sortLink('kelas') }}">Kelas <i class="{{ $sortIcon('kelas') }}"></i></a></th>
                            <th class="ds-th-sort{{ $sortActive('kelompok') }}"><a href="{{ $sortLink('kelompok') }}">Kelompok <i class="{{ $sortIcon('kelompok') }}"></i></a></th>
                            <th class="ds-th-sort{{ $sortActive('angkatan') }}"><a href="{{ $sortLink('angkatan') }}">Angkatan <i class="{{ $sortIcon('angkatan') }}"></i></a></th>
                            <th class="ds-th-sort{{ $sortActive('stcust') }}"><a href="{{ $sortLink('stcust') }}">Status <i class="{{ $sortIcon('stcust') }}"></i></a></th>
                            <th class="ds-th-sort{{ $sortActive('gender') }}"><a href="{{ $sortLink('gender') }}">Jenis Kelamin <i class="{{ $sortIcon('gender') }}"></i></a></th>
                            <th class="ds-th-sort{{ $sortActive('alamat') }}"><a href="{{ $sortLink('alamat') }}">Alamat <i class="{{ $sortIcon('alamat') }}"></i></a></th>
                            <th class="ds-th-sort{{ $sortActive('wali') }}"><a href="{{ $sortLink('wali') }}">Wali <i class="{{ $sortIcon('wali') }}"></i></a></th>
                            <th class="ds-col-act">Reset Login</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse (($siswaRows ?? []) as $index => $row)
                            @php
                                $r = array_change_key_case((array) $row, CASE_LOWER);
                                $nocust = trim((string) ($r['nocust'] ?? ''));
                                $noVa = \App\Support\VaFormatter::fromNis($nocust);
                                $unit = trim((string) ($r['code02'] ?? ''));
                                if ($unit === '') {
                                    $c01 = trim((string) ($r['code01'] ?? ''));
                                    $uSek = trim((string) ($r['unit_sekolah'] ?? ''));
                                    $unit = ($c01 !== '' && $uSek !== '') ? ($c01 . ' — ' . $uSek) : (($uSek !== '') ? $uSek : (($c01 !== '') ? $c01 : '-'));
                                }
                                $wali = trim((string) ($r['wali'] ?? $r['genus'] ?? ''));
                                $custid = trim((string) ($r['custid'] ?? ''));
                                $canReset = $custid !== '' && $nocust !== '' && $nocust !== '-';
                                $stRaw = trim((string) ($r['stcust'] ?? ''));
                                $isAktif = ($stRaw === '1' || $stRaw === '1.0');
                                $gRaw = strtoupper(trim((string) ($r['code04'] ?? '')));
                                if ($gRaw === '') {
                                    $genderLbl = '-';
                                } elseif (in_array($gRaw, ['L', 'LK', 'LAKI', 'LAKI-LAKI', 'PRIA', 'M'], true)) {
                                    $genderLbl = 'Laki-laki';
                                } elseif (in_array($gRaw, ['P', 'PR', 'PEREMPUAN', 'WANITA', 'F'], true)) {
                                    $genderLbl = 'Perempuan';
                                } else {
                                    $genderLbl = $gRaw;
                                }
                                $alamat = trim((string) ($r['desc05'] ?? ''));
                            @endphp
                            <tr>
                                <td class="ds-col-chk">
                                    @if ($canReset)
                                        <input type="checkbox" class="ds-reset-chk" value="{{ $custid }}">
                                    @endif
                                </td>
                                <td class="ds-col-no">{{ ($siswaRows->firstItem() ?? 1) + $index }}</td>
                                <td>{{ $nocust !== '' ? $nocust : '-' }}</td>
                                <td>{{ $noVa !== '' ? $noVa : '-' }}</td>
                                <td>{{ trim((string) ($r['nmcust'] ?? '')) !== '' ? $r['nmcust'] : '-' }}</td>
                                <td>{{ trim((string) ($r['num2nd'] ?? '')) !== '' ? $r['num2nd'] : '-' }}</td>
                                <td>{{ $unit !== '' ? $unit : '-' }}</td>
                                <td>{{ trim((string) ($r['desc02'] ?? '')) !== '' ? $r['desc02'] : '-' }}</td>
                                <td>{{ trim((string) ($r['desc03'] ?? '')) !== '' ? $r['desc03'] : '-' }}</td>
                                <td>{{ trim((string) ($r['desc04'] ?? '')) !== '' ? $r['desc04'] : '-' }}</td>
                                <td class="ds-center">
                                    <span class="ds-pill {{ $isAktif ? 'ds-pill-ok' : 'ds-pill-no' }}">{{ $isAktif ? 'Aktif' : 'Tidak Aktif' }}</span>
                                </td>
                                <td>{{ $genderLbl }}</td>
                                <td>{{ $alamat !== '' ? $alamat : '-' }}</td>
                                <td>{{ $wali !== '' ? $wali : '-' }}</td>
                                <td class="ds-col-act">
                                    @if ($canReset)
                                        <button
                                            type="button"
                                            class="ds-btn-reset-login ds-reset-one"
                                            data-custid="{{ $custid }}"
                                            data-nocust="{{ $nocust }}"
                                            data-nmcust="{{ trim((string) ($r['nmcust'] ?? '')) }}"
                                            data-desc02="{{ trim((string) ($r['desc02'] ?? '')) }}"
                                            data-desc03="{{ trim((string) ($r['desc03'] ?? '')) }}"
                                            data-desc04="{{ trim((string) ($r['desc04'] ?? '')) }}"
                                        >Reset</button>
                                    @else
                                        <button type="button" class="ds-btn-reset-login" disabled title="NIS tidak tersedia">Reset</button>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="15" class="ds-empty">Data siswa tidak ditemukan.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if (isset($siswaRows) && ($siswaRows->total() ?? 0) > 0)
                <div class="ds-pagination-wrap">
                    <div class="ds-pagination-info">
                        Showing {{ $siswaRows->firstItem() }} to {{ $siswaRows->lastItem() }} of {{ $siswaRows->total() }} results
                    </div>
                    <div class="ds-pagination">
                        @php
                            $current = $siswaRows->currentPage();
                            $last = $siswaRows->lastPage();
                            $start = max(1, $current - 2);
                            $end = min($last, $current + 2);
                        @endphp
                        @if ($siswaRows->onFirstPage())
                            <span class="ds-page-link disabled">Prev</span>
                        @else
                            <a class="ds-page-link" href="{{ $siswaRows->appends(request()->query())->url($current - 1) }}">Prev</a>
                        @endif
                        @for ($p = $start; $p <= $end; $p++)
                            @if ($p === $current)
                                <span class="ds-page-link active">{{ $p }}</span>
                            @else
                                <a class="ds-page-link" href="{{ $siswaRows->appends(request()->query())->url($p) }}">{{ $p }}</a>
                            @endif
                        @endfor
                        @if ($siswaRows->hasMorePages())
                            <a class="ds-page-link" href="{{ $siswaRows->appends(request()->query())->url($current + 1) }}">Next</a>
                        @else
                            <span class="ds-page-link disabled">Next</span>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    </div>
    <div class="ds-copy-toast" id="dsCopyToast">Tabel berhasil dicopy.</div>
    <div class="ds-copy-toast" id="dsResetToast"></div>

    <div class="ds-modal" id="dsResetModal" aria-hidden="true">
        <div class="ds-modal-box" role="dialog" aria-modal="true" aria-labelledby="dsResetModalTitle">
            <div class="ds-modal-h">
                <h3 id="dsResetModalTitle">Reset Login Android</h3>
                <button type="button" class="ds-modal-close" id="dsResetModalClose" aria-label="Tutup">×</button>
            </div>
            <div class="ds-modal-b">
                <p style="margin:0;font-size:13px;color:#374151;">Reset login Android siswa? Akun akan dibuat/diperbarui di <strong>sm_user</strong>.</p>
                <div class="ds-modal-fields">
                    <div class="ds-modal-row"><label>NIS</label><input type="text" id="dsResetNocust" readonly></div>
                    <div class="ds-modal-row"><label>Nama</label><input type="text" id="dsResetNmcust" readonly></div>
                    <div class="ds-modal-row"><label>Kelas</label><input type="text" id="dsResetDesc02" readonly></div>
                    <div class="ds-modal-row"><label>Kelompok</label><input type="text" id="dsResetDesc03" readonly></div>
                    <div class="ds-modal-row"><label>Angkatan</label><input type="text" id="dsResetDesc04" readonly></div>
                </div>
                <input type="hidden" id="dsResetCustid" value="">
            </div>
            <div class="ds-modal-f">
                <button type="button" class="ds-modal-cancel" id="dsResetCancel">Batal</button>
                <button type="button" class="ds-modal-submit" id="dsResetSubmit">Reset</button>
            </div>
        </div>
    </div>

    <script>
        (function () {
            const wrap = document.getElementById('dsExport');
            const toggle = document.getElementById('dsExportToggle');
            const copyBtn = document.getElementById('dsCopyBtn');
            const toast = document.getElementById('dsCopyToast');
            const table = document.querySelector('.ds-table');

            if (!wrap || !toggle || !copyBtn || !table) return;

            toggle.addEventListener('click', function () {
                wrap.classList.toggle('open');
            });

            document.addEventListener('click', function (event) {
                if (!wrap.contains(event.target)) {
                    wrap.classList.remove('open');
                }
            });

            copyBtn.addEventListener('click', async function () {
                const lines = [];
                const rows = table.querySelectorAll('tr');

                rows.forEach(function (tr) {
                    const cols = tr.querySelectorAll('th,td');
                    const values = [];
                    cols.forEach(function (cell) {
                        values.push((cell.textContent || '').trim().replace(/\s+/g, ' '));
                    });
                    if (values.length > 0) lines.push(values.join('\t'));
                });

                const text = lines.join('\n');

                try {
                    await navigator.clipboard.writeText(text);
                    if (toast) {
                        toast.classList.add('show');
                        setTimeout(function () {
                            toast.classList.remove('show');
                        }, 1300);
                    }
                } catch (e) {
                    // fallback for older browsers
                    const ta = document.createElement('textarea');
                    ta.value = text;
                    document.body.appendChild(ta);
                    ta.select();
                    document.execCommand('copy');
                    document.body.removeChild(ta);
                }

                wrap.classList.remove('open');
            });
        })();

        (function () {
            const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            const modal = document.getElementById('dsResetModal');
            const resetToast = document.getElementById('dsResetToast');
            const bulkBtn = document.getElementById('dsBulkResetBtn');
            const selectAll = document.getElementById('dsSelectAllReset');
            const selected = new Set();

            function showToast(msg, isError) {
                if (!resetToast) return;
                resetToast.textContent = msg;
                resetToast.style.background = isError ? '#fef2f2' : '#ecfdf5';
                resetToast.style.color = isError ? '#b91c1c' : '#047857';
                resetToast.classList.add('show');
                setTimeout(function () { resetToast.classList.remove('show'); }, 2200);
            }

            function openModal(btn) {
                if (!modal) return;
                document.getElementById('dsResetCustid').value = btn.dataset.custid || '';
                document.getElementById('dsResetNocust').value = btn.dataset.nocust || '';
                document.getElementById('dsResetNmcust').value = btn.dataset.nmcust || '';
                document.getElementById('dsResetDesc02').value = btn.dataset.desc02 || '';
                document.getElementById('dsResetDesc03').value = btn.dataset.desc03 || '';
                document.getElementById('dsResetDesc04').value = btn.dataset.desc04 || '';
                modal.classList.add('open');
            }

            function closeModal() {
                if (modal) modal.classList.remove('open');
            }

            function syncBulkBtn() {
                if (bulkBtn) bulkBtn.disabled = selected.size === 0;
            }

            document.querySelectorAll('.ds-reset-one').forEach(function (btn) {
                btn.addEventListener('click', function () { openModal(btn); });
            });

            ['dsResetModalClose', 'dsResetCancel'].forEach(function (id) {
                const el = document.getElementById(id);
                if (el) el.addEventListener('click', closeModal);
            });
            if (modal) {
                modal.addEventListener('click', function (e) {
                    if (e.target === modal) closeModal();
                });
            }

            const submitBtn = document.getElementById('dsResetSubmit');
            if (submitBtn) {
                submitBtn.addEventListener('click', async function () {
                    const custid = document.getElementById('dsResetCustid')?.value || '';
                    if (!custid) return;
                    submitBtn.disabled = true;
                    try {
                        const urlTpl = @json(route('master.data_siswa.reset_login_android', ['id' => 0]));
                        const url = urlTpl.replace(/\/0\/reset-login-android$/, '/' + encodeURIComponent(custid) + '/reset-login-android');
                        const res = await fetch(url, {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': csrf,
                                'Accept': 'application/json',
                            },
                        });
                        const data = await res.json().catch(function () { return {}; });
                        if (!res.ok) {
                            showToast(data.message || 'Gagal reset login Android', true);
                            return;
                        }
                        closeModal();
                        showToast(data.message || 'Login Android direset!', false);
                    } catch (e) {
                        showToast('Gagal menghubungi server', true);
                    } finally {
                        submitBtn.disabled = false;
                    }
                });
            }

            document.querySelectorAll('.ds-reset-chk').forEach(function (chk) {
                chk.addEventListener('change', function () {
                    if (chk.checked) selected.add(chk.value);
                    else selected.delete(chk.value);
                    syncBulkBtn();
                    if (selectAll) {
                        const all = document.querySelectorAll('.ds-reset-chk');
                        selectAll.checked = all.length > 0 && Array.from(all).every(function (c) { return c.checked; });
                    }
                });
            });

            if (selectAll) {
                selectAll.addEventListener('change', function () {
                    document.querySelectorAll('.ds-reset-chk').forEach(function (chk) {
                        chk.checked = selectAll.checked;
                        if (selectAll.checked) selected.add(chk.value);
                        else selected.delete(chk.value);
                    });
                    syncBulkBtn();
                });
            }

            if (bulkBtn) {
                bulkBtn.addEventListener('click', async function () {
                    const ids = Array.from(selected);
                    if (ids.length === 0) return;
                    if (!window.confirm('Reset login Android untuk ' + ids.length + ' siswa terpilih?')) return;
                    bulkBtn.disabled = true;
                    try {
                        const res = await fetch(@json(route('master.data_siswa.reset_login_android_bulk')), {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': csrf,
                                'Accept': 'application/json',
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify({ custids: ids }),
                        });
                        const data = await res.json().catch(function () { return {}; });
                        if (!res.ok) {
                            showToast(data.message || 'Gagal reset login android massal', true);
                            return;
                        }
                        selected.clear();
                        document.querySelectorAll('.ds-reset-chk').forEach(function (c) { c.checked = false; });
                        if (selectAll) selectAll.checked = false;
                        showToast(data.message || 'Reset Android berhasil', false);
                    } catch (e) {
                        showToast('Gagal menghubungi server', true);
                    } finally {
                        syncBulkBtn();
                    }
                });
            }
        })();
    </script>
@endsection
