@extends('layouts.app')

@section('title', 'Transaksi Cashless')

@section('content')
    <h3 class="page-heading d-flex text-gray-900 fw-bold flex-column justify-content-center my-0">Cashless</h3>
    <ul class="breadcrumb breadcrumb-style2">
        <li class="breadcrumb-item">Cashless</li>
        <li class="breadcrumb-item active">Transaksi</li>
    </ul>

    @include('cashless._nav')

    <h1 style="font-size:1.4rem;font-weight:700;margin-bottom:6px;">Riwayat Transaksi</h1>
    <p style="color:#6b7280;margin-bottom:12px;">100 transaksi terakhir cashless.</p>

        @if(session('status'))
            <div class="alert alert-info" style="margin-bottom:10px;">{{ session('status') }}</div>
        @endif

        <div style="overflow:auto;border:1px solid #d1e7d8;border-radius:12px;background:#fff;">
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
@endsection

