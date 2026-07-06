@extends('layouts.app')

@section('content')
    <div class="sc-page tar-page">
        <div class="page-heading sc-page-heading">
            <h2>TAP Ambil Rutin</h2>
            <p>Smartcard / Pengeluaran uang saku via tap kartu</p>
        </div>

        <div class="card sc-card tar-card">
            <div class="sc-card-body">
                <div class="tar-title">PENGELUARAN UANG SAKU / AMBIL CASH</div>

                <form id="tarForm" autocomplete="off">
                    @csrf
                    <div class="tar-panel">
                        <div class="tar-row">
                            <div class="tar-label">TAP ID</div>
                            <div class="tar-value">
                                <input type="text" id="tapId" name="tap_id" class="tar-input" inputmode="numeric" autofocus>
                            </div>
                        </div>
                        <div class="tar-row">
                            <div class="tar-label">Nama</div>
                            <div class="tar-value tar-value-readonly">
                                <input type="text" id="nama" name="nama" class="tar-input tar-input-readonly" readonly tabindex="-1">
                            </div>
                        </div>
                        <div class="tar-row">
                            <div class="tar-label">SALDO</div>
                            <div class="tar-value tar-value-saldo">
                                <input type="text" id="saldo" class="tar-input tar-input-saldo" readonly tabindex="-1" value="0">
                            </div>
                        </div>
                        <div class="tar-row">
                            <div class="tar-label tar-label-ambil">AMBIL</div>
                            <div class="tar-value tar-value-ambil">
                                <input type="text" id="ambil" name="ambil" class="tar-input tar-input-ambil" inputmode="numeric" placeholder="0">
                            </div>
                        </div>
                        <div class="tar-row tar-row-end">
                            <div class="tar-label">PIN</div>
                            <div class="tar-value">
                                <input type="password" id="pin" name="pin" class="tar-input" inputmode="numeric" maxlength="20">
                            </div>
                        </div>
                    </div>
                    <input type="hidden" id="maxAmbil" value="0">
                    <input type="hidden" id="batasCash" value="0">
                </form>

                <div id="tarInfo" class="tar-info" hidden>
                    Batas cash: <strong id="tarInfoBatas">0</strong>
                    &nbsp;|&nbsp; Maks. ambil: <strong id="tarInfoMax">0</strong>
                </div>
            </div>
        </div>
    </div>

    @include('smartcard.partials.styles')

    <style>
        .tar-page { max-width: 720px; margin: 0 auto; }
        .tar-card { border: 2px solid #c4b5fd; }
        .tar-title {
            font-family: 'Sora', sans-serif;
            font-size: 20px;
            font-weight: 800;
            color: #5b21b6;
            text-align: center;
            margin-bottom: 24px;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }
        .tar-panel {
            border: 2px solid #7c3aed;
            border-radius: 4px;
            overflow: hidden;
            background: #fff;
        }
        .tar-row {
            display: grid;
            grid-template-columns: 160px 1fr;
            border-bottom: 2px solid #7c3aed;
            min-height: 72px;
        }
        .tar-row-end { border-bottom: 0; }
        .tar-label {
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            font-weight: 800;
            color: #fff;
            background: linear-gradient(180deg, #7c3aed 0%, #6d28d9 100%);
            text-transform: uppercase;
            letter-spacing: 0.06em;
            padding: 12px;
        }
        .tar-label-ambil { color: #fef08a; }
        .tar-value {
            display: flex;
            align-items: center;
            background: #e0f2fe;
            padding: 8px 16px;
        }
        .tar-value-readonly { background: #f0f9ff; }
        .tar-value-saldo { background: #fff; }
        .tar-value-ambil { background: #fff; }
        .tar-input {
            width: 100%;
            border: 0;
            background: transparent;
            font-size: 28px;
            font-weight: 700;
            color: #1e3a8a;
            outline: none;
            font-family: 'Sora', sans-serif;
        }
        .tar-input-readonly {
            font-size: 22px;
            font-weight: 600;
            color: #1f2937;
        }
        .tar-input-saldo {
            font-size: 42px;
            font-weight: 800;
            color: #dc2626;
            text-align: left;
        }
        .tar-input-ambil {
            font-size: 42px;
            font-weight: 800;
            color: #2563eb;
        }
        .tar-info {
            margin-top: 16px;
            padding: 12px 16px;
            background: #faf5ff;
            border: 1px solid #e9d5ff;
            border-radius: 10px;
            font-size: 14px;
            color: #5b21b6;
            text-align: center;
        }
        @media (max-width: 600px) {
            .tar-row { grid-template-columns: 110px 1fr; min-height: 60px; }
            .tar-label { font-size: 16px; }
            .tar-input-saldo, .tar-input-ambil { font-size: 32px; }
        }
    </style>

    <script>
        (function () {
            const lookupUrl = @json(route('smartcard.tap_ambil_rutin.lookup'));
            const processUrl = @json(route('smartcard.tap_ambil_rutin.process'));
            const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

            const tapId = document.getElementById('tapId');
            const nama = document.getElementById('nama');
            const saldo = document.getElementById('saldo');
            const ambil = document.getElementById('ambil');
            const pin = document.getElementById('pin');
            const maxAmbil = document.getElementById('maxAmbil');
            const batasCash = document.getElementById('batasCash');
            const tarInfo = document.getElementById('tarInfo');
            const tarInfoBatas = document.getElementById('tarInfoBatas');
            const tarInfoMax = document.getElementById('tarInfoMax');
            const form = document.getElementById('tarForm');

            let cardLoaded = false;

            const formatRp = function (n) {
                return String(Math.max(0, parseInt(n, 10) || 0)).replace(/\B(?=(\d{3})+(?!\d))/g, '.');
            };
            const parseNum = function (v) {
                return parseInt(String(v || '').replace(/\D/g, ''), 10) || 0;
            };
            const alertMsg = function (msg, focusEl) {
                alert(msg);
                if (focusEl) focusEl.focus();
            };

            const resetCard = function () {
                cardLoaded = false;
                nama.value = '';
                saldo.value = '0';
                ambil.value = '';
                pin.value = '';
                maxAmbil.value = '0';
                batasCash.value = '0';
                tarInfo.hidden = true;
            };

            const doLookup = async function () {
                const id = tapId.value.trim();
                if (!id) {
                    resetCard();
                    return;
                }

                try {
                    const res = await fetch(lookupUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrf,
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: JSON.stringify({ tap_id: id }),
                        credentials: 'same-origin',
                    });
                    const json = await res.json();

                    if (!json.ok) {
                        resetCard();
                        alertMsg(json.message || 'Gagal membaca kartu.', tapId);
                        return;
                    }

                    const d = json.data || {};
                    cardLoaded = true;
                    nama.value = d.nama || '';
                    saldo.value = formatRp(d.saldo || 0);
                    maxAmbil.value = String(d.max_ambil || 0);
                    batasCash.value = String(d.batas_cash || 0);
                    tarInfoBatas.textContent = formatRp(d.batas_cash || 0);
                    tarInfoMax.textContent = formatRp(d.max_ambil || 0);
                    tarInfo.hidden = false;
                    ambil.focus();
                } catch (e) {
                    resetCard();
                    alertMsg('Gagal menghubungi server.', tapId);
                }
            };

            const doProcess = async function () {
                const id = tapId.value.trim();
                const nominal = parseNum(ambil.value);
                const pinVal = pin.value.trim();
                const maxVal = parseNum(maxAmbil.value);
                const saldoVal = parseNum(saldo.value);

                if (!id || !cardLoaded) {
                    alertMsg('Tap kartu terlebih dahulu.', tapId);
                    return;
                }
                if (!nominal) {
                    alertMsg('Isi nominal AMBIL.', ambil);
                    return;
                }
                if (!pinVal) {
                    alertMsg('Isi PIN kartu.', pin);
                    return;
                }
                if (nominal > saldoVal) {
                    alertMsg('Saldo tidak mencukupi.', ambil);
                    return;
                }
                if (maxVal > 0 && nominal > maxVal) {
                    alertMsg('Nominal melebihi batas cash / saldo.', ambil);
                    return;
                }

                try {
                    const res = await fetch(processUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrf,
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: JSON.stringify({
                            tap_id: id,
                            ambil: String(nominal),
                            pin: pinVal,
                        }),
                        credentials: 'same-origin',
                    });
                    const json = await res.json();

                    if (!json.ok) {
                        if (json.pin_error) {
                            alertMsg(json.message || 'PIN salah.', pin);
                        } else if (json.blocked) {
                            alertMsg(json.message || 'Kartu terblokir.', tapId);
                            resetCard();
                            tapId.value = '';
                        } else {
                            alertMsg(json.message || 'Transaksi gagal.', ambil);
                        }
                        return;
                    }

                    const d = json.data || {};
                    alert(
                        (json.message || 'Berhasil') + '\n' +
                        'Nama: ' + (d.nama || '') + '\n' +
                        'Ambil: Rp ' + formatRp(d.ambil || 0) + '\n' +
                        'Saldo baru: Rp ' + formatRp(d.saldo_baru || 0)
                    );

                    form.reset();
                    resetCard();
                    tapId.focus();
                } catch (e) {
                    alertMsg('Gagal memproses transaksi.', ambil);
                }
            };

            tapId.addEventListener('keydown', function (e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    if (!cardLoaded) {
                        doLookup();
                    } else if (!ambil.value.trim()) {
                        doLookup();
                    }
                }
            });

            tapId.addEventListener('blur', function () {
                if (tapId.value.trim() && !cardLoaded) {
                    doLookup();
                }
            });

            ambil.addEventListener('input', function () {
                const raw = parseNum(ambil.value);
                ambil.value = raw > 0 ? formatRp(raw) : '';
            });

            pin.addEventListener('keydown', function (e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    doProcess();
                }
            });

            ambil.addEventListener('keydown', function (e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    pin.focus();
                }
            });

            form.addEventListener('submit', function (e) {
                e.preventDefault();
                const id = tapId.value.trim();
                const nominal = parseNum(ambil.value);

                if (id && !cardLoaded) {
                    doLookup();
                    return;
                }
                if (id && cardLoaded && nominal > 0) {
                    doProcess();
                    return;
                }
                if (id) {
                    doLookup();
                }
            });
        })();
    </script>
@endsection
