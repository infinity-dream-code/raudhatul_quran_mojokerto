<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Kuitansi Uang Saku</title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            color: #111;
            margin: 0;
            padding: 24px 28px;
        }
        .header-right {
            text-align: right;
            font-weight: bold;
            font-size: 12px;
            margin-bottom: 8px;
        }
        .title {
            text-align: center;
            font-weight: bold;
            font-size: 14px;
            margin: 12px 0 16px;
            border-top: 1px solid #111;
            border-bottom: 1px solid #111;
            padding: 6px 0;
        }
        table.info {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 14px;
        }
        table.info td {
            padding: 3px 0;
            vertical-align: top;
        }
        table.info .lbl { width: 28%; }
        table.trx {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0 18px;
        }
        table.trx th, table.trx td {
            border: 1px solid #111;
            padding: 6px 8px;
        }
        table.trx th { text-align: left; }
        .footer {
            margin-top: 24px;
            width: 100%;
        }
        .footer td { vertical-align: top; padding-top: 8px; }
        .sign-line {
            margin-top: 48px;
            border-top: 1px dotted #333;
            width: 180px;
            text-align: center;
            padding-top: 4px;
        }
    </style>
</head>
<body>
    <div class="header-right">{{ $sekolahNama ?? 'Sekolah' }}</div>
    <div class="title">UANG SAKU</div>

    <table class="info">
        <tr>
            <td class="lbl">Nama</td>
            <td>: {{ $nama ?? '—' }}</td>
            <td class="lbl">Unit</td>
            <td>: {{ $unit ?? '—' }}</td>
        </tr>
        <tr>
            <td class="lbl">NIS, NO VA</td>
            <td>: {{ $nis ?? '—' }}</td>
            <td class="lbl">Kelas</td>
            <td>: {{ $kelas ?? '—' }}</td>
        </tr>
        <tr>
            <td class="lbl">No Terima</td>
            <td colspan="3">: {{ $transNo ?? '—' }}</td>
        </tr>
    </table>

    <table class="trx">
        <thead>
            <tr>
                <th>Bayar</th>
                <th>Biaya Admin</th>
                <th>Masuk Uang Saku</th>
                <th>Tanggal Bayar</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>{{ number_format((int) ($nominal ?? 0), 0, ',', '.') }}</td>
                <td>{{ number_format((int) ($fee ?? 0), 0, ',', '.') }}</td>
                <td>{{ number_format((int) ($saldoDidapat ?? max(0, (int) ($nominal ?? 0) - (int) ($fee ?? 0))), 0, ',', '.') }}</td>
                <td>{{ ($trxDate ?? now())->format('Y-m-d H:i:s') }}</td>
            </tr>
        </tbody>
    </table>

    @if (!empty($note))
        <div><strong>Catatan:</strong> {{ $note }}</div>
    @endif

    <table class="footer">
        <tr>
            <td style="width:50%;">
                Raudhatul Quran, {{ ($trxDate ?? now())->format('Y-m-d') }}<br>
                Teller: {{ $teller ?? 'BMI' }}
            </td>
            <td style="width:50%; text-align:right;">
                <div class="sign-line" style="margin-left:auto;">( ........................ )<br>Penyetor</div>
            </td>
        </tr>
    </table>
</body>
</html>
