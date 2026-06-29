<?php

namespace App\Http\Controllers;

use App\Support\CashlessAuth;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PortalController extends Controller
{
    public function index(): View|RedirectResponse
    {
        if (!session('sso_authenticated')) {
            return redirect()->route('login');
        }

        if (session('auth_module') === 'sikeu') {
            return redirect()->route('dashboard');
        }

        return view('auth.portal', [
            'modules' => config('sso.modules', []),
            'userName' => session('auth_name', session('auth_username', 'Pengguna')),
        ]);
    }

    public function sikeu(Request $request): RedirectResponse
    {
        if (!session('sso_authenticated')) {
            return redirect()->route('login');
        }

        $request->session()->put('auth_module', 'sikeu');
        $request->session()->put('dummy_logged_in', true);

        $targetUrl = trim((string) (config('sso.modules.sikeu.url') ?? ''));
        if ($this->isExternalUrl($targetUrl)) {
            return redirect()->away($targetUrl);
        }

        return redirect()->route('dashboard');
    }

    public function switchModule(Request $request): RedirectResponse
    {
        if (!session('sso_authenticated')) {
            return redirect()->route('login');
        }

        $request->session()->forget(['auth_module', 'dummy_logged_in', 'user']);

        return redirect()->route('portal');
    }

    public function cashless(Request $request): RedirectResponse
    {
        if (!session('sso_authenticated')) {
            return redirect()->route('login');
        }

        $request->session()->put('auth_module', 'cashless');
        $request->session()->put('dummy_logged_in', false);
        CashlessAuth::syncSession();

        $targetUrl = trim((string) (config('sso.modules.cashless.url') ?? ''));
        $targetHost = strtolower((string) parse_url($targetUrl, PHP_URL_HOST));
        $targetPath = trim((string) parse_url($targetUrl, PHP_URL_PATH), '/');
        $currentHost = strtolower((string) $request->getHost());

        // Jika diarahkan ke host sendiri/localhost dengan path cashless, pakai route internal satu project.
        if (
            $targetUrl === ''
            || (
                $targetPath === 'cashless'
                && ($targetHost === '' || $targetHost === 'localhost' || $targetHost === '127.0.0.1' || $targetHost === $currentHost)
            )
        ) {
            return redirect()->route('cashless.index');
        }

        if ($this->isExternalUrl($targetUrl)) {
            $useSignedToken = (bool) config('sso.modules.cashless.use_signed_token', false);
            if ($useSignedToken) {
                $token = $this->signToken();
                return redirect()->away($this->buildTargetUrl($targetUrl, [
                    'sso' => 1,
                    'token' => $token,
                ]));
            }

            return redirect()->away($targetUrl);
        }

        return redirect()->route('cashless.index');
    }

    public function presensi(Request $request): RedirectResponse
    {
        if (!session('sso_authenticated')) {
            return redirect()->route('login');
        }

        $enabled = (bool) config('sso.modules.presensi.enabled', false);
        $targetUrl = trim((string) (config('sso.modules.presensi.url') ?? ''));
        if (!$enabled || $targetUrl === '') {
            return redirect()->route('portal')->with('portal_info', 'Modul Presensi belum tersedia.');
        }

        $params = [];
        $useSignedToken = (bool) config('sso.modules.presensi.use_signed_token', true);
        if ($useSignedToken) {
            $params = [
                'sso' => 1,
                'token' => $this->signToken(),
            ];
        }

        return redirect()->away($this->buildTargetUrl($targetUrl, $params));
    }

    private function signToken(): string
    {
        $secret = (string) config('sso.token.secret', '');
        $payload = [
            'iss' => config('app.name'),
            'sub' => (int) session('auth_user_id', 0),
            'username' => (string) session('auth_username', ''),
            'name' => (string) session('auth_name', ''),
            'fid' => (string) session('auth_fid', ''),
            'kel' => (string) session('auth_kel', ''),
            'unit' => (string) session('auth_sekolah_nama', ''),
            'iat' => now()->timestamp,
            'exp' => now()->addSeconds((int) config('sso.token.ttl', 300))->timestamp,
        ];

        $body = rtrim(strtr(base64_encode((string) json_encode($payload, JSON_UNESCAPED_UNICODE)), '+/', '-_'), '=');
        $signature = hash_hmac('sha256', $body, $secret);

        return $body . '.' . $signature;
    }

    /**
     * @param array<string, string|int> $params
     */
    private function buildTargetUrl(string $url, array $params): string
    {
        if ($params === []) {
            return $url;
        }

        return $url . (str_contains($url, '?') ? '&' : '?') . http_build_query($params);
    }

    private function isExternalUrl(string $url): bool
    {
        return preg_match('/^https?:\/\//i', $url) === 1;
    }
}
