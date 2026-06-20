@extends('layouts.app')

@section('title', 'Saldo Cashless')

@section('content')
    <h3 class="page-heading d-flex text-gray-900 fw-bold flex-column justify-content-center my-0">Cashless</h3>
    <ul class="breadcrumb breadcrumb-style2">
        <li class="breadcrumb-item">Cashless</li>
        <li class="breadcrumb-item active">Saldo</li>
    </ul>

    @include('cashless._nav')

    <h1 style="font-size:1.4rem;font-weight:700;margin-bottom:6px;">Saldo Cashless</h1>
    <p style="color:#6b7280;margin-bottom:12px;">Data saldo wallet siswa.</p>

        <form method="GET" action="{{ route('cashless.saldo') }}" style="display:flex;gap:8px;margin-bottom:12px;">
            <input type="text" name="q" value="{{ $search ?? '' }}" placeholder="Cari NIS / nama siswa" style="flex:1;height:42px;border:1px solid #d1e7d8;border-radius:10px;padding:0 12px;">
            <button type="submit" class="btn-primary" style="width:auto;padding:0 18px;">Cari</button>
        </form>

        <div style="overflow:auto;border:1px solid #d1e7d8;border-radius:12px;background:#fff;">
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
@endsection

