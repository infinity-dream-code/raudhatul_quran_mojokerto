<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SsoAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!session('sso_authenticated')) {
            return redirect()->route('login');
        }

        return $next($request);
    }
}
