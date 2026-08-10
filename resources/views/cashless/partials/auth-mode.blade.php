@php
    $showPinOnLookup = $showPinOnLookup ?? false;
    $showPinField = $showPinField ?? true;
@endphp

<div class="col mb-4">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
        <label class="form-label mb-0 fw-semibold">Metode Identifikasi</label>
        <div class="btn-group" role="group" aria-label="Metode identifikasi">
            <input type="radio" class="btn-check" name="auth_mode" id="auth_mode_qr" value="qr" checked autocomplete="off">
            <label class="btn btn-outline-primary" for="auth_mode_qr">
                <i class="ri-qr-code-line me-1"></i> QR
            </label>
            <input type="radio" class="btn-check" name="auth_mode" id="auth_mode_pin" value="pin" autocomplete="off">
            <label class="btn btn-outline-primary" for="auth_mode_pin">
                <i class="ri-bank-card-line me-1"></i> PIN
            </label>
        </div>
    </div>
</div>

<div class="col mb-4 auth-section auth-section-qr" id="authSectionQr">
    <div class="border rounded p-3 bg-light">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
            <span class="fw-semibold text-primary">Scan QR Code</span>
            <button type="button" class="btn btn-sm btn-primary" id="btnToggleQrScanner">
                <i class="ri-camera-line me-1"></i> Buka Kamera
            </button>
        </div>
        <div id="qrReaderWrap" class="mb-3" hidden>
            <div id="qrReader" class="mx-auto" style="max-width: 320px;"></div>
        </div>
        <div class="input-group">
            <span class="input-group-text auth-tap-label" style="width: 120px;">QR / PID</span>
            <input type="text" class="form-control form-control-lg" placeholder="Scan QR atau ketik PID"
                   name="tap_id" id="tap_id" aria-describedby="tap_id" enterkeyhint="done">
        </div>
        <small class="text-muted d-block mt-2">Default: scan QR dari aplikasi siswa. Bisa juga ketik PID manual.</small>
    </div>
</div>

<div class="col mb-4 auth-section auth-section-pin" id="authSectionPin" hidden>
    <div class="border rounded p-3">
        <div class="input-group mb-3">
            <span class="input-group-text" style="width: 120px;">TAP ID</span>
            <input type="text" class="form-control form-control-lg" placeholder="Tap kartu fisik"
                   id="tap_id_pin_mirror" enterkeyhint="done" inputmode="numeric" autocomplete="off">
        </div>
        @if($showPinField)
            @if($showPinOnLookup)
                <div class="input-group">
                    <span class="input-group-text" style="width: 120px;">PIN</span>
                    <input type="password" class="form-control form-control-lg" placeholder="PIN kartu"
                           name="pin" id="pin" enterkeyhint="done" inputmode="numeric" maxlength="20" autocomplete="off">
                </div>
            @else
                <div class="input-group">
                    <span class="input-group-text" style="width: 120px;">PIN</span>
                    <input type="password" class="form-control form-control-lg" placeholder="PIN kartu (saat bayar)"
                           name="pin" id="pin" enterkeyhint="done" inputmode="numeric" maxlength="20" autocomplete="off">
                </div>
                <small class="text-muted d-block mt-2">Tap kartu untuk isi TAP ID. PIN diisi saat proses belanja.</small>
            @endif
        @else
            <small class="text-muted d-block mt-2">Tap kartu fisik untuk mengisi TAP ID.</small>
        @endif
    </div>
</div>
