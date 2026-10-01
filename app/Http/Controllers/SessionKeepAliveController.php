<?php

namespace App\Http\Controllers;

use App\Support\PersistentLogin;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SessionKeepAliveController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        if (session('sso_authenticated')) {
            PersistentLogin::queueSet();
            $request->session()->put('_keepalive_at', now()->timestamp);
        }

        return response()->json([
            'ok' => true,
            'csrf_token' => csrf_token(),
            'authenticated' => (bool) session('sso_authenticated'),
        ]);
    }
}
