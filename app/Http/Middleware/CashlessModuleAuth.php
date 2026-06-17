<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CashlessModuleAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!session('sso_authenticated')) {
            return redirect()->route('login');
        }

        if (session('auth_module') !== 'cashless') {
            return redirect()->route('portal');
        }

        return $next($request);
    }
}

