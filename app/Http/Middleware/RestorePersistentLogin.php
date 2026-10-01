<?php

namespace App\Http\Middleware;

use App\Support\PersistentLogin;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RestorePersistentLogin
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!session('sso_authenticated')) {
            PersistentLogin::restoreFromRequest($request);
        }

        if (session('sso_authenticated')) {
            PersistentLogin::queueSet();
        }

        return $next($request);
    }
}
