<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class HideProjects
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->is('projects', 'projects/*', 'projects-export', 'projects_report*', 'reports/projects*', 'api/projects*', 'api/v1/projects*')) {
            return response('Not Found', 404);
        }
        return $next($request);
    }
}
