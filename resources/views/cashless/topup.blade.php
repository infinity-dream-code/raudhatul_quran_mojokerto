@extends('layouts.app')

@section('title', 'Topup Cashless')

@section('content')
    <h3 class="page-heading d-flex text-gray-900 fw-bold flex-column justify-content-center my-0">Cashless</h3>
    <ul class="breadcrumb breadcrumb-style2">
        <li class="breadcrumb-item">Cashless</li>
        <li class="breadcrumb-item active">Topup</li>
    </ul>

    @include('cashless._nav')

    <div style="background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:16px;">
        <h1 style="font-size:1.4rem;font-weight:700;margin-bottom:6px;">Topup Cashless</h1>
        <p style="color:#6b7280;margin-bottom:12px;">Input topup saldo siswa.</p>

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

            <button type="submit" class="btn btn-primary">Simpan Topup</button>
        </form>
    </div>
@endsection

