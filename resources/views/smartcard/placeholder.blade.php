@extends('layouts.app')

@section('content')
    <div class="sc-page">
        <div class="page-heading sc-page-heading">
            <h2>{{ $title ?? 'Smartcard' }}</h2>
            <p>Smartcard / {{ $subtitle ?? '' }}</p>
        </div>

        <div class="card sc-card">
            <div class="sc-card-body">
                <div class="sc-placeholder">
                    <div class="sc-placeholder-icon">🚧</div>
                    <h3>{{ $title ?? 'Halaman' }}</h3>
                    <p>Halaman ini masih kosong dan akan disesuaikan kemudian.</p>
                </div>
            </div>
        </div>
    </div>

    @include('smartcard.partials.styles')

    <style>
        .sc-placeholder {
            text-align: center;
            padding: 48px 24px;
            color: #6b7280;
        }
        .sc-placeholder-icon {
            font-size: 42px;
            margin-bottom: 12px;
        }
        .sc-placeholder h3 {
            font-size: 18px;
            font-weight: 800;
            color: #374151;
            margin: 0 0 8px;
        }
        .sc-placeholder p {
            margin: 0;
            font-size: 14px;
        }
    </style>
@endsection
