<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     */
    protected function redirectTo(Request $request): ?string
    {
        if ($request->expectsJson()) {
            return null;
        }

        // Store the intended URL in the session before redirecting to login
        if (!$request->is('login')) {
            session(['url.intended' => $request->url()]);
        }

        return route('login');
    }
    /**
     * Handle an incoming request.
     */
    public function handle($request, \Closure $next, ...$guards)
    {
        $this->authenticate($request, $guards);
        
        $user = \Illuminate\Support\Facades\Auth::user();
        if ($user) {
            $planOwner = $user;
            if (!$user->isAdmin() && !empty($user->parent_id)) {
                $planOwner = \App\Models\User::find($user->parent_id) ?: $user;
            }

            if ($planOwner) {
                $subscriptionAssignment = \Illuminate\Support\Facades\DB::table('subscription_user_plan')
                    ->where('user_id', $planOwner->id)
                    ->orderByDesc('id')
                    ->first();

                try {
                    $hasEndDate = isset($subscriptionAssignment->end_date);
                    
                    if ($hasEndDate && !empty($subscriptionAssignment->end_date)) {
                        $endDate = \Carbon\Carbon::parse($subscriptionAssignment->end_date)->startOfDay();
                        $daysRemaining = (int) \Carbon\Carbon::now()->startOfDay()->diffInDays($endDate, false);
                        
                        if ($daysRemaining < 0) {
                            \Illuminate\Support\Facades\Auth::logout();
                            
                            if ($request->expectsJson()) {
                                return response()->json(['message' => 'Your subscription has expired.'], 403);
                            }
                            
                            return redirect()->route('login')->with('expired_error', [
                                'message' => 'Your subscription expired on ' . $endDate->format('d M Y') . '. Please contact support or renew your plan to continue using the platform.',
                                'plan_name' => \Illuminate\Support\Facades\DB::table('subscription_plan')->where('id', $subscriptionAssignment->subscription_id)->value('name') ?? 'Basic Plan',
                                'end_date' => $endDate->format('d M Y')
                            ]);
                        }
                    }
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::warning('Subscription check failed: ' . $e->getMessage());
                    // Fail gracefully - allow login
                }
            }
        }
        
        return $next($request);
    }
}
