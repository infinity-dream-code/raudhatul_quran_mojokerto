<?php

namespace App\Http\Controllers\Cashless;

use App\Http\Controllers\Controller;
use App\Support\PersistentLogin;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class CashlessLoginController extends Controller
{
    public function show(): View|RedirectResponse
    {
        if (session('auth_module') === 'cashless' && session('cashless_kantin_ok')) {
            return redirect()->route('cashless.index');
        }

        return view('auth.login', [
            'formAction' => route('cashless.login.submit'),
            'heading' => 'Masuk ke Cashless',
            'subheading' => 'Silakan masuk menggunakan akun kantin',
            'showTurnstile' => false,
            'backUrl' => route('portal'),
        ]);
    }

    public function login(Request $request): RedirectResponse
    {
        $request->validate([
            'username' => 'required|string|max:50',
            'password' => 'required|string|max:100',
        ]);

        $username = trim((string) $request->input('username'));
        $password = (string) $request->input('password');

        try {
            $kantin = DB::connection('DATA_MYSQL')
                ->table('sm_kantin')
                ->where('username', $username)
                ->first();
        } catch (\Throwable $e) {
            Log::error('CashlessLogin: gagal membaca sm_kantin', [
                'username' => $username,
                'error' => $e->getMessage(),
            ]);

            return back()
                ->withErrors(['username' => 'Gagal terhubung ke server, silakan coba lagi.'])
                ->withInput($request->only('username'));
        }

        $stored = trim((string) ($kantin->password ?? ''));
        if (!$kantin || $stored === '' || !hash_equals(strtolower($stored), md5($password))) {
            return back()
                ->withErrors(['username' => 'Username atau password kantin salah.'])
                ->withInput($request->only('username'));
        }

        $request->session()->regenerate();
        session([
            'auth_module' => 'cashless',
            'dummy_logged_in' => false,
            'cashless_kantin_ok' => true,
            'user' => [
                'id' => $kantin->urut,
                'username' => $kantin->username,
                'kantin' => $kantin->NamaKantin ?? '',
                'kode_merchan' => $kantin->KDMERCAN ?? '',
            ],
        ]);
        PersistentLogin::queueSet();

        return redirect()->route('cashless.index');
    }
}
