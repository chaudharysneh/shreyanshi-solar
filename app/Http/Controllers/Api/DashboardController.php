<?php

namespace App\Http\Controllers\Api;

use App\Models\Lead;
use App\Models\Booking;
use App\Models\Quotation;
use App\Models\Stage;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class DashboardController extends ApiBaseController
{
    /**
     * Get dynamic dashboard metrics
     */
    public function index()
    {
        $activeStageIds = Stage::query()
            ->whereNotIn(DB::raw('LOWER(name)'), ['won', 'lost'])
            ->pluck('id');

        $metrics = [
            'total_leads' => Lead::count(),
            'total_bookings' => Booking::count(),
            'total_quotations' => Quotation::count(),
            'total_staff' => User::count(),
            
            // Use dynamic stage lookup to avoid brittle hardcoded IDs.
            'active_deals' => Lead::whereIn('lead_stage_id', $activeStageIds)->count(),
            'recent_bookings' => Booking::latest()->take(5)->get(),
        ];

        return $this->success($metrics, 'Dashboard metrics retrieved successfully');
    }
}

