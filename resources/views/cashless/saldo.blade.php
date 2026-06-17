@extends('layouts.portal')

@section('title', 'Saldo Cashless')

@section('content')
<div class="portal-page">
    <div class="portal-card wide">
        <div class="brand">
            <img src="{{ asset('logo.png') }}" alt="Logo">
            <div class="brand-name">{{ config('app.name') }}</div>
        </div>

        @include('cashless._nav')

        <h1 class="portal-title" style="font-size:1.4rem;">Saldo Cashless</h1>
        <p class="portal-sub">Data saldo wallet siswa.</p>

        <form method="GET" action="{{ route('cashless.saldo') }}" style="display:flex;gap:8px;margin-bottom:12px;">
            <input type="text" name="q" value="{{ $search ?? '' }}" placeholder="Cari NIS / nama siswa" style="flex:1;height:42px;border:1px solid #d1e7d8;border-radius:10px;padding:0 12px;">
            <button type="submit" class="btn-primary" style="width:auto;padding:0 18px;">Cari</button>
        </form>

        <div style="overflow:auto;border:1px solid #d1e7d8;border-radius:12px;">
            <table style="width:100%;border-collapse:collapse;background:#fff;">
                <thead style="background:#f6fbf8;">
                    <tr>
                        <th style="text-align:left;padding:10px;border-bottom:1px solid #e5efe8;">NIS</th>
                        <th style="text-align:left;padding:10px;border-bottom:1px solid #e5efe8;">Nama</th>
                        <th style="text-align:right;padding:10px;border-bottom:1px solid #e5efe8;">Saldo</th>
                        <th style="text-align:left;padding:10px;border-bottom:1px solid #e5efe8;">Update</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($saldoRows as $row)
                        <tr>
                            <td style="padding:10px;border-bottom:1px solid #f1f5f3;">{{ $row['student_id'] ?? '-' }}</td>
                            <td style="padding:10px;border-bottom:1px solid #f1f5f3;">{{ $row['student_name'] ?? '-' }}</td>
                            <td style="padding:10px;border-bottom:1px solid #f1f5f3;text-align:right;">Rp {{ number_format((float)($row['balance'] ?? 0), 0, ',', '.') }}</td>
                            <td style="padding:10px;border-bottom:1px solid #f1f5f3;">{{ $row['updated_at'] ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" style="padding:14px;text-align:center;color:#6b7280;">Belum ada data saldo / tabel `cashless_wallets` belum tersedia.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

