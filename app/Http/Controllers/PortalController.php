<?php

namespace App\Http\Controllers;

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

        return redirect()->route('dashboard');
    }

    public function switchModule(Request $request): RedirectResponse
    {
        if (!session('sso_authenticated')) {
            return redirect()->route('login');
        }

        $request->session()->forget(['auth_module', 'dummy_logged_in']);

        return redirect()->route('portal');
    }

    public function cashless(Request $request): RedirectResponse
    {
        if (!session('sso_authenticated')) {
            return redirect()->route('login');
        }

        $url = trim((string) config('sso.modules.cashless.url', ''));
        if ($url === '') {
            return redirect()->route('portal')->withErrors([
                'module' => 'URL modul Cashless belum dikonfigurasi.',
            ]);
        }

        $query = array_filter([
            'username' => session('auth_username'),
            'name' => session('auth_name'),
            'fid' => session('auth_fid'),
            'kel' => session('auth_kel'),
        ], fn ($v) => $v !== null && $v !== '');

        $separator = str_contains($url, '?') ? '&' : '?';

        return redirect()->away($url . ($query ? $separator . http_build_query($query) : ''));
    }

    public function presensi(): RedirectResponse
    {
        return redirect()->route('portal')->with('portal_info', 'Modul Presensi belum tersedia.');
    }
}
