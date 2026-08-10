<div class="col mb-4">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
        <label class="form-label mb-0 fw-semibold">Metode Identifikasi</label>
        <div class="btn-group" role="group" aria-label="Metode identifikasi">
            <input type="radio" class="btn-check" name="input_mode" id="input_mode_qr" value="qr" checked autocomplete="off">
            <label class="btn btn-outline-primary" for="input_mode_qr">
                <i class="ri-qr-code-line me-1"></i> QR
            </label>
            <input type="radio" class="btn-check" name="input_mode" id="input_mode_pid" value="pid" autocomplete="off">
            <label class="btn btn-outline-primary" for="input_mode_pid">
                <i class="ri-bank-card-line me-1"></i> PID
            </label>
        </div>
    </div>
</div>

<div class="col mb-5 input-section input-section-qr" id="inputSectionQr">
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
            <span class="input-group-text" style="width: 120px;">QR / PID</span>
            <input type="text" class="form-control form-control-lg" placeholder="Scan QR atau ketik PID"
                   name="tap_id" id="tap_id" aria-describedby="tap_id" enterkeyhint="done">
        </div>
        <small class="text-muted d-block mt-2">Default: scan QR dari aplikasi siswa.</small>
    </div>
</div>

<div class="col mb-5 input-section input-section-pid" id="inputSectionPid" hidden>
    <div class="input-group">
        <span class="input-group-text" style="width: 120px;">PID</span>
        <input type="text" class="form-control form-control-lg" placeholder="Tap kartu / ketik PID"
               id="tap_id_pid" enterkeyhint="done" inputmode="numeric" autocomplete="off">
    </div>
    <small class="text-muted d-block mt-2">Tap kartu fisik atau ketik nomor PID kartu.</small>
</div>
