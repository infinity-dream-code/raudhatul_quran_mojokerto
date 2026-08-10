<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script type="text/javascript" defer>
    window.CashlessAuthMode = (function () {
        let qrScanner = null;
        let scannerActive = false;

        function getMode() {
            return document.querySelector('input[name="auth_mode"]:checked')?.value || 'qr';
        }

        function getTapInput() {
            return document.getElementById('tap_id');
        }

        function getPinInput() {
            return document.getElementById('pin');
        }

        function getPinMirrorInput() {
            return document.getElementById('tap_id_pin_mirror');
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

        function syncTapIdFromMode() {
            const mode = getMode();
            const tapInput = getTapInput();
            const pinMirror = getPinMirrorInput();
            if (!tapInput) return;

            if (mode === 'pin' && pinMirror) {
                tapInput.value = pinMirror.value;
            } else if (mode === 'qr' && pinMirror) {
                pinMirror.value = tapInput.value;
            }
        }

        function setTapId(value) {
            const tapId = parseQrContent(value);
            const tapInput = getTapInput();
            const pinMirror = getPinMirrorInput();
            if (tapInput) tapInput.value = tapId;
            if (pinMirror) pinMirror.value = tapId;
            return tapId;
        }

        function focusPrimaryInput() {
            const mode = getMode();
            if (mode === 'qr') {
                getTapInput()?.focus();
            } else {
                getPinMirrorInput()?.focus();
            }
        }

        function applyMode(mode) {
            const qrSection = document.getElementById('authSectionQr');
            const pinSection = document.getElementById('authSectionPin');
            const tapInput = getTapInput();

            if (qrSection) qrSection.hidden = mode !== 'qr';
            if (pinSection) pinSection.hidden = mode !== 'pin';

            if (tapInput) {
                tapInput.required = true;
            }

            if (mode === 'pin') {
                stopScanner();
            } else {
                getPinInput()?.value && (getPinInput().value = '');
            }

            syncTapIdFromMode();
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
                        const tapId = setTapId(decodedText);
                        if (tapId) {
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
                qrScanner.stop().then(() => {
                    qrScanner.clear();
                }).catch(() => {});
                scannerActive = false;
            }
            if (wrap) wrap.hidden = true;
            if (btn) btn.innerHTML = '<i class="ri-camera-line me-1"></i> Buka Kamera';
        }

        function init() {
            document.querySelectorAll('input[name="auth_mode"]').forEach((radio) => {
                radio.addEventListener('change', () => applyMode(getMode()));
            });

            document.getElementById('btnToggleQrScanner')?.addEventListener('click', startScanner);

            getTapInput()?.addEventListener('input', () => {
                if (getMode() === 'qr') {
                    const pinMirror = getPinMirrorInput();
                    if (pinMirror) pinMirror.value = getTapInput().value;
                }
            });

            getPinMirrorInput()?.addEventListener('input', () => {
                if (getMode() === 'pin') syncTapIdFromMode();
            });

            getPinMirrorInput()?.addEventListener('keydown', (e) => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    syncTapIdFromMode();
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

        return {
            init,
            getMode,
            syncTapIdFromMode,
            setTapId,
            focusPrimaryInput,
            stopScanner,
        };
    })();

    document.addEventListener('DOMContentLoaded', function () {
        if (window.CashlessAuthMode) {
            window.CashlessAuthMode.init();
        }
    });
</script>
