<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script type="text/javascript" defer>
    window.CashlessQrPid = (function () {
        let qrScanner = null;
        let scannerActive = false;

        function getMode() {
            return document.querySelector('input[name="input_mode"]:checked')?.value || 'qr';
        }

        function getTapInput() {
            return document.getElementById('tap_id');
        }

        function getPidInput() {
            return document.getElementById('tap_id_pid');
        }

        function parseQrContent(raw) {
            const value = (raw || '').trim();
            if (!value) return '';

            if (value.startsWith('{') || value.startsWith('[')) {
                try {
                    const json = JSON.parse(value);
                    for (const key of ['tap_id', 'pid', 'PID', 'TAP_ID', 'id', 'card_id']) {
                        if (json[key]) return String(json[key]).trim();
                    }
                } catch (e) {}
            }

            const urlMatch = value.match(/[?&](?:tap_id|pid|id)=([^&]+)/i);
            if (urlMatch) return decodeURIComponent(urlMatch[1]).trim();
            if (value.includes('|')) return value.split('|')[0].trim();

            return value;
        }

        function setTapId(value) {
            const pid = parseQrContent(value);
            const tapInput = getTapInput();
            const pidInput = getPidInput();
            if (tapInput) tapInput.value = pid;
            if (pidInput) pidInput.value = pid;
            return pid;
        }

        function syncTapId() {
            const mode = getMode();
            const tapInput = getTapInput();
            const pidInput = getPidInput();
            if (!tapInput) return;

            if (mode === 'pid' && pidInput) {
                tapInput.value = pidInput.value;
            } else if (mode === 'qr' && pidInput) {
                pidInput.value = tapInput.value;
            }
        }

        function focusPrimaryInput() {
            if (getMode() === 'qr') {
                getTapInput()?.focus();
            } else {
                getPidInput()?.focus();
            }
        }

        function applyMode(mode) {
            const qrSection = document.getElementById('inputSectionQr');
            const pidSection = document.getElementById('inputSectionPid');

            if (qrSection) qrSection.hidden = mode !== 'qr';
            if (pidSection) pidSection.hidden = mode !== 'pid';

            if (mode === 'pid') {
                stopScanner();
            }

            syncTapId();
            focusPrimaryInput();
        }

        async function startScanner() {
            const wrap = document.getElementById('qrReaderWrap');
            const btn = document.getElementById('btnToggleQrScanner');
            if (!wrap || typeof Html5Qrcode === 'undefined') return;

            if (scannerActive) {
                stopScanner();
                return;
            }

            wrap.hidden = false;
            if (!qrScanner) {
                qrScanner = new Html5Qrcode('qrReader');
            }

            try {
                await qrScanner.start(
                    { facingMode: 'environment' },
                    { fps: 10, qrbox: { width: 250, height: 250 } },
                    (decodedText) => {
                        const pid = setTapId(decodedText);
                        if (pid) {
                            stopScanner();
                            document.getElementById('form-data')?.requestSubmit();
                        }
                    },
                    () => {}
                );
                scannerActive = true;
                if (btn) btn.innerHTML = '<i class="ri-close-line me-1"></i> Tutup Kamera';
            } catch (err) {
                wrap.hidden = true;
                if (typeof warningAlert === 'function') {
                    warningAlert('Tidak dapat membuka kamera. Pastikan izin kamera aktif atau ketik PID manual.');
                }
            }
        }

        function stopScanner() {
            const wrap = document.getElementById('qrReaderWrap');
            const btn = document.getElementById('btnToggleQrScanner');
            if (qrScanner && scannerActive) {
                qrScanner.stop().then(() => qrScanner.clear()).catch(() => {});
                scannerActive = false;
            }
            if (wrap) wrap.hidden = true;
            if (btn) btn.innerHTML = '<i class="ri-camera-line me-1"></i> Buka Kamera';
        }

        function init() {
            document.querySelectorAll('input[name="input_mode"]').forEach((radio) => {
                radio.addEventListener('change', () => applyMode(getMode()));
            });

            document.getElementById('btnToggleQrScanner')?.addEventListener('click', startScanner);

            getTapInput()?.addEventListener('input', () => {
                if (getMode() === 'qr') {
                    const pidInput = getPidInput();
                    if (pidInput) pidInput.value = getTapInput().value;
                }
            });

            getPidInput()?.addEventListener('input', () => {
                if (getMode() === 'pid') syncTapId();
            });

            getPidInput()?.addEventListener('keydown', (e) => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    syncTapId();
                    document.getElementById('form-data')?.requestSubmit();
                }
            });

            getTapInput()?.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' && getMode() === 'qr') {
                    e.preventDefault();
                    document.getElementById('form-data')?.requestSubmit();
                }
            });

            applyMode(getMode());
        }

        return { init, getMode, syncTapId, setTapId, focusPrimaryInput, stopScanner };
    })();

    document.addEventListener('DOMContentLoaded', function () {
        window.CashlessQrPid?.init();
    });
</script>
