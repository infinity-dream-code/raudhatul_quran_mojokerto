@extends('layouts.app')

@section('content')
    <style>
        .eid-card { background:#fff; border:1px solid #e4eaf0; border-radius:14px; box-shadow:0 6px 18px rgba(15,23,42,.06); margin-top:16px; }
        .eid-card-head { display:flex; justify-content:space-between; align-items:center; gap:12px; padding:16px 18px; border-bottom:1px solid #eef2f7; }
        .eid-title { font-size:18px; font-weight:700; margin:0; }
        .eid-toolbar { display:flex; justify-content:space-between; align-items:center; gap:10px; padding:12px 18px; flex-wrap:wrap; border-bottom:1px solid #eef2f7; }
        .eid-toolbar form { display:flex; align-items:center; gap:8px; margin:0; font-size:13px; color:#6b7280; }
        .eid-table-wrap { overflow-x:auto; }
        .eid-table { width:100%; min-width:900px; border-collapse:collapse; font-size:13px; }
        .eid-table th, .eid-table td { border-bottom:1px solid #eef2f7; padding:10px 12px; text-align:left; vertical-align:middle; }
        .eid-table th { background:#fafbfd; color:#4b5563; font-size:12px; font-weight:700; white-space:nowrap; }
        .eid-empty { text-align:center; color:#6b7280; padding:24px 12px; }
        .eid-footer { padding:14px 18px; border-top:1px solid #eef2f7; display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap; }
        .eid-info { font-size:12px; color:#6b7280; }
        .eid-actions { display:flex; gap:10px; flex-wrap:wrap; }
        .eid-alert { margin:12px 18px 0; padding:10px 12px; border-radius:8px; font-size:13px; font-weight:600; }
        .eid-alert.ok { background:#ecfdf5; color:#047857; }
        .eid-alert.err { background:#fef2f2; color:#b91c1c; }
        #modal-import-siswa .list-group-item { border:0; padding:.35rem 0; background:transparent; }
    </style>

    <h3 class="page-heading d-flex text-gray-900 fw-bold flex-column justify-content-center my-0">Export Import Data</h3>
    <ul class="breadcrumb breadcrumb-style2">
        <li class="breadcrumb-item active">Export Import Data</li>
    </ul>

    <div class="eid-card">
        <div class="eid-card-head">
            <h5 class="eid-title">Export Import Data</h5>
            <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#modal-import-siswa">
                <i class="ri-file-excel-2-line me-1"></i> Import Data Siswa
            </button>
        </div>

        @if (session('status'))
            <div class="eid-alert ok">{{ session('status') }}</div>
        @endif
        @if (session('error'))
            <div class="eid-alert err">{{ session('error') }}</div>
        @endif
        @if ($errors->any())
            <div class="eid-alert err">{{ $errors->first() }}</div>
        @endif

        <div class="eid-toolbar">
            <form method="GET" action="{{ route('master.export_import') }}">
                <span>Tampilkan</span>
                <select class="form-select form-select-sm" style="width:auto;" name="per_page" onchange="this.form.submit()">
                    <option value="10" {{ (int) ($perPage ?? 10) === 10 ? 'selected' : '' }}>10</option>
                    <option value="25" {{ (int) ($perPage ?? 10) === 25 ? 'selected' : '' }}>25</option>
                    <option value="50" {{ (int) ($perPage ?? 10) === 50 ? 'selected' : '' }}>50</option>
                </select>
                @if (($keyword ?? '') !== '')
                    <input type="hidden" name="q" value="{{ $keyword }}">
                @endif
                <span>entri</span>
            </form>

            <form method="GET" action="{{ route('master.export_import') }}">
                <input type="hidden" name="per_page" value="{{ (int) ($perPage ?? 10) }}">
                <span>Cari:</span>
                <input type="text" class="form-control form-control-sm" style="width:200px;" name="q" value="{{ $keyword ?? '' }}" placeholder="kata kunci pencarian">
                <button class="btn btn-sm btn-outline-secondary" type="submit">Cari</button>
            </form>
        </div>

        <div class="eid-table-wrap">
            <table class="eid-table table table-hover mb-0">
                <thead>
                    <tr>
                        <th>No</th><th>NIS</th><th>NO PEND</th><th>NAMA</th><th>UNIT</th><th>KELAS</th><th>KELOMPOK</th><th>ANGKATAN</th><th>JENIS KELAMIN</th><th>ALAMAT</th><th>WALI</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse (($importRows ?? []) as $index => $row)
                        <tr>
                            <td>{{ ($importRows->firstItem() ?? 1) + $index }}</td>
                            <td>{{ $row['nis'] ?? '-' }}</td>
                            <td>{{ $row['nodaf'] ?? '-' }}</td>
                            <td>{{ $row['nama'] ?? '-' }}</td>
                            <td>{{ $row['unit'] ?? '-' }}</td>
                            <td>{{ $row['kelas'] ?? '-' }}</td>
                            <td>{{ $row['kelompok'] ?? '-' }}</td>
                            <td>{{ $row['angkatan'] ?? '-' }}</td>
                            <td>{{ $row['gender'] ?? '-' }}</td>
                            <td>{{ $row['alamat'] ?? '-' }}</td>
                            <td>{{ $row['wali'] ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="11" class="eid-empty">Belum ada data preview. Klik <strong>Import Data Siswa</strong> untuk upload file Excel.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if (($importRows->total() ?? 0) > 0)
            <div class="eid-footer">
                <div class="eid-info">Menampilkan {{ $importRows->firstItem() }} sampai {{ $importRows->lastItem() }} dari {{ $importRows->total() }} entri</div>
                <div class="eid-actions">
                    <form method="POST" action="{{ route('master.export_import.clear') }}">@csrf
                        <button type="submit" class="btn btn-outline-danger btn-sm"><i class="ri-delete-bin-line me-1"></i> Bersihkan Data</button>
                    </form>
                    <form method="POST" action="{{ route('master.export_import.save') }}">@csrf
                        <button type="submit" class="btn btn-primary btn-sm"><i class="ri-save-line me-1"></i> Simpan Data</button>
                    </form>
                </div>
            </div>
        @endif
    </div>

    <form method="POST" action="{{ route('master.export_import.import') }}" enctype="multipart/form-data" id="form-import-siswa">
        @csrf
        <input type="hidden" name="preview_rows" id="eidPreviewRows" value="">
        <div class="modal modal-blur fade" id="modal-import-siswa" tabindex="-1" aria-labelledby="modal-import-siswa-label" aria-hidden="true" data-bs-backdrop="static">
            <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modal-import-siswa-label">Import Data Siswa</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                    </div>
                    <div class="modal-body">
                        <ul class="list-group list-group-timeline mb-3">
                            <li class="list-group-item">File harus berformat <strong>XLSX</strong>.</li>
                            <li class="list-group-item">Ukuran file tidak boleh lebih dari <strong>1024KB / 1MB</strong>.</li>
                            <li class="list-group-item">Kolom wajib: <strong>NIS</strong>. Opsional: NODAF, NAMA, UNIT, KELAS, KELOMPOK, ANGKATAN, GENDER, ALAMAT, WALI.</li>
                            <li class="list-group-item">
                                Contoh file:
                                <a href="{{ asset('format_import_data_siswa.csv') }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-primary ms-1">
                                    <i class="ri-file-line me-1"></i> format_import_data_siswa.csv
                                </a>
                            </li>
                        </ul>

                        <fieldset class="form-fieldset mb-0">
                            <label class="form-label required" for="eidFileInput">File Excel</label>
                            <input type="file" class="form-control" name="file" id="eidFileInput" accept=".xlsx,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" required>
                        </fieldset>
                    </div>
                    <div class="modal-footer">
                        <div class="w-100">
                            <div class="row g-2">
                                <div class="col"><button type="button" class="btn btn-outline-secondary w-100" data-bs-dismiss="modal">Batal</button></div>
                                <div class="col"><button type="submit" class="btn btn-primary w-100">Upload &amp; Preview</button></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection

@section('script')
    <script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
    <script>
        (function () {
            var modalEl = document.getElementById('modal-import-siswa');
            var fileInput = document.getElementById('eidFileInput');
            var previewInput = document.getElementById('eidPreviewRows');
            if (!modalEl || !fileInput || !previewInput) return;

            @if ($errors->any())
                bootstrap.Modal.getOrCreateInstance(modalEl).show();
            @endif

            fileInput.addEventListener('change', function (event) {
                var file = event.target.files && event.target.files[0];
                if (!file || typeof XLSX === 'undefined') { previewInput.value = '[]'; return; }
                var reader = new FileReader();
                reader.onload = function (e) {
                    try {
                        var wb = XLSX.read(e.target.result, { type: 'binary' });
                        var ws = wb.Sheets[wb.SheetNames[0]];
                        var rows = XLSX.utils.sheet_to_json(ws, { defval: '' });
                        var normalized = rows.map(function (r) {
                            return {
                                nis: String(r.NIS || '').trim(),
                                nodaf: String(r.NODAF || '').trim(),
                                nama: String(r.Nama || r.NAMA || '').trim(),
                                unit: String(r.UNIT || '').trim(),
                                kelas: String(r.KELAS || '').trim(),
                                kelompok: String(r.KELOMPOK || '').trim(),
                                angkatan: String(r.ANGKATAN || '').trim(),
                                gender: String(r.GENDER || '').trim(),
                                alamat: String(r.ALAMAT || '').trim(),
                                wali: String(r.WALI || r.Wali || '').trim()
                            };
                        });
                        previewInput.value = JSON.stringify(normalized);
                    } catch (err) { previewInput.value = '[]'; }
                };
                reader.readAsBinaryString(file);
            });

            modalEl.addEventListener('hidden.bs.modal', function () {
                var form = document.getElementById('form-import-siswa');
                if (form) form.reset();
                previewInput.value = '[]';
            });
        })();
    </script>
@endsection
