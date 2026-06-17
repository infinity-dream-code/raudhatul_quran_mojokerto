@extends('layouts.portal')

@section('title', 'Cashless')

@section('style')
<style>
    .cashless-head {
        text-align: center;
        margin-bottom: 18px;
    }
    .cashless-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 12px;
        margin-top: 14px;
    }
    .cashless-item {
        background: #fff;
        border: 1px solid #d1e7d8;
        border-radius: 12px;
        padding: 14px;
    }
    .cashless-item h4 {
        color: #1f6b45;
        margin-bottom: 4px;
        font-size: 0.95rem;
    }
    .cashless-item p {
        font-size: 0.85rem;
        color: #6b7280;
    }
</style>
@endsection

@section('content')
<div class="portal-page">
    <div class="portal-card wide">
        <div class="brand">
            <img src="{{ asset('logo.png') }}" alt="Logo">
            <div class="brand-name">{{ config('app.name') }}</div>
        </div>

        @include('cashless._nav')

        <div class="cashless-head">
            <h1 class="portal-title">Dashboard Cashless</h1>
            <p class="portal-sub">Halo, {{ $userName }}. Ringkasan modul cashless.</p>
        </div>

        <div class="cashless-grid">
            <div class="cashless-item">
                <h4>Jumlah Wallet</h4>
                <p><strong>{{ number_format((int)($stats['wallet_count'] ?? 0), 0, ',', '.') }}</strong> akun wallet.</p>
            </div>
            <div class="cashless-item">
                <h4>Total Saldo</h4>
                <p><strong>Rp {{ number_format((float)($stats['total_balance'] ?? 0), 0, ',', '.') }}</strong></p>
            </div>
            <div class="cashless-item">
                <h4>Topup Hari Ini</h4>
                <p><strong>Rp {{ number_format((float)($stats['today_topup'] ?? 0), 0, ',', '.') }}</strong></p>
            </div>
            <div class="cashless-item">
                <h4>Total Transaksi</h4>
                <p><strong>{{ number_format((int)($stats['transaction_count'] ?? 0), 0, ',', '.') }}</strong> data transaksi.</p>
            </div>
        </div>

        <div class="portal-actions">
            <a href="{{ route('cashless.saldo') }}" class="btn-link">Lihat Saldo</a>
            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button type="submit" class="btn-link">Keluar</button>
            </form>
        </div>
    </div>
</div>
@endsection

