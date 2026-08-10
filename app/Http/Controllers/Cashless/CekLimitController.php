<?php

namespace App\Http\Controllers\Cashless;

use App\Http\Controllers\Controller;
use App\Models\ValidationMessage;
use App\Services\Cashless\CashlessCardService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class CekLimitController extends Controller
{
    private string $title = "Cek Limit";
    private string $mainTitle = 'Cek Limit';
    private string $cacheKey = 'Cek Limit';

    public function __construct(
        private readonly CashlessCardService $cardService,
    ) {
        $key = Str::slug($this->cacheKey) . '_cache_version';
        Cache::add($key, 1);
    }

    public function index()
    {
        $data['title'] = $this->title;
        return view('cashless.cek_limit.index', $data);
    }

    public function getLimit(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                "tap_id" => ["required", "string"],
                "auth_mode" => ["nullable", "in:qr,pin"],
            ],
            ValidationMessage::messages(),
            ValidationMessage::attributes(),
        );

        if ($validator->fails()) {
            $message = $validator->errors()->first();
            if ($validator->errors()->count() > 1) {
                $message = "{$message} Dan beberapa masalah validasi lainnya, silahkan periksa form anda!";
            }
            return response()->json(
                [
                    "message" => $message,
                    "errors" => $validator->errors(),
                ],
                422,
            );
        }

        $authMode = $request->input('auth_mode', 'qr');
        $tapId = $this->cardService->parseQrToTapId((string) $request->tap_id);

        if ($tapId === '') {
            return response()->json([
                "message" => $authMode === 'qr'
                    ? "QR / PID tidak valid, silahkan scan ulang"
                    : "TAP ID tidak valid, silahkan tap kartu",
                "errors" => ["tap_id" => ["Identifikasi kartu tidak valid"]],
            ], 422);
        }

        try {
            \Log::info('CekLimit - Request tap_id:', ['tap_id' => $tapId, 'auth_mode' => $authMode]);

            $card = $this->cardService->fetchCardByTapId($tapId);
            if (!$card) {
                return response()->json([
                    'data' => 'error',
                    'nama' => '',
                    'nis' => '',
                    'message' => 'Kartu tidak ditemukan',
                ], 422);
            }

            if ($this->cardService->isBlocked($card)) {
                return response()->json([
                    'data' => 'error',
                    'nama' => '',
                    'nis' => '',
                    'message' => 'Kartu terblokir',
                ], 422);
            }

            $limit = $this->cardService->fetchBatasBelanjaHari();
            if ($limit <= 0) {
                $limit = 20000;
            }

            $nama = trim((string) ($card->nama ?? ''));
            $nis = trim((string) ($card->nis ?? ''));

            \Log::info('CekLimit - Student data:', [
                'nama' => $nama,
                'nis' => $nis,
                'limit' => $limit
            ]);

            return response()->json([
                'data' => $limit,
                'nama' => $nama,
                'nis' => $nis,
                'tap_id' => $tapId,
            ], 200);

        } catch (\Exception $e) {
            \Log::error('CekLimit - Error:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                "message" => "gagal mendapatkan data limit, silahkan coba lagi",
                "error" => $e->getMessage()
            ], 422);
        }
    }
}
