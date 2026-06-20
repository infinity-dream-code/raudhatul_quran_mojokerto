@extends('layouts.app')

@section('title', 'Beranda')

@section('content')
    @php
        $hariIndo = [
            'Sunday' => 'Minggu', 'Monday' => 'Senin', 'Tuesday' => 'Selasa', 'Wednesday' => 'Rabu',
            'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu',
        ];
        $bulanIndo = [
            'January' => 'Januari', 'February' => 'Februari', 'March' => 'Maret', 'April' => 'April',
            'May' => 'Mei', 'June' => 'Juni', 'July' => 'Juli', 'August' => 'Agustus',
            'September' => 'September', 'October' => 'Oktober', 'November' => 'November', 'December' => 'Desember',
        ];
    @endphp

    <h3 class="page-heading d-flex text-gray-900 fw-bold flex-column justify-content-center my-0">Beranda</h3>
    <ul class="breadcrumb breadcrumb-style2">
        <li class="breadcrumb-item active">Beranda</li>
    </ul>

    @if (empty($wsConfigured))
        <div class="alert alert-warning d-flex align-items-center" role="alert">
            <i class="ri-error-warning-line me-2"></i>
            <div>
                Data SIKEU belum aktif: isi <code>JWT_KEY</code> atau <code>WS_AMAL_FATIMAH_JWT_KEY</code> di file <code>.env</code>, lalu jalankan <code>php artisan config:clear</code>.
            </div>
        </div>
    @endif

    <div class="row">
        <div class="col-12 col-lg-8">
            <div class="row row-cols-2 row-cols-md-3 g-4 mb-4">
                <div class="col">
                    <div class="card card-border-shadow-primary bg-label-primary p-4 h-100 text-center">
                        <a href="{{ route('keu.tagihan.buat') }}" class="nav-link btn d-flex flex-column align-items-center justify-content-center">
                            <i class="ri-add-line ri-40px"></i>
                            <h6 class="mt-2 mb-0">Buat Tagihan</h6>
                        </a>
                    </div>
                </div>
                <div class="col">
                    <div class="card card-border-shadow-success bg-label-success p-4 h-100 text-center">
                        <a href="{{ route('keu.manual') }}" class="nav-link btn d-flex flex-column align-items-center justify-content-center">
                            <i class="ri-cash-line ri-40px"></i>
                            <h6 class="mt-2 mb-0">Bayar Manual</h6>
                        </a>
                    </div>
                </div>
                <div class="col">
                    <div class="card card-border-shadow-warning bg-label-warning p-4 h-100 text-center">
                        <a href="{{ route('keu.saldo.va') }}" class="nav-link btn d-flex flex-column align-items-center justify-content-center">
                            <i class="ri-bank-card-line ri-40px"></i>
                            <h6 class="mt-2 mb-0">Saldo VA</h6>
                        </a>
                    </div>
                </div>
                <div class="col">
                    <div class="card card-border-shadow-info bg-label-info p-4 h-100 text-center">
                        <a href="{{ route('keu.tagihan.data') }}" class="nav-link btn d-flex flex-column align-items-center justify-content-center">
                            <i class="ri-archive-stack-line ri-40px"></i>
                            <h6 class="mt-2 mb-0">Data Tagihan</h6>
                        </a>
                    </div>
                </div>
                <div class="col">
                    <div class="card card-border-shadow-success bg-label-whatsapp p-4 h-100 text-center">
                        <a href="{{ route('keu.penerimaan.data') }}" class="nav-link btn d-flex flex-column align-items-center justify-content-center">
                            <i class="ri-receipt-line ri-40px"></i>
                            <h6 class="mt-2 mb-0">Data Penerimaan</h6>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-12 col-lg-6">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title m-0">Pembayaran Baru</h5>
                    <span class="badge bg-label-primary">Terbaru</span>
                </div>
                <div class="card-body">
                    <ul class="timeline pb-0 mb-0">
                        @forelse($pembayaranBaru as $item)
                            @php
                                $bulan = $item['billname'] ?? $item['bulan'] ?? $item['periode'] ?? $item['nama_bulan'] ?? 'BULAN -';
                                $nominal = (int)($item['billam'] ?? $item['nominal'] ?? $item['jumlah'] ?? $item['total'] ?? 0);
                                $nama = $item['nama_cust'] ?? $item['nama'] ?? $item['nama_pembayar'] ?? $item['nama_siswa'] ?? '-';
                                $tgl = $item['paiddt'] ?? $item['tanggal'] ?? $item['tgl_bayar'] ?? $item['tanggal_bayar'] ?? null;
                                $tglFormatted = '';
                                if ($tgl) {
                                    $c = \Carbon\Carbon::parse($tgl);
                                    $tglFormatted = ($hariIndo[$c->format('l')] ?? $c->format('l')) . ', ' . $c->format('d') . ' ' . ($bulanIndo[$c->format('F')] ?? $c->format('F')) . ' ' . $c->format('Y');
                                }
                            @endphp
                            <li class="timeline-item ps-6 border-success border-left-dashed">
                                <span class="timeline-indicator-advanced text-success border-0 shadow-none">
                                    <i class="ri-checkbox-circle-line ri-lg"></i>
                                </span>
                                <div class="timeline-event ps-1">
                                    <div class="timeline-header d-flex justify-content-between gap-2 flex-wrap">
                                        <h6 class="mb-0">{{ $bulan }}</h6>
                                        @if($tglFormatted)<small class="text-muted">{{ $tglFormatted }}</small>@endif
                                    </div>
                                    <h6 class="text-primary mb-1">Rp {{ number_format($nominal, 0, ',', '.') }}</h6>
                                    <p class="mb-0 small text-muted">{{ $nama }}</p>
                                </div>
                            </li>
                        @empty
                            <li class="timeline-item timeline-item-transparent border-transparent">
                                <div class="timeline-event">
                                    <h6 class="mb-0 text-muted">Belum ada data pembayaran baru.</h6>
                                </div>
                            </li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-6">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="card-title m-0">Ringkasan Tagihan</h5>
                </div>
                <div class="card-body">
                    @php
                        $showTagihan = is_array($tagihan) && (isset($tagihan['total']) || isset($tagihan['dibayar']));
                    @endphp
                    @if($showTagihan)
                        @php
                            $total = (int)($tagihan['total'] ?? ($tagihan['dibayar'] ?? 0) + ($tagihan['belum_dibayar'] ?? 0));
                            $dibayar = (int)($tagihan['dibayar'] ?? 0);
                            $belum = (int)($tagihan['belum_dibayar'] ?? 0);
                            $paid = $total > 0 ? round($dibayar / $total * 100, 1) : 0;
                        @endphp
                        <div class="row g-3 mb-3">
                            <div class="col-12">
                                <div class="p-3 border rounded">
                                    <small class="text-muted d-block">Total Tagihan</small>
                                    <h4 class="mb-0">{{ number_format($total, 0, ',', '.') }}</h4>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="p-3 border rounded">
                                    <small class="text-muted d-block">Dibayar</small>
                                    <h5 class="mb-0 text-success">{{ number_format($dibayar, 0, ',', '.') }}</h5>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="p-3 border rounded">
                                    <small class="text-muted d-block">Belum Dibayar</small>
                                    <h5 class="mb-0 text-danger">{{ number_format($belum, 0, ',', '.') }}</h5>
                                </div>
                            </div>
                        </div>
                        <div class="d-flex justify-content-between small mb-1">
                            <span class="text-success">Dibayar {{ number_format($paid, 2) }}%</span>
                            <span class="text-danger">Belum {{ number_format(100 - $paid, 2) }}%</span>
                        </div>
                        <div class="progress" style="height: 8px;">
                            <div class="progress-bar bg-success" style="width: {{ $paid }}%"></div>
                        </div>
                    @else
                        <p class="text-muted mb-0">Data tagihan belum tersedia.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @if(count($tagihanDibayarChart) > 0)
        @php $maxTotal = max(array_column($tagihanDibayarChart, 'total')) ?: 1; @endphp
        <div class="card mt-4">
            <div class="card-header">
                <h5 class="card-title m-0">Tagihan Dibayar</h5>
            </div>
            <div class="card-body">
                <div class="d-flex align-items-end gap-3 flex-wrap" style="min-height: 180px;">
                    @foreach($tagihanDibayarChart as $row)
                        @php $pct = $maxTotal > 0 ? ($row['total'] / $maxTotal) * 100 : 0; @endphp
                        <div class="text-center" style="min-width: 48px;">
                            <div class="bg-primary rounded-top mx-auto" style="width: 32px; height: {{ max(8, $pct) }}px;"></div>
                            <small class="d-block fw-semibold mt-1">{{ number_format($row['total'] ?? 0, 0, ',', '.') }}</small>
                            <small class="text-muted">{{ $row['label'] ?? $row['tanggal'] ?? '-' }}</small>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif
@endsection
