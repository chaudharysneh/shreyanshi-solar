<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Route;

class RouteHelper
{
    /**
     * Safely get a route URL, with fallback
     */
    public static function safeRoute($routeName, $fallback = null)
    {
        try {
            if (Route::has($routeName)) {
                return route($routeName);
            }
            
            // Try fallback routes
            $fallbacks = [
                'reports.customers' => ['reports.customers_report', 'customers_report_old'],
                'reports.leads' => ['reports.leads_report', 'leads_report_old'],
                'reports.deals' => ['reports.deals_report', 'deals_report_old'],
                'reports.tasks' => ['reports.tasks_report', 'tasks_report_old'],
                'reports.followups' => ['reports.followups_report', 'followups_report_old'],
            ];
            
            if (isset($fallbacks[$routeName])) {
                foreach ($fallbacks[$routeName] as $fallbackRoute) {
                    if (Route::has($fallbackRoute)) {
                        return route($fallbackRoute);
                    }
                }
            }
            
            // If no route found, return fallback URL
            return $fallback ?? '#';
        } catch (\Exception $e) {
            return $fallback ?? '#';
        }
    }
    
    /**
     * Check if route exists
     */
    public static function hasRoute($routeName)
    {
        try {
            return Route::has($routeName);
        } catch (\Exception $e) {
            return false;
        }
    }
    
    /**
     * Check if current route matches any of the given routes
     */
    public static function isRoute(...$routes)
    {
        try {
            return request()->routeIs(...$routes);
        } catch (\Exception $e) {
            return false;
        }
    }
}
