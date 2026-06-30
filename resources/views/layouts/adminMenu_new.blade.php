<aside id="layout-menu" class="layout-menu menu-vertical menu bg-menu-theme">
    <div class="app-brand pt-2">
        <a href="{{ route('dashboard') }}" class="app-brand-link">
            <span class="app-brand-logo">
                <span style="color: var(--bs-primary)">
                    <img width="50" height="50" src="{{ asset('mojokerto.png') }}" alt="logo">
                </span>
            </span>
            <span class="app-brand-text demo menu-text fw-bold ms-2">
                SIKEU
                @if(session('auth_sekolah_nama'))
                    <div class="pt-1 small fw-normal">{{ session('auth_sekolah_nama') }}</div>
                @endif
            </span>
        </a>
        <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large ms-auto">
            <svg width="22" height="22" viewBox="0 0 22 22" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M11.4854 4.88844C11.0081 4.41121 10.2344 4.41121 9.75715 4.88844L4.51028 10.1353C4.03297 10.6126 4.03297 11.3865 4.51028 11.8638L9.75715 17.1107C10.2344 17.5879 11.0081 17.5879 11.4854 17.1107C11.9626 16.6334 11.9626 15.8597 11.4854 15.3824L7.96672 11.8638C7.48942 11.3865 7.48942 10.6126 7.96672 10.1353L11.4854 6.61667C11.9626 6.13943 11.9626 5.36568 11.4854 4.88844Z" fill="currentColor" fill-opacity="0.6"/>
                <path d="M15.8683 4.88844L10.6214 10.1353C10.1441 10.6126 10.1441 11.3865 10.6214 11.8638L15.8683 17.1107C16.3455 17.5879 17.1192 17.5879 17.5965 17.1107C18.0737 16.6334 18.0737 15.8597 17.5965 15.3824L14.0778 11.8638C13.6005 11.3865 13.6005 10.6126 14.0778 10.1353L17.5965 6.61667C18.0737 6.13943 18.0737 5.36568 17.5965 4.88844C17.1192 4.41121 16.3455 4.41121 15.8683 4.88844Z" fill="currentColor" fill-opacity="0.38"/>
            </svg>
        </a>
    </div>

    <div class="menu-inner-shadow"></div>

    <ul class="menu-inner py-1">
        <li class="menu-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
            <a href="{{ route('dashboard') }}" class="menu-link">
                <i class="menu-icon ri-home-5-line"></i>
                <div>Beranda</div>
            </a>
        </li>

        <li class="menu-item {{ request()->routeIs('master.*') ? 'active open' : '' }}">
            <a href="javascript:void(0);" class="menu-link menu-toggle">
                <i class="menu-icon ri-database-2-line"></i>
                <div>Master Data</div>
            </a>
            <ul class="menu-sub">
                @if($authIsSuperadmin ?? false)
                    <li class="menu-item {{ request()->routeIs('master.sekolah*') ? 'active' : '' }}">
                        <a href="{{ route('master.sekolah') }}" class="menu-link"><div>Master Sekolah</div></a>
                    </li>
                @endif
                <li class="menu-item {{ request()->routeIs('master.kelas*') ? 'active' : '' }}">
                    <a href="{{ route('master.kelas') }}" class="menu-link"><div>Master Kelas</div></a>
                </li>
                <li class="menu-item {{ request()->routeIs('master.tahun_pelajaran*') ? 'active' : '' }}">
                    <a href="{{ route('master.tahun_pelajaran') }}" class="menu-link"><div>Tahun Pelajaran</div></a>
                </li>
                <li class="menu-item {{ request()->routeIs('master.post*') ? 'active' : '' }}">
                    <a href="{{ route('master.post') }}" class="menu-link"><div>Master Post</div></a>
                </li>
                <li class="menu-item {{ request()->routeIs('master.beban_post*') ? 'active' : '' }}">
                    <a href="{{ route('master.beban_post') }}" class="menu-link"><div>Beban Post</div></a>
                </li>
                <li class="menu-item {{ request()->routeIs('master.export_import*') ? 'active' : '' }}">
                    <a href="{{ route('master.export_import') }}" class="menu-link"><div>Export Import Data</div></a>
                </li>
                <li class="menu-item {{ request()->routeIs('master.data_siswa*') ? 'active' : '' }}">
                    <a href="{{ route('master.data_siswa') }}" class="menu-link"><div>Data Siswa</div></a>
                </li>
                <li class="menu-item {{ request()->routeIs('master.pindah_kelas*') ? 'active' : '' }}">
                    <a href="{{ route('master.pindah_kelas') }}" class="menu-link"><div>Pindah Kelas</div></a>
                </li>
            </ul>
        </li>

        <li class="menu-item {{ request()->routeIs('keu.*') ? 'active open' : '' }}">
            <a href="javascript:void(0);" class="menu-link menu-toggle">
                <i class="menu-icon ri-bank-line"></i>
                <div>Keuangan</div>
            </a>
            <ul class="menu-sub">
                <li class="menu-item {{ request()->routeIs('keu.tagihan.*') ? 'active open' : '' }}">
                    <a href="javascript:void(0)" class="menu-link menu-toggle">
                        <div>Tagihan Siswa</div>
                    </a>
                    <ul class="menu-sub">
                        <li class="menu-item {{ request()->routeIs('keu.tagihan.buat') ? 'active' : '' }}">
                            <a href="{{ route('keu.tagihan.buat') }}" class="menu-link"><div>Buat Tagihan</div></a>
                        </li>
                        <li class="menu-item {{ request()->routeIs(['keu.tagihan.upload_excel', 'keu.tagihan.upload_excel.contoh', 'keu.tagihan.upload_excel.import', 'keu.tagihan.upload_excel.save', 'keu.tagihan.upload_excel.clear']) ? 'active' : '' }}">
                            <a href="{{ route('keu.tagihan.upload_excel') }}" class="menu-link"><div>Buat Tagihan Excel</div></a>
                        </li>
                        <li class="menu-item {{ request()->routeIs('keu.tagihan.data') ? 'active' : '' }}">
                            <a href="{{ route('keu.tagihan.data') }}" class="menu-link"><div>Data Tagihan</div></a>
                        </li>
                        <li class="menu-item {{ request()->routeIs('keu.tagihan.rekap') ? 'active' : '' }}">
                            <a href="{{ route('keu.tagihan.rekap') }}" class="menu-link"><div>Rekap Tagihan</div></a>
                        </li>
                    </ul>
                </li>
                <li class="menu-item {{ request()->routeIs('keu.manual') ? 'active' : '' }}">
                    <a href="{{ route('keu.manual') }}" class="menu-link"><div>Manual Pembayaran</div></a>
                </li>
                <li class="menu-item {{ request()->routeIs('keu.penerimaan.*') ? 'active open' : '' }}">
                    <a href="javascript:void(0);" class="menu-link menu-toggle">
                        <div>Penerimaan Siswa</div>
                    </a>
                    <ul class="menu-sub">
                        <li class="menu-item {{ request()->routeIs('keu.penerimaan.data') ? 'active' : '' }}">
                            <a href="{{ route('keu.penerimaan.data') }}" class="menu-link"><div>Data Penerimaan</div></a>
                        </li>
                        <li class="menu-item {{ request()->routeIs(['keu.penerimaan.rekap', 'keu.penerimaan.rekap_rows']) ? 'active' : '' }}">
                            <a href="{{ route('keu.penerimaan.rekap') }}" class="menu-link"><div>Rekap Penerimaan</div></a>
                        </li>
                    </ul>
                </li>
                <li class="menu-item {{ request()->routeIs('keu.saldo.*') ? 'active open' : '' }}">
                    <a href="javascript:void(0);" class="menu-link menu-toggle">
                        <div>Saldo</div>
                    </a>
                    <ul class="menu-sub">
                        <li class="menu-item {{ request()->routeIs(['keu.saldo.va', 'keu.saldo.va.rows', 'keu.saldo.va.detail', 'keu.saldo.va.detail_rows']) ? 'active' : '' }}">
                            <a href="{{ route('keu.saldo.va') }}" class="menu-link"><div>Saldo Virtual Account</div></a>
                        </li>
                        <li class="menu-item {{ request()->routeIs('keu.saldo.transaksi') ? 'active' : '' }}">
                            <a href="{{ route('keu.saldo.transaksi') }}" class="menu-link"><div>Data Transaksi</div></a>
                        </li>
                    </ul>
                </li>
                <li class="menu-item {{ request()->routeIs(['keu.hapus_tagihan', 'keu.hapus_tagihan.rows', 'keu.hapus_tagihan.submit']) ? 'active' : '' }}">
                    <a href="{{ route('keu.hapus_tagihan') }}" class="menu-link"><div>Hapus Tagihan</div></a>
                </li>
            </ul>
        </li>

        <li class="menu-item {{ request()->routeIs('manual_input.*') ? 'active open' : '' }}">
            <a href="javascript:void(0);" class="menu-link menu-toggle">
                <i class="menu-icon ri-keyboard-line"></i>
                <div>Manual Input</div>
            </a>
            <ul class="menu-sub">
                <li class="menu-item {{ request()->routeIs('manual_input.edit_manual') ? 'active' : '' }}">
                    <a href="{{ route('manual_input.edit_manual') }}" class="menu-link"><div>Edit Manual</div></a>
                </li>
            </ul>
        </li>

        <li class="menu-item {{ request()->routeIs('smartcard.*') ? 'active open' : '' }}">
            <a href="javascript:void(0);" class="menu-link menu-toggle">
                <i class="menu-icon ri-bank-card-line"></i>
                <div>Smartcard</div>
            </a>
            <ul class="menu-sub">
                <li class="menu-item {{ request()->routeIs('smartcard.data_kartu*') ? 'active' : '' }}">
                    <a href="{{ route('smartcard.data_kartu') }}" class="menu-link"><div>Data Kartu Siswa</div></a>
                </li>
            </ul>
        </li>

        <li class="menu-item {{ request()->routeIs('rekap.*') ? 'active open' : '' }}">
            <a href="javascript:void(0);" class="menu-link menu-toggle">
                <i class="menu-icon ri-file-list-3-line"></i>
                <div>Rekap Data</div>
            </a>
            <ul class="menu-sub">
                <li class="menu-item {{ request()->routeIs('rekap.cek_pelunasan') ? 'active' : '' }}">
                    <a href="{{ route('rekap.cek_pelunasan') }}" class="menu-link"><div>Cek Pelunasan</div></a>
                </li>
            </ul>
        </li>

        <li class="menu-item mt-auto pb-2">
            <a href="{{ route('logout') }}" class="menu-link btn-danger text-white" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                <i class="menu-icon ri-logout-box-r-line"></i>
                <div>Logout</div>
            </a>
        </li>
    </ul>
</aside>
