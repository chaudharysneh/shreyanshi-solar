<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
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

class WorkflowService
{
    // Define the sequence of modules
    protected $stages = [
        DocumentVerification::class,
        Registration::class,
        Feasibility::class,
        Finance::class,
        WaitingFloor::class,
        Dispatch::class,
        Installation::class,
        Intimation::class,
        NetMetering::class,
        Subsidy::class,
    ];

    /**
     * Advance a customer to the next stage if they are completed in the current stage.
     */
    public function advanceIfCompleted($currentRecord, $modelClass)
    {
        if ($currentRecord->status !== 'completed') {
            return false;
        }

        $currentIndex = array_search($modelClass, $this->stages);

        if ($currentIndex === false || $currentIndex >= count($this->stages) - 1) {
            // Already at the last stage or model not found in sequence
            return false;
        }

        $nextStageClass = $this->stages[$currentIndex + 1];

        // Check if a record already exists in the next stage to prevent duplicates
        $exists = $nextStageClass::where('customer_id', $currentRecord->customer_id)->exists();

        if (!$exists) {
            $nextStageClass::create(array_merge(app(WorkflowStageCatalog::class)->initialState($nextStageClass, $currentRecord->customer_id), [
                'customer_id' => $currentRecord->customer_id,
                'aspl_id' => $currentRecord->aspl_id,
                'status' => 'pending',
                'previous_stage' => class_basename($modelClass) . ' (' . $currentRecord->id . ')',
                'assigned_to' => null, // Or logic to inherit assigned_to if required
            ]));
            
            $this->syncPipelineStage($currentRecord->customer_id, $nextStageClass);
            
            Log::info("Customer {$currentRecord->customer_id} advanced from " . class_basename($modelClass) . " to " . class_basename($nextStageClass));
            return true;
        }

        return false;
    }

    /**
     * Sync the underlying Customer pipeline_stage_id to reflect the current active Workflow Stage.
     */
    public function syncPipelineStage($customer_id, $modelClass)
    {
        $stageNameMap = [
            DocumentVerification::class => 'Document Verification',
            Registration::class => 'Registration',
            Feasibility::class => 'Meter Charge',
            Finance::class => 'Finance',
            WaitingFloor::class => 'Waiting Floor',
            Dispatch::class => 'Dispatch',
            Installation::class => 'Installations',
            Intimation::class => 'Intimation',
            NetMetering::class => 'Net Metering',
            Subsidy::class => 'Subsidy',
        ];

        $stageName = $stageNameMap[$modelClass] ?? null;
        if (!$stageName) return;

        $stage = \App\Models\Stage::where('name', $stageName)->first();
        if (!$stage) return;

        $customer = \App\Models\Customer::find($customer_id);
        if (!$customer) return;

        // Update the customer's pipeline stage
        $customer->update(['pipeline_stage_id' => $stage->id]);
    }

    /**
     * Synchronize the workflow stages records (creation, completion, deletion) to match a target Pipeline Stage ID.
     */
    public function syncWorkflowToPipelineStage($customerId, $stageId)
    {
        $stage = \App\Models\Stage::find($stageId);
        if (!$stage) return;

        $stageName = $stage->name;

        $stageModelMap = [
            'Document Verification' => DocumentVerification::class,
            'Registration' => Registration::class,
            'Meter Charge' => Feasibility::class,
            'Finance' => Finance::class,
            'Waiting Floor' => WaitingFloor::class,
            'Dispatch' => Dispatch::class,
            'Installations' => Installation::class,
            'Intimation' => Intimation::class,
            'Net Metering' => NetMetering::class,
            'Subsidy' => Subsidy::class,
        ];

        $targetModelClass = $stageModelMap[$stageName] ?? null;
        if (!$targetModelClass) return;

        $targetIndex = array_search($targetModelClass, $this->stages);
        if ($targetIndex === false) return;

        $asplId = 'ASPL-' . str_pad($customerId, 4, '0', STR_PAD_LEFT);

        foreach ($this->stages as $index => $modelClass) {
            if ($index < $targetIndex) {
                // This stage is BEFORE the target stage -> must be completed
                $record = $modelClass::where('customer_id', $customerId)->first();
                if ($record) {
                    $record->update(['status' => 'completed']);
                } else {
                    $modelClass::create([
                        'customer_id' => $customerId,
                        'aspl_id' => $asplId,
                        'status' => 'completed',
                        'remarks' => 'Auto-completed via Pipeline Stage Map sync',
                        'previous_stage' => $index > 0 ? class_basename($this->stages[$index - 1]) : null,
                    ]);
                }
            } elseif ($index === $targetIndex) {
                // This is the target stage -> must be pending (active)
                $record = $modelClass::where('customer_id', $customerId)->first();
                if ($record) {
                    $record->update(array_merge(['status' => 'pending'], app(WorkflowStageCatalog::class)->initialState($modelClass, $customerId)));
                } else {
                    $modelClass::create(array_merge(app(WorkflowStageCatalog::class)->initialState($modelClass, $customerId), [
                        'customer_id' => $customerId,
                        'aspl_id' => $asplId,
                        'status' => 'pending',
                        'remarks' => 'Activated via Pipeline Stage Map sync',
                        'previous_stage' => $index > 0 ? class_basename($this->stages[$index - 1]) : null,
                    ]));
                }
            } else {
                // This stage is AFTER the target stage -> delete record if it exists
                $modelClass::where('customer_id', $customerId)->delete();
            }
        }
    }
}
