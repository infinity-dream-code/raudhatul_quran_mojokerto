<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CashlessAuth
{
    /**
     * Sinkronkan session user cashless (sm_kantin) dari login portal SSO.
     */
    public static function syncSession(): void
    {
        $username = session('auth_username');
        if (!$username) {
            return;
        }

        if (session('user.username') === $username && session('user.id')) {
            return;
        }

        try {
            $row = DB::connection('DATA_MYSQL')
                ->table('sm_kantin')
                ->where('username', $username)
                ->first();

            if ($row) {
                session([
                    'user' => [
                        'id' => $row->urut,
                        'username' => $row->username,
                        'kantin' => $row->NamaKantin ?? '',
                        'kode_merchan' => $row->KDMERCAN ?? '',
                    ],
                ]);

                return;
            }
        } catch (\Throwable $e) {
            Log::warning('CashlessAuth: gagal lookup sm_kantin', [
                'username' => $username,
                'error' => $e->getMessage(),
            ]);
        }

        session([
            'user' => [
                'id' => null,
                'username' => $username,
                'kantin' => session('auth_name', ''),
                'kode_merchan' => '',
            ],
        ]);
    }
}
