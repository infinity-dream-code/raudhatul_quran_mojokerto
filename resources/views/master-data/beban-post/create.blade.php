@extends('layouts.app')

@section('content')
    <style>
        .bp-form-card { width: 100%; background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; box-shadow: 0 8px 24px rgba(15, 23, 42, 0.08); }
        .bp-form-body { padding: 18px; }
        .bp-field-wrap { display: grid; gap: 10px; padding: 12px; border: 1px solid #eef2f7; border-radius: 10px; background: #fafafa; }
        .bp-label { display: block; font-size: 13px; font-weight: 700; margin-bottom: 6px; color: #374151; }
        .bp-required { color: #ef4444; }
        .bp-input { width: 100%; height: 42px; border: 1px solid #d1d5db; border-radius: 8px; padding: 0 12px; outline: none; font-size: 14px; background: #fff; color: #374151; }
        .bp-actions { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-top: 14px; }
        .bp-btn { height: 42px; border-radius: 8px; font-size: 14px; font-weight: 700; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; justify-content: center; }
        .bp-btn-cancel { border: 1px solid #d1d5db; background: #fff; color: #4b5563; }
        .bp-btn-save { border: 1px solid #4f6ef7; background: #4f6ef7; color: #fff; }
        .bp-error-box { margin-top: 12px; margin-bottom: 12px; padding: 10px 12px; border-radius: 8px; background: #fef2f2; color: #b91c1c; font-size: 13px; }
    </style>

    <div class="page-heading">
        <h2>Tambah Beban Post</h2>
        <p>Isi data Tahun Angkatan, Kelas, Kode Akun, dan Nominal.</p>
    </div>

    <div class="bp-form-card">
        <div class="bp-form-body">
            <form method="POST" action="{{ route('master.beban_post.store') }}">
                @csrf
                @if ($errors->any())
                    <div class="bp-error-box">{{ $errors->first() }}</div>
                @endif
                <div class="bp-field-wrap">
                    <div>
                        <label class="bp-label">Tahun Angkatan <span class="bp-required">*</span></label>
                        <select name="thn_masuk" class="bp-input" required>
                            <option value="">Pilih Tahun Angkatan</option>
                            @foreach (($thnAkaOptions ?? []) as $thn)
                                @php
                                    $label = is_array($thn)
                                        ? (string) ($thn['thn_masuk'] ?? $thn['THN_MASUK'] ?? $thn['thn_aka'] ?? $thn['THN_AKA'] ?? '')
                                        : (string) $thn;
                                @endphp
                                @if ($label !== '')
                                    <option value="{{ $label }}" {{ old('thn_masuk') === $label ? 'selected' : '' }}>{{ $label }}</option>
                                @endif
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="bp-label">Kelas <span class="bp-required">*</span></label>
                        <select name="kode_prod" class="bp-input" required>
                            <option value="">Pilih Kelas</option>
                            @foreach (($kelasOptions ?? []) as $kls)
                                @php
                                    $kp = (string) ($kls['id'] ?? '');
                                    $un = (string) ($kls['unit'] ?? '');
                                    $klKelas = (string) ($kls['jenjang'] ?? '');
                                    $klKelompok = (string) ($kls['kelompok'] ?? $kls['kelas'] ?? '');
                                    $parts = array_values(array_filter([$un, $klKelas, $klKelompok], static fn ($v) => $v !== ''));
                                    $label = implode(' - ', $parts);
                                @endphp
                                @if ($kp !== '' && $label !== '')
                                    <option value="{{ $kp }}" {{ old('kode_prod') === $kp ? 'selected' : '' }}>{{ $label }}</option>
                                @endif
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="bp-label">Kode Akun <span class="bp-required">*</span></label>
                        <select name="kode_akun" class="bp-input" required>
                            <option value="">Pilih Kode Akun</option>
                            @foreach (($akunOptions ?? []) as $akn)
                                @php
                                    $ka = (string) ($akn['KodeAkun'] ?? $akn['kodeakun'] ?? '');
                                    $na = (string) ($akn['NamaAkun'] ?? $akn['namaakun'] ?? '');
                                @endphp
                                <option value="{{ $ka }}" {{ old('kode_akun') === $ka ? 'selected' : '' }}>{{ $ka . ($na !== '' ? ' - '.$na : '') }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="bp-label">Nominal <span class="bp-required">*</span></label>
                        <input name="nominal" type="text" class="bp-input" value="{{ old('nominal') }}" placeholder="Rp. Nominal" required>
                    </div>
                </div>

                <div class="bp-actions">
                    <a class="bp-btn bp-btn-cancel" href="{{ route('master.beban_post') }}">Batal</a>
                    <button class="bp-btn bp-btn-save" type="submit">Simpan Data</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        (function () {
            var nominalInput = document.querySelector('input[name="nominal"]');
            if (!nominalInput) return;

            var formatRibuan = function (value) {
                var digits = (value || '').replace(/\D+/g, '');
                if (!digits) return '';
                return digits.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
            };

            nominalInput.addEventListener('input', function () {
                nominalInput.value = formatRibuan(nominalInput.value);
            });

            nominalInput.value = formatRibuan(nominalInput.value);
        })();
    </script>
@endsection

