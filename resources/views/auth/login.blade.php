@extends('layouts.portal')

@section('title', 'Masuk Portal')

@section('content')
<div class="portal-page">
    <div class="portal-card">
        <div class="brand">
            <img src="{{ asset('logo.png') }}" alt="Logo">
            <div class="brand-name">{{ config('app.name') }}</div>
        </div>

        <h1 class="portal-title">Masuk Portal</h1>
        <p class="portal-sub">{{ config('app.name') }}</p>

        @if(session('status'))
            <div class="alert alert-info">{{ session('status') }}</div>
        @endif

        <form method="POST" action="{{ route('login') }}">
            @csrf

            <div class="field">
                <label for="username">Username</label>
                <input
                    id="username"
                    type="text"
                    name="username"
                    value="{{ old('username') }}"
                    placeholder="contoh: admin"
                    required
                    autofocus
                >
                @error('username')
                    <div class="error-text">{{ $message }}</div>
                @enderror
            </div>

            <div class="field">
                <label for="password">Password</label>
                <input
                    id="password"
                    type="password"
                    name="password"
                    placeholder="Masukkan password"
                    required
                >
                @error('password')
                    <div class="error-text">{{ $message }}</div>
                @enderror
            </div>

            @if(config('services.turnstile.site_key'))
                <div class="field" style="display:flex;justify-content:center;margin-bottom:14px;">
                    <div class="cf-turnstile" data-sitekey="{{ config('services.turnstile.site_key') }}" data-language="id"></div>
                </div>
                @error('turnstile')
                    <div class="error-text" style="text-align:center;margin-bottom:10px;">{{ $message }}</div>
                @enderror
            @endif

            <button type="submit" class="btn-primary">Masuk</button>
        </form>

        <div class="portal-note">
            <strong>Satu login — pilih modul setelah masuk</strong>
            Setelah login, Anda akan diarahkan ke halaman pemilihan modul:
            <strong>SIKEU</strong> (keuangan), <strong>Cashless</strong>, atau <strong>Presensi</strong> (segera hadir).
        </div>
    </div>
</div>

@if(config('services.turnstile.site_key'))
    <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
@endif
@endsection
