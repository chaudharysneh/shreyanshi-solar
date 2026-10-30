<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureMainAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user || !($user->is_active ?? true)) {
            abort(403);
        }

        // Allow only admin-role users to access user logs
        if (!$user->isAdmin()) {
            abort(403);
        }

        return $next($request);
    }
}
