<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$api = app(App\Services\AmalFatimahApiService::class);
echo 'wsReady: ' . ($api->isWsConfigured() ? 'yes' : 'no') . PHP_EOL;
echo 'url: ' . config('services.ws_raudhatul_quran.url') . PHP_EOL;
echo 'jwt: ' . (config('services.ws_raudhatul_quran.jwt_key') ? 'set' : 'empty') . PHP_EOL;

$fo = $api->getFilterBuatTagihan();
echo 'filter thn_aka count: ' . count($fo['thn_akademik'] ?? []) . PHP_EOL;
echo 'filter kelas count: ' . count($fo['kelas'] ?? []) . PHP_EOL;

$local = app(SikeuBuatTagihanService::class)->safeQuery($filters, 10, 0);
echo 'local ok: ' . (($local['ok'] ?? false) ? 'yes' : 'no') . PHP_EOL;
echo 'local message: ' . ($local['message'] ?? '') . PHP_EOL;
echo 'local total_siswa: ' . ($local['data']['total_siswa'] ?? 'n/a') . PHP_EOL;

$res = $api->getBuatTagihan([
    'thn_akademik' => '2024/2025 - GANJIL',
    'thn_angkatan' => '2024/2025 - GANJIL',
    'kelas_id' => '6',
    'search' => '',
    'fungsi' => '202407',
    'tagihan' => '',
], 10, 0);

echo 'buat ok: ' . (($res['ok'] ?? false) ? 'yes' : 'no') . PHP_EOL;
echo 'message: ' . ($res['message'] ?? '') . PHP_EOL;
$data = $res['data'] ?? [];
echo 'total_siswa: ' . ($data['total_siswa'] ?? 'n/a') . PHP_EOL;
echo 'daftar_harga count: ' . (is_array($data['daftar_harga'] ?? null) ? count($data['daftar_harga']) : 'n/a') . PHP_EOL;
