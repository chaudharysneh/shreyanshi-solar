<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    public function handle($request, \Closure $next)
    {
        try {
            return parent::handle($request, $next);
        } catch (\Illuminate\Session\TokenMismatchException $exception) {
            if (!$request->routeIs('login.submit') || $request->expectsJson()) {
                throw $exception;
            }

            $request->session()->regenerateToken();
            return redirect()->route('login')
                ->withErrors(['email' => 'Your login session expired. Please sign in again.'])
                ->withInput($request->only('email'))
                ->withHeaders(['Cache-Control' => 'no-store, no-cache, must-revalidate']);
        }
    }

    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        //
    ];
}
