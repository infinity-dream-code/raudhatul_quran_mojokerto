<?php

namespace App\Http\Controllers\Cashless;

use App\Http\Controllers\Controller;
use App\Models\ValidationMessage;
use App\Services\Cashless\CashlessCardService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class TapBelanjaController extends Controller
{
    public string $title;
    public $datasUrl;
    public $columnsUrl;

    public function __construct(
        private readonly CashlessCardService $cardService,
    ) {
        $this->title = "TAP KARTU";
    }

    public function index()
    {
        $data["title"] = $this->title;
        return view('cashless.tap_belanja.index', $data);
    }

    public function getSaldo(Request $request)
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
                $message = "{$message} Dan beberapa error lainnya";
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
            \Log::info('getSaldo - Request tap_id:', ['tap_id' => $tapId, 'auth_mode' => $authMode]);

            $saldo = DB::connection('DATA_MYSQL')
                ->select('SELECT GetSaldoCard_1VACashless(?) AS saldo', [$tapId]);

            \Log::info('getSaldo - Raw result from DB:', ['result' => $saldo[0]->saldo ?? 'NULL']);

            $data = explode("|", $saldo[0]->saldo);

            \Log::info('getSaldo - Exploded data:', ['data' => $data, 'count' => count($data)]);

            return response()->json(["data" => $data, "tap_id" => $tapId]);
        } catch (\Exception $e) {
            \Log::error('getSaldo - Error:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                "message" => "gagal mendapatkan data saldo, silahkan coba lagi",
                "error" => $e->getMessage()
            ], 422);
        }
    }

    const STATUS_MAP = [
        'ok' => [
            'code' => 1000,
            'message' => 'Transaksi berhasil',
        ],
        'insufficient_balance' => [
            'code' => 2001,
            'message' => 'Saldo tidak cukup',
        ],
        'unknown_or_blocked_card' => [
            'code' => 2002,
            'message' => 'Kartu Terblokir',
        ],
        'daily_transaction_limit_exceeded' => [
            'code' => 2003,
            'message' => 'Limit transaksi sudah tercapai!',
        ],
    ];

    public function payment(Request $request)
    {
        $authMode = $request->input('auth_mode', 'qr');
        $tapId = $this->cardService->parseQrToTapId((string) $request->tap_id);

        \Log::info('payment - Started', [
            'tap_id' => $tapId,
            'auth_mode' => $authMode,
            'belanja_raw' => $request->belanja,
            'session_user' => session('user.username')
        ]);

        $rules = [
            "tap_id" => ["required", "string"],
            "belanja" => ["required", 'regex:/^[0-9]+(\.[0-9]{3})*$/', 'not_in:0'],
            "auth_mode" => ["nullable", "in:qr,pin"],
        ];

        if ($authMode === 'pin') {
            $rules['pin'] = ['required', 'string', 'min:1', 'max:20'];
        }

        $validator = Validator::make(
            $request->all(),
            $rules,
            ValidationMessage::messages(),
            ValidationMessage::attributes(),
        );

        if ($validator->fails()) {
            $message = $validator->errors()->first();
            if ($validator->errors()->count() > 1) {
                $message = "{$message} Dan beberapa error lainnya";
            }

            \Log::warning('payment - Validation failed', [
                'errors' => $validator->errors()->toArray()
            ]);

            return response()->json(
                [
                    "message" => $message,
                    "errors" => $validator->errors(),
                ],
                422,
            );
        }

        if ($tapId === '') {
            return response()->json([
                "message" => "Identifikasi kartu tidak valid",
                "errors" => ["tap_id" => ["Identifikasi kartu tidak valid"]],
            ], 422);
        }

        if ($authMode === 'pin') {
            $card = $this->cardService->fetchCardByTapId($tapId);
            if (!$card) {
                return response()->json([
                    'status' => 'unknown_or_blocked_card',
                    'code' => self::STATUS_MAP['unknown_or_blocked_card']['code'],
                    'message' => 'Kartu tidak ditemukan',
                    'data' => [],
                ], 422);
            }

            if ($this->cardService->isBlocked($card)) {
                return response()->json([
                    'status' => 'unknown_or_blocked_card',
                    'code' => self::STATUS_MAP['unknown_or_blocked_card']['code'],
                    'message' => self::STATUS_MAP['unknown_or_blocked_card']['message'],
                    'data' => [],
                ], 422);
            }

            if (!$this->cardService->validatePin($card, (string) $request->pin)) {
                return response()->json([
                    'status' => 'pin_error',
                    'code' => 2004,
                    'message' => 'PIN salah',
                    'data' => [],
                ], 422);
            }
        }

        try {
            $nominal = str_replace('.', '', $request->belanja);
            \Log::info('payment - Process payment', [
                'tap_id' => $tapId,
                'nominal' => $nominal,
                'teller' => session('user.username')
            ]);

            $result = DB::connection('DATA_MYSQL')
                ->select(
                    'SELECT WebPaymentBUY(?,?,?) AS result',
                    [
                        $tapId,
                        $nominal,
                        session('user.username'),
                    ]);

            $result = $result[0]->result ?? "error";
            \Log::info('payment - Raw result from WebPaymentBUY', ['result' => $result]);

            $statusKey = null;
            $data = [];

            if (str_contains($result, '|')) {
                $parts = explode('|', $result);
                \Log::info('payment - Exploded parts', ['parts' => $parts]);

                $statusKey = strtolower($parts[0]);

                if ($statusKey === 'ok') {
                    $data = [
                        'nama' => $parts[1] ?? null,
                        'sisa_saldo' => $parts[2] ?? null,
                    ];
                    \Log::info('payment - Success transaction', [
                        'nama' => $data['nama'],
                        'sisa_saldo' => $data['sisa_saldo']
                    ]);
                }
            } else {
                $statusKey = strtolower($result);
                \Log::info('payment - Status key from result', ['statusKey' => $statusKey]);
            }

            $config = self::STATUS_MAP[$statusKey] ?? [
                'code' => 9999,
                'message' => 'Unknown error',
            ];

            \Log::info('payment - Final response', [
                'status' => $statusKey,
                'code' => $config['code'],
                'message' => $config['message']
            ]);

            return response()->json([
                'status' => $statusKey,
                'code'   => $config['code'],
                'message'=> $config['message'],
                'data'   => $data,
            ], 200);

        } catch (\Exception $e) {
            \Log::error('payment - Exception occurred', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                "message" => "gagal mendapatkan data saldo, silahkan coba lagi",
                "error" => $e->getMessage()
            ], 422);
        }
    }
}
