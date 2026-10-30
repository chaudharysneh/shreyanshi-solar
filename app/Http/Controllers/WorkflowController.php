<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\DocumentVerification;
use App\Models\Registration;
use App\Models\Feasibility;
use App\Models\Finance;
use App\Models\WaitingFloor;
use App\Models\Dispatch;
use App\Models\Installation;
use App\Models\Intimation;
use App\Models\NetMetering;
use App\Models\Subsidy;

class WorkflowController extends Controller
{
    public function index($stage)
    {
        $validStages = [
            'document-verification',
            'registration',
            'feasibility',
            'finance',
            'waiting-floor',
            'dispatch',
            'installations',
            'intimation',
            'net-metering',
            'subsidy'
        ];

        if (!in_array($stage, $validStages)) {
            abort(404);
        }

        $pageTitle = Str::title(str_replace('-', ' ', $stage));
        $masterName = $stage === 'feasibility' ? 'Meter Charge' : $pageTitle;
        abort_unless(\App\Models\Stage::where('name', $masterName)->exists(), 404);

        // Define columns for Kanban boards
        $columns = [];
        $financeOptions = null;
        $allFinanceCount = 0;
        
        if ($stage === 'feasibility') {
            $pageTitle = 'Meter Charge';
        }
        
        $dynamicStages = \App\Models\WorkflowStage::where('workflow_type', $stage)
            ->where('is_active', true)
            ->orderBy('order', 'asc')
            ->get();
            
        if ($dynamicStages->isEmpty() && !in_array($stage, ['finance', 'installations'])) {
            // Fallback generic columns if none defined yet in db
            $columns = [
                ['name' => 'Pending ' . $pageTitle, 'count' => 0],
                ['name' => $pageTitle . ' In Progress', 'count' => 0],
                ['name' => $pageTitle . ' Completed', 'count' => 0],
            ];
        } else {
            $columns = $dynamicStages->map(function($s) {
                return ['id' => $s->id, 'name' => $s->name, 'count' => 0];
            })->toArray();
        }

        if ($stage === 'finance') {
            $financeOptions = \App\Models\FinanceMaster::where('is_active', 1)->get()->map(function ($financeMaster) {
                $count = \App\Models\Finance::whereHas('customer', function ($q) use ($financeMaster) {
                    $q->where('finance_id', $financeMaster->id);
                })->count();
                $financeMaster->current_count = $count;
                return $financeMaster;
            });
            $allFinanceCount = \App\Models\Finance::count();
        } elseif ($stage === 'installations') {
            $financeOptions = \App\Models\InstallationMaster::where('is_active', 1)->get()->map(function ($master) {
                $count = \App\Models\Installation::whereHas('customer', function ($q) use ($master) {
                    $q->where('installation_master_id', $master->id);
                })->count();
                $master->current_count = $count;
                return $master;
            });
            $allFinanceCount = \App\Models\Installation::count();
        }

        if ($stage === 'intimation') {
            return view('workflow.intimation', compact('stage', 'pageTitle'));
        }

        return view('workflow.kanban', compact('stage', 'pageTitle', 'columns', 'financeOptions', 'allFinanceCount'));
    }

    public function customerHistory($customerId)
    {
        $history = collect();
        $stages = [
            DocumentVerification::class => 'Document Verification',
            Registration::class => 'Registration',
            Feasibility::class => 'Feasibility',
            Finance::class => 'Finance',
            WaitingFloor::class => 'Waiting Floor',
            Dispatch::class => 'Dispatch',
            Installation::class => 'Installation',
            Intimation::class => 'Intimation',
            NetMetering::class => 'Net Metering',
            Subsidy::class => 'Subsidy',
        ];

        foreach ($stages as $modelClass => $stageName) {
            $records = $modelClass::with('assignedTo')->where('customer_id', $customerId)->orderBy('created_at', 'asc')->get();
            foreach ($records as $record) {
                if ($record->status === 'completed') {
                    $history->push([
                        'stage' => $stageName,
                        'transition' => $record->previous_stage ? explode(' (', $record->previous_stage)[0] . ' -> ' . $stageName . ' Done' : 'Started -> ' . $stageName . ' Done',
                        'by' => $record->assignedTo ? $record->assignedTo->name : 'System',
                        'date' => $record->updated_at->format('d M Y'),
                        'note' => $record->remarks ?? 'ok'
                    ]);
                }
            }
        }

        return response()->json([
            'success' => true,
            'data' => $history->sortBy('date')->values()
        ]);
    }
}
