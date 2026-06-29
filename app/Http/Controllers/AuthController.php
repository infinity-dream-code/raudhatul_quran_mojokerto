<?php

namespace App\Http\Controllers;

use App\Services\CyberKeyAuthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (session('sso_authenticated')) {
            if (session('auth_module') === 'sikeu') {
                return redirect()->route('dashboard');
            }

            return redirect()->route('portal');
        }

        return view('auth.login');
    }

    public function login(Request $request, CyberKeyAuthService $cyberKey)
    {
        $rules = [
            'username' => 'required|string',
            'password' => 'required|string',
        ];

        if ($this->turnstileEnabled()) {
            $rules['cf-turnstile-response'] = 'required|string';
        }

        $messages = [
            'cf-turnstile-response.required' => 'Captcha Cloudflare wajib dicentang.',
        ];
        $request->validate($rules, $messages);

        if ($this->turnstileEnabled() && !$this->verifyTurnstile($request)) {
            return back()
                ->withErrors(['turnstile' => 'Gagal melakukan verifikasi Captcha, silahkan coba lagi.'])
                ->withInput($request->only('username'));
        }

        $login = trim((string) $request->input('username', ''));
        $password = (string) $request->input('password', '');

        $res = $cyberKey->login($login, $password);
        if (!$res['ok']) {
            return back()
                ->withErrors(['username' => $res['message'] ?? 'Username atau password salah.'])
                ->withInput($request->only('username'));
        }

        $user = is_array($res['user'] ?? null) ? $res['user'] : [];

        session([
            'sso_authenticated' => true,
            'auth_module' => null,
            'dummy_logged_in' => false,
            'auth_user' => $user,
            'auth_user_id' => (int) ($user['id'] ?? 0),
            'auth_username' => (string) ($user['username'] ?? ''),
            'auth_name' => (string) ($user['name'] ?? ''),
            'auth_fid' => (string) ($user['fid'] ?? ''),
            'auth_kel' => (string) ($user['kel'] ?? ''),
            'auth_is_superadmin' => (bool) ($user['is_superadmin'] ?? false),
            'auth_sekolah_code01' => (string) ($user['sekolah_code01'] ?? ''),
            'auth_sekolah_nama' => (string) ($user['sekolah_nama'] ?? ($user['unit'] ?? '')),
        ]);
        $request->session()->regenerate();

        return redirect()->route('portal');
    }

    public function logout(Request $request)
    {
        session()->forget([
            'sso_authenticated',
            'auth_module',
            'dummy_logged_in',
            'auth_user',
            'auth_user_id',
            'auth_username',
            'auth_name',
            'auth_fid',
            'auth_kel',
            'auth_is_superadmin',
            'auth_sekolah_code01',
            'auth_sekolah_nama',
        ]);
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    private function turnstileEnabled(): bool
    {
        return trim((string) config('services.turnstile.site_key', '')) !== ''
            && trim((string) config('services.turnstile.secret_key', '')) !== '';
    }

    private function verifyTurnstile(Request $request): bool
    {
        try {
            $response = Http::asForm()->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
                'secret' => config('services.turnstile.secret_key'),
                'response' => $request->input('cf-turnstile-response'),
                'remoteip' => $request->ip(),
            ]);

            return (bool) $response->json('success');
        } catch (\Throwable) {
            return false;
        }
    }
}
