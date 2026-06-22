@extends('layouts.app')

@section('content')
    <style>
        .mk-card {
            background: #fff;
            border: 1px solid #e4eaf0;
            border-radius: 14px;
            box-shadow: 0 6px 18px rgba(15, 23, 42, 0.06);
            margin-top: 16px;
        }

        .mk-card-head {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            padding: 16px 18px 8px;
        }

        .mk-title {
            font-family: 'Sora', sans-serif;
            font-size: 18px;
            font-weight: 700;
        }

        .mk-btn-create {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: #4f6ef7;
            color: #fff;
            border: 0;
            border-radius: 8px;
            padding: 8px 14px;
            font-size: 13px;
            font-weight: 700;
            text-decoration: none;
            cursor: pointer;
        }

        .mk-toolbar {
            padding: 8px 18px 14px;
            display: flex;
            justify-content: flex-end;
        }

        .mk-search {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            color: #6b7280;
        }

        .mk-search input {
            width: 220px;
            height: 34px;
            border: 1px solid #e5e7eb;
            border-radius: 7px;
            padding: 0 10px;
            outline: none;
            font-size: 12px;
        }

        .mk-search input:focus {
            border-color: #4f6ef7;
        }

        .mk-table-wrap {
            overflow-x: auto;
            border-top: 1px solid #eef2f7;
        }

        .mk-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 700px;
        }

        .mk-table th,
        .mk-table td {
            border-bottom: 1px solid #eef2f7;
            padding: 10px 12px;
            font-size: 13px;
            text-align: left;
            vertical-align: middle;
        }

        .mk-table th {
            color: #4b5563;
            font-weight: 700;
            background: #fafbfd;
        }

        .mk-col-no {
            width: 56px;
            text-align: center;
        }

        .mk-col-action {
            width: 130px;
            text-align: center;
        }

        .mk-delete-form {
            display: inline;
        }

        .mk-btn-delete {
            border: 0;
            border-radius: 6px;
            background: #ef4444;
            color: #fff;
            font-size: 12px;
            font-weight: 700;
            padding: 6px 12px;
            cursor: pointer;
        }

        .mk-empty {
            text-align: center;
            color: #6b7280;
            padding: 20px 12px;
        }

        .mk-pagination-wrap {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            padding: 14px 18px 18px;
        }

        .mk-pagination-info {
            font-size: 12px;
            color: #6b7280;
        }

        .mk-pagination {
            display: flex;
            align-items: center;
            gap: 6px;
            flex-wrap: wrap;
            justify-content: flex-end;
        }

        .mk-page-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 30px;
            height: 30px;
            padding: 0 10px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            text-decoration: none;
            color: #374151;
            font-size: 12px;
            font-weight: 600;
            background: #fff;
        }

        .mk-page-link:hover {
            border-color: #4f6ef7;
            color: #4f6ef7;
        }

        .mk-page-link.active {
            background: #4f6ef7;
            color: #fff;
            border-color: #4f6ef7;
        }

        .mk-page-link.disabled {
            color: #9ca3af;
            border-color: #e5e7eb;
            pointer-events: none;
            background: #f9fafb;
        }

        @media (max-width: 640px) {
            .mk-pagination-wrap {
                flex-direction: column;
                align-items: flex-start;
            }
        }

        .mk-alert {
            margin: 0 18px 12px;
            padding: 10px 12px;
            border-radius: 8px;
            background: #ecfdf5;
            color: #047857;
            font-size: 13px;
            font-weight: 600;
        }

        .mk-alert-error {
            background: #fef2f2;
            color: #b91c1c;
        }

        #modal-create-kelas .form-label.required::after {
            content: ' *';
            color: #ef4444;
        }

        #modal-create-kelas .form-fieldset {
            border: 1px solid #eef2f7;
            border-radius: 10px;
            padding: 16px;
            background: #fafbfd;
        }

        #modal-create-kelas .modal-error {
            margin-bottom: 12px;
            padding: 10px 12px;
            border-radius: 8px;
            background: #fef2f2;
            color: #b91c1c;
            font-size: 13px;
        }
    </style>

    <div class="page-heading">
        <h2>Master Kelas</h2>
        <p>Data master kelas.</p>
    </div>

    <div class="mk-card">
        <div class="mk-card-head">
            <div class="mk-title">Master Kelas</div>
            <button type="button" class="mk-btn-create" data-bs-toggle="modal" data-bs-target="#modal-create-kelas">
                + Buat Data
            </button>
        </div>

        @if (session('status'))
            <div class="mk-alert">{{ session('status') }}</div>
        @endif
        @if (session('error'))
            <div class="mk-alert mk-alert-error">{{ session('error') }}</div>
        @endif

        <div class="mk-toolbar">
            <form method="GET" action="{{ route('master.kelas') }}" class="mk-search">
                <span>Cari:</span>
                <input type="text" name="q" value="{{ $keyword ?? '' }}" placeholder="kata kunci pencarian">
            </form>
        </div>

        <div class="mk-table-wrap">
            <table class="mk-table">
                <thead>
                    <tr>
                        <th class="mk-col-no">No</th>
                        <th>Unit</th>
                        <th>Kelas</th>
                        <th>Kelompok</th>
                        <th class="mk-col-action"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse (($kelasRows ?? []) as $index => $row)
                        <tr>
                            <td class="mk-col-no">{{ ($kelasRows->firstItem() ?? 1) + $index }}</td>
                            <td>{{ $row['unit'] ?? '-' }}</td>
                            <td>{{ trim((string) ($row['jenjang'] ?? '')) !== '' ? $row['jenjang'] : '-' }}</td>
                            <td>{{ trim((string) ($row['kelas'] ?? '')) !== '' ? $row['kelas'] : '-' }}</td>
                            <td class="mk-col-action">
                                <form method="POST" action="{{ route('master.kelas.destroy', ['id' => $row['id'] ?? 0]) }}" class="mk-delete-form" onsubmit="return confirm('Yakin hapus data kelas ini?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="mk-btn-delete">Hapus</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="mk-empty">Data kelas tidak ditemukan.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if (isset($kelasRows) && method_exists($kelasRows, 'hasPages') && $kelasRows->hasPages())
            <div class="mk-pagination-wrap">
                <div class="mk-pagination-info">
                    Showing {{ $kelasRows->firstItem() }} to {{ $kelasRows->lastItem() }} of {{ $kelasRows->total() }} results
                </div>

                <div class="mk-pagination">
                    @php
                        $current = $kelasRows->currentPage();
                        $last = $kelasRows->lastPage();
                        $start = max(1, $current - 2);
                        $end = min($last, $current + 2);
                    @endphp

                    @if ($kelasRows->onFirstPage())
                        <span class="mk-page-link disabled">Prev</span>
                    @else
                        <a class="mk-page-link" href="{{ $kelasRows->appends(request()->query())->url($current - 1) }}">Prev</a>
                    @endif

                    @for ($page = $start; $page <= $end; $page++)
                        @if ($page === $current)
                            <span class="mk-page-link active">{{ $page }}</span>
                        @else
                            <a class="mk-page-link" href="{{ $kelasRows->appends(request()->query())->url($page) }}">{{ $page }}</a>
                        @endif
                    @endfor

                    @if ($kelasRows->hasMorePages())
                        <a class="mk-page-link" href="{{ $kelasRows->appends(request()->query())->url($current + 1) }}">Next</a>
                    @else
                        <span class="mk-page-link disabled">Next</span>
                    @endif
                </div>
            </div>
        @endif
    </div>

    <form method="POST" action="{{ route('master.kelas.store') }}" id="form-create-kelas">
        @csrf
        <div class="modal modal-blur fade" id="modal-create-kelas" tabindex="-1" aria-labelledby="modal-create-kelas-label" aria-hidden="true" data-bs-backdrop="static">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modal-create-kelas-label">Tambah Data Master Kelas</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                    </div>
                    <div class="modal-body py-4">
                        @if ($errors->any())
                            <div class="modal-error">{{ $errors->first() }}</div>
                        @endif

                        <fieldset class="form-fieldset">
                            <div class="mb-3">
                                <label class="form-label required" for="unit">Unit</label>
                                <input type="text" class="form-control" id="unit" name="unit" value="{{ old('unit') }}" placeholder="Unit" required autocomplete="off">
                            </div>
                            <div class="mb-3">
                                <label class="form-label required" for="kelas">Kelas</label>
                                <input type="text" class="form-control" id="kelas" name="kelas" value="{{ old('kelas') }}" placeholder="Kelas" required autocomplete="off">
                            </div>
                            <div class="mb-0">
                                <label class="form-label required" for="kelompok">Kelompok</label>
                                <input type="text" class="form-control" id="kelompok" name="kelompok" value="{{ old('kelompok') }}" placeholder="Kelompok" required autocomplete="off">
                            </div>
                        </fieldset>
                    </div>
                    <div class="modal-footer">
                        <div class="w-100">
                            <div class="row g-2">
                                <div class="col">
                                    <button type="button" class="btn btn-outline-secondary w-100" data-bs-dismiss="modal">Batal</button>
                                </div>
                                <div class="col">
                                    <button type="submit" class="btn btn-primary w-100">Simpan Data</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>

    <script>
        (function () {
            var modalEl = document.getElementById('modal-create-kelas');
            if (!modalEl) return;

            @if (session('openCreateModal') || $errors->any())
                bootstrap.Modal.getOrCreateInstance(modalEl).show();
            @endif

            modalEl.addEventListener('hidden.bs.modal', function () {
                var form = document.getElementById('form-create-kelas');
                if (form) form.reset();
            });
        })();
    </script>
@endsection

