<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <div class="nav nav-pills gap-2">
        <a href="{{ route('cashless.index') }}" class="nav-link {{ request()->routeIs('cashless.index') ? 'active' : '' }}">Dashboard</a>
        <a href="{{ route('cashless.saldo') }}" class="nav-link {{ request()->routeIs('cashless.saldo') ? 'active' : '' }}">Saldo</a>
        <a href="{{ route('cashless.topup') }}" class="nav-link {{ request()->routeIs('cashless.topup') ? 'active' : '' }}">Topup</a>
        <a href="{{ route('cashless.transactions') }}" class="nav-link {{ request()->routeIs('cashless.transactions') ? 'active' : '' }}">Transaksi</a>
    </div>
    <a href="{{ route('portal.switch') }}" class="btn btn-outline-secondary btn-sm">Ganti Modul</a>
</div>

