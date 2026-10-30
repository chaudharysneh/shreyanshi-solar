<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware to prevent browser caching of authenticated pages.
 * 
 * This middleware adds cache prevention headers to all responses,
 * ensuring that:
 * - Authenticated pages cannot be accessed from browser cache after logout
 * - Back button doesn't show cached authenticated content
 * - Forward button doesn't reopen protected pages after logout
 */
class NoCache
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Branding URLs include the file modification time, so a new upload
        // gets a new URL while navigation can reuse the browser's image cache.
        if ($request->routeIs('profile.company_logo.image', 'profile.sidebar_icon.image')
            && $response->isSuccessful()) {
            $response->headers->set('Cache-Control', 'private, max-age=86400');
            $response->headers->remove('Pragma');
            $response->headers->remove('Expires');

            return $response;
        }

        // Prevent browser caching of authenticated pages
        $response->headers->set('Cache-Control', 'no-cache, no-store, must-revalidate');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', '0');

        return $response;
    }
}
