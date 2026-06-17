@extends('layouts.portal')

@section('title', 'Pilih Modul')

@section('content')
<div class="portal-page">
    <div class="portal-card wide">
        <div class="brand">
            <img src="{{ asset('logo.png') }}" alt="Logo">
            <div class="brand-name">{{ config('app.name') }}</div>
        </div>

        <h1 class="portal-title">Pilih Modul</h1>
        <p class="portal-sub">Satu login untuk semua layanan</p>

        <div class="portal-user">
            Halo, <strong>{{ $userName }}</strong>
            @if(session('auth_sekolah_nama'))
                <br><span>{{ session('auth_sekolah_nama') }}</span>
            @endif
        </div>

        @if(session('portal_info'))
            <div class="alert alert-info">{{ session('portal_info') }}</div>
        @endif
        @error('module')
            <div class="alert alert-error">{{ $message }}</div>
        @enderror

        <div class="module-grid">
            <a href="{{ route('portal.sikeu') }}" class="module-card">
                <div class="module-icon">💰</div>
                <h3>SIKEU</h3>
                <p>Sistem Informasi Keuangan — tagihan, pembayaran, dan laporan.</p>
            </a>

            @if(($modules['cashless']['enabled'] ?? false))
                <a href="{{ route('portal.cashless') }}" class="module-card">
                    <div class="module-icon">💳</div>
                    <h3>Cashless</h3>
                    <p>Pembayaran non-tunai dan saldo siswa.</p>
                </a>
            @else
                <div class="module-card disabled">
                    <div class="module-icon">💳</div>
                    <h3>Cashless</h3>
                    <p>Modul cashless tidak aktif.</p>
                </div>
            @endif

            <a href="{{ route('portal.presensi') }}" class="module-card disabled" onclick="return false;" style="pointer-events: none;">
                <div class="module-icon">📋</div>
                <h3>Presensi</h3>
                <p>Segera hadir — modul presensi sedang disiapkan.</p>
            </a>
        </div>

        <div class="portal-actions">
            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button type="submit" class="btn-link">Keluar</button>
            </form>
            <span class="back-link">Menu menurut role, data menurut unit</span>
        </div>
    </div>
</div>
@endsection
