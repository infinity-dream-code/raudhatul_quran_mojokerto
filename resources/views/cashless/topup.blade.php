@extends('layouts.portal')

@section('title', 'Topup Cashless')

@section('content')
<div class="portal-page">
    <div class="portal-card wide">
        <div class="brand">
            <img src="{{ asset('logo.png') }}" alt="Logo">
            <div class="brand-name">{{ config('app.name') }}</div>
        </div>

        @include('cashless._nav')

        <h1 class="portal-title" style="font-size:1.4rem;">Topup Cashless</h1>
        <p class="portal-sub">Input topup saldo siswa.</p>

        @error('topup')
            <div class="alert alert-error" style="margin-bottom:10px;">{{ $message }}</div>
        @enderror

        <form method="POST" action="{{ route('cashless.topup.store') }}">
            @csrf
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
                <div class="field">
                    <label for="student_id">NIS / Student ID</label>
                    <input id="student_id" name="student_id" type="text" value="{{ old('student_id') }}" required>
                    @error('student_id')<div class="error-text">{{ $message }}</div>@enderror
                </div>
                <div class="field">
                    <label for="student_name">Nama Siswa</label>
                    <input id="student_name" name="student_name" type="text" value="{{ old('student_name') }}" required>
                    @error('student_name')<div class="error-text">{{ $message }}</div>@enderror
                </div>
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
                <div class="field">
                    <label for="amount">Nominal Topup</label>
                    <input id="amount" name="amount" type="number" min="1" step="1" value="{{ old('amount') }}" required>
                    @error('amount')<div class="error-text">{{ $message }}</div>@enderror
                </div>
                <div class="field">
                    <label for="note">Catatan</label>
                    <input id="note" name="note" type="text" value="{{ old('note') }}" placeholder="Opsional">
                    @error('note')<div class="error-text">{{ $message }}</div>@enderror
                </div>
            </div>

            <button type="submit" class="btn-primary">Simpan Topup</button>
        </form>
    </div>
</div>
@endsection

