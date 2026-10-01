<?php

namespace App\Support;

use Illuminate\Support\Facades\Cookie;

class PersistentLogin
{
    public const COOKIE = 'raudhatul_quran';

    /**
     * Session keys that keep the user logged in across session-file loss.
     *
     * @return list<string>
     */
    public static function keys(): array
    {
        return [
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
            'user',
        ];
    }

    public static function snapshot(): array
    {
        $data = [];
        foreach (self::keys() as $key) {
            if (session()->has($key)) {
                $data[$key] = session($key);
            }
        }

        return $data;
    }

    public static function queueSet(?array $data = null): void
    {
        $data = $data ?? self::snapshot();
        if (empty($data['sso_authenticated'])) {
            return;
        }

        $minutes = max(120, (int) config('session.lifetime', 5256000));

        Cookie::queue(cookie(
            self::COOKIE,
            json_encode($data, JSON_UNESCAPED_UNICODE),
            $minutes,
            config('session.path', '/'),
            config('session.domain'),
            config('session.secure'),
            true,
            false,
            config('session.same_site', 'lax')
        ));
    }

    public static function queueForget(): void
    {
        Cookie::queue(Cookie::forget(self::COOKIE));
    }

    public static function restoreFromRequest($request): bool
    {
        $raw = $request->cookie(self::COOKIE);
        if (!is_string($raw) || $raw === '') {
            return false;
        }

        try {
            $data = json_decode($raw, true);
            if (!is_array($data) || empty($data['sso_authenticated'])) {
                return false;
            }

            foreach (self::keys() as $key) {
                if (array_key_exists($key, $data)) {
                    session([$key => $data[$key]]);
                }
            }

            return (bool) session('sso_authenticated');
        } catch (\Throwable) {
            return false;
        }
    }
}
