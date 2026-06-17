<div class="portal-actions" style="margin-top:0;margin-bottom:16px;">
    <div style="display:flex;gap:8px;flex-wrap:wrap;">
        <a href="{{ route('cashless.index') }}" class="btn-link" @if(request()->routeIs('cashless.index')) style="font-weight:700;" @endif>Dashboard</a>
        <a href="{{ route('cashless.saldo') }}" class="btn-link" @if(request()->routeIs('cashless.saldo')) style="font-weight:700;" @endif>Saldo</a>
        <a href="{{ route('cashless.topup') }}" class="btn-link" @if(request()->routeIs('cashless.topup')) style="font-weight:700;" @endif>Topup</a>
        <a href="{{ route('cashless.transactions') }}" class="btn-link" @if(request()->routeIs('cashless.transactions')) style="font-weight:700;" @endif>Transaksi</a>
    </div>
    <a href="{{ route('portal.switch') }}" class="btn-link">Ganti Modul</a>
</div>

