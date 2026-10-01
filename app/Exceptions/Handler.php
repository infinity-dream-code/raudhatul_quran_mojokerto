<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });

        $this->renderable(function (TokenMismatchException $e, Request $request) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'message' => 'Token mismatch',
                    'csrf_token' => csrf_token(),
                ], 419);
            }

            // Hindari halaman "Page Expired" — kembali diam-diam ke halaman sebelumnya.
            $fallback = url('/admin');

            return redirect()->to($request->headers->get('referer') ?: $fallback);
        });
    }

    public function render($request, Throwable $e)
    {
        if ($this->isTransientServerError($e) && $request->isMethodSafe()) {
            try {
                if ($request->hasSession() && !$request->session()->pull('_transient_retried')) {
                    $request->session()->put('_transient_retried', true);

                    return redirect()->to($request->fullUrl());
                }
            } catch (Throwable) {
                // ignore session issues during exception rendering
            }

            return response()->view('errors.500', [
                'exception' => $e,
            ], 500);
        }

        return parent::render($request, $e);
    }

    protected function isTransientServerError(Throwable $e): bool
    {
        $message = strtolower($e->getMessage());
        $needles = [
            'serialization failure',
            'deadlock',
            'lock wait timeout',
            'try restarting transaction',
            'server has gone away',
            'connection refused',
            'connection timed out',
            'unable to obtain lock',
            'resource temporarily unavailable',
            'database is locked',
        ];

        foreach ($needles as $needle) {
            if (str_contains($message, $needle)) {
                return true;
            }
        }

        $class = $e::class;

        return str_contains($class, 'QueryException')
            && (str_contains($message, '1213') || str_contains($message, '1205') || str_contains($message, '2006'));
    }
}
