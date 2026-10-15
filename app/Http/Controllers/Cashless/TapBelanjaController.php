<?php

namespace App\Http\Controllers\Cashless;

use App\Http\Controllers\Controller;
use App\Models\ValidationMessage;
use App\Services\Cashless\CashlessQrParser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class TapBelanjaController extends Controller
{
    public string $title;
    public $datasUrl;
    public $columnsUrl;

    public function __construct()
    {
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

        $tapId = CashlessQrParser::toPid((string) $request->tap_id);
        if ($tapId === '') {
            return response()->json([
                "message" => "QR / PID tidak valid",
                "errors" => ["tap_id" => ["QR / PID tidak valid"]],
            ], 422);
        }

        \Log::info('getSaldo - Request tap_id:', ['tap_id' => $tapId]);

        $data = [];
        try {
            $saldo = DB::connection('DATA_MYSQL')
                ->select('SELECT GetSaldoCard_1VACashless(?) AS saldo', [$tapId]);

            $raw = trim((string) ($saldo[0]->saldo ?? ''));
            \Log::info('getSaldo - Raw result from DB:', ['result' => $raw !== '' ? $raw : 'NULL']);

            if ($raw !== '') {
                $data = array_map('trim', explode("|", $raw));
            }
        } catch (\Throwable $e) {
            \Log::error('getSaldo - GetSaldoCard_1VACashless gagal, memakai query langsung', [
                'tap_id' => $tapId,
                'message' => $e->getMessage(),
            ]);
        }

        if (count($data) !== 3) {
            try {
                $data = $this->fetchSaldoDirect($tapId);
            } catch (\Throwable $e) {
                \Log::error('getSaldo - Error:', [
                    'tap_id' => $tapId,
                    'message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);

                return response()->json([
                    "message" => "gagal mendapatkan data saldo, silahkan coba lagi",
                    "error" => $e->getMessage()
                ], 422);
            }
        }

        if ($data === []) {
            return response()->json([
                "message" => "Kartu tidak ditemukan, silahkan tap kartu yang valid",
                "errors" => ["tap_id" => ["Kartu tidak ditemukan"]],
            ], 422);
        }

        \Log::info('getSaldo - Data:', ['data' => $data]);

        return response()->json(["data" => $data, "tap_id" => $tapId]);
    }

    private function fetchSaldoDirect(string $tapId): array
    {
        $card = DB::connection('DATA_MYSQL')
            ->table('sm_pin')
            ->join('scctcust', 'sm_pin.CUSTID', '=', 'scctcust.CUSTID')
            ->where('sm_pin.PID', $tapId)
            ->first(['sm_pin.CUSTID as custid', 'scctcust.NMCUST as nama']);

        if (!$card) {
            return [];
        }

        $row = DB::connection('DATA_MYSQL')
            ->table('sccttran_cashless')
            ->where('CUSTID', $card->custid)
            ->selectRaw('CAST(COALESCE(SUM(KREDIT), 0) AS SIGNED) - CAST(COALESCE(SUM(DEBET), 0) AS SIGNED) AS saldo')
            ->first();

        return [$tapId, (string) (int) ($row->saldo ?? 0), trim((string) ($card->nama ?? ''))];
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
        $tapId = CashlessQrParser::toPid((string) $request->tap_id);

        \Log::info('payment - Started', [
            'tap_id' => $tapId,
            'belanja_raw' => $request->belanja,
            'session_user' => session('user.username')
        ]);

        $validator = Validator::make(
            $request->all(),
            [
                "tap_id" => ["required", "string"],
                "belanja" => ["required", 'regex:/^[0-9]+(\.[0-9]{3})*$/', 'not_in:0'],
            ],
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
                "message" => "QR / PID tidak valid",
                "errors" => ["tap_id" => ["QR / PID tidak valid"]],
            ], 422);
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
                'message' => 'Transaksi gagal (' . $result . ')',
            ];

            if ($config['code'] === 9999) {
                \Log::warning('payment - Unknown result from WebPaymentBUY', [
                    'tap_id' => $tapId,
                    'teller' => session('user.username'),
                    'result' => $result,
                ]);
            }

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

        } catch (\Throwable $e) {
            \Log::error('payment - Exception occurred', [
                'tap_id' => $tapId,
                'teller' => session('user.username'),
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                "message" => "Gagal memproses pembayaran, silahkan coba lagi",
                "error" => $e->getMessage()
            ], 422);
        }
    }
}
