@extends('layouts.portal')

@section('title', 'Transaksi Cashless')

@section('content')
<div class="portal-page">
    <div class="portal-card wide">
        <div class="brand">
            <img src="{{ asset('logo.png') }}" alt="Logo">
            <div class="brand-name">{{ config('app.name') }}</div>
        </div>

        @include('cashless._nav')

        <h1 class="portal-title" style="font-size:1.4rem;">Riwayat Transaksi</h1>
        <p class="portal-sub">100 transaksi terakhir cashless.</p>

        @if(session('status'))
            <div class="alert alert-info" style="margin-bottom:10px;">{{ session('status') }}</div>
        @endif

        <div style="overflow:auto;border:1px solid #d1e7d8;border-radius:12px;">
            <table style="width:100%;border-collapse:collapse;background:#fff;">
                <thead style="background:#f6fbf8;">
                    <tr>
                        <th style="text-align:left;padding:10px;border-bottom:1px solid #e5efe8;">Tanggal</th>
                        <th style="text-align:left;padding:10px;border-bottom:1px solid #e5efe8;">NIS</th>
                        <th style="text-align:left;padding:10px;border-bottom:1px solid #e5efe8;">Nama</th>
                        <th style="text-align:left;padding:10px;border-bottom:1px solid #e5efe8;">Jenis</th>
                        <th style="text-align:right;padding:10px;border-bottom:1px solid #e5efe8;">Nominal</th>
                        <th style="text-align:left;padding:10px;border-bottom:1px solid #e5efe8;">Catatan</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows as $row)
                        <tr>
                            <td style="padding:10px;border-bottom:1px solid #f1f5f3;">{{ $row->created_at ?? '-' }}</td>
                            <td style="padding:10px;border-bottom:1px solid #f1f5f3;">{{ $row->student_id ?? '-' }}</td>
                            <td style="padding:10px;border-bottom:1px solid #f1f5f3;">{{ $row->student_name ?? '-' }}</td>
                            <td style="padding:10px;border-bottom:1px solid #f1f5f3;">{{ $row->type ?? '-' }}</td>
                            <td style="padding:10px;border-bottom:1px solid #f1f5f3;text-align:right;">Rp {{ number_format((float)($row->amount ?? 0), 0, ',', '.') }}</td>
                            <td style="padding:10px;border-bottom:1px solid #f1f5f3;">{{ $row->note ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" style="padding:14px;text-align:center;color:#6b7280;">Belum ada data transaksi / tabel `cashless_transactions` belum tersedia.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

