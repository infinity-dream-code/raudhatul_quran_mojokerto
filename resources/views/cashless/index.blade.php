@extends('layouts.app')

@section('title', 'Cashless')

@section('style')
<style>
    .summary-cards {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 12px;
        margin-bottom: 16px;
    }
    .summary-card {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        padding: 14px;
        box-shadow: 0 8px 18px rgba(15, 23, 42, 0.05);
    }
    .summary-label {
        color: #6b7280;
        font-size: 12px;
        margin-bottom: 6px;
    }
    .summary-value {
        font-size: 1.25rem;
        font-weight: 700;
        color: #111827;
    }
    .filter-wrap {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        padding: 14px;
        margin-bottom: 14px;
    }
    .table-wrap {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        overflow: auto;
    }
    .cashless-table {
        width: 100%;
        border-collapse: collapse;
        min-width: 920px;
    }
    .cashless-table th, .cashless-table td {
        padding: 10px 12px;
        border-bottom: 1px solid #eef2f7;
        font-size: 13px;
        vertical-align: top;
    }
    .cashless-table th {
        text-align: left;
        font-weight: 700;
        color: #374151;
        background: #f9fafb;
    }
    .money {
        text-align: right;
        font-weight: 700;
    }
    .badge-soft {
        display: inline-block;
        padding: 4px 8px;
        border-radius: 999px;
        font-size: 11px;
        font-weight: 700;
    }
    .badge-sukses { background: #dcfce7; color: #166534; }
    .badge-gagal { background: #fee2e2; color: #991b1b; }
    .badge-refund { background: #ffedd5; color: #9a3412; }
    .sub-line { color: #6b7280; font-size: 12px; margin-top: 2px; }
    .page-tools {
        display: flex;
        gap: 8px;
        align-items: center;
        flex-wrap: wrap;
    }
</style>
@endsection

@section('content')
    <h3 class="page-heading d-flex text-gray-900 fw-bold flex-column justify-content-center my-0">Cashless</h3>
    <ul class="breadcrumb breadcrumb-style2">
        <li class="breadcrumb-item active">Dashboard Cashless</li>
    </ul>

    @include('cashless._nav')

    <div class="summary-cards">
        <div class="summary-card">
            <div class="summary-label">Total Transaksi</div>
            <div class="summary-value">{{ number_format((int)($stats['transaction_count'] ?? 0), 0, ',', '.') }}</div>
        </div>
        <div class="summary-card">
            <div class="summary-label">Total Pendapatan (Sukses)</div>
            <div class="summary-value">Rp {{ number_format((float)($stats['total_balance'] ?? 0), 0, ',', '.') }}</div>
        </div>
        <div class="summary-card">
            <div class="summary-label">Sukses</div>
            <div class="summary-value">{{ number_format((int)($stats['success_count'] ?? 0), 0, ',', '.') }}</div>
        </div>
        <div class="summary-card">
            <div class="summary-label">Gagal</div>
            <div class="summary-value">{{ number_format((int)($stats['failed_count'] ?? 0), 0, ',', '.') }}</div>
        </div>
        <div class="summary-card">
            <div class="summary-label">Refund</div>
            <div class="summary-value">{{ number_format((int)($stats['refund_count'] ?? 0), 0, ',', '.') }}</div>
        </div>
    </div>

    <div class="filter-wrap">
        <div class="page-tools">
            <a class="btn btn-primary btn-sm" href="{{ route('cashless.topup') }}">Catat Transaksi / Topup</a>
            <a class="btn btn-outline-primary btn-sm" href="{{ route('cashless.transactions') }}">Lihat Semua Transaksi</a>
            <a class="btn btn-outline-secondary btn-sm" href="{{ route('cashless.saldo') }}">Saldo Wallet</a>
        </div>
    </div>

    <div class="table-wrap">
        <table class="cashless-table">
            <thead>
            <tr>
                <th>Transaksi</th>
                <th>Siswa</th>
                <th>Detail</th>
                <th class="money">Nominal</th>
                <th>Status</th>
            </tr>
            </thead>
            <tbody>
            @forelse($rows as $row)
                @php
                    $status = strtolower((string)($row->status ?? 'sukses'));
                    $badgeClass = $status === 'gagal' ? 'badge-gagal' : ($status === 'refund' ? 'badge-refund' : 'badge-sukses');
                    $kode = $row->trx_code ?? ('TRX-' . str_pad((string)($row->id ?? 0), 6, '0', STR_PAD_LEFT));
                @endphp
                <tr>
                    <td>
                        <strong>{{ $kode }}</strong>
                        <div class="sub-line">{{ $row->created_at ?? '-' }}</div>
                    </td>
                    <td>
                        <strong>{{ $row->student_name ?? '-' }}</strong>
                        <div class="sub-line">{{ $row->student_id ?? '-' }}</div>
                    </td>
                    <td>
                        {{ $row->note ?? '-' }}
                    </td>
                    <td class="money">Rp {{ number_format((float)($row->amount ?? 0), 0, ',', '.') }}</td>
                    <td><span class="badge-soft {{ $badgeClass }}">{{ ucfirst($status) }}</span></td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" style="text-align:center;color:#6b7280;padding:18px;">
                        Belum ada data transaksi cashless.
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
@endsection

