<?php
 
namespace App\Http\Controllers\Workflow;

use App\Models\Intimation;
use App\Http\Requests\Workflow\StoreIntimationRequest;
use App\Http\Requests\Workflow\UpdateIntimationRequest;
use App\Http\Resources\Workflow\IntimationResource;

class IntimationController extends BaseWorkflowController
{
    public function __construct()
    {
        $this->modelClass = Intimation::class;
        $this->storeRequestClass = StoreIntimationRequest::class;
        $this->updateRequestClass = UpdateIntimationRequest::class;
        $this->resourceClass = IntimationResource::class;
    }

    public function uploadFile(\Illuminate\Http\Request $request, $id)
    {
        $request->validate([
            'field' => 'required|string',
            'file' => 'required|file|max:10240'
        ]);
        
        $record = Intimation::findOrFail($id);
        $path = $request->file('file')->store('intimations', 'public');
        
        $field = $request->field;
        $record->update([
            $field . '_file' => $path,
            $field . '_status' => 'uploaded'
        ]);

        // Check if all uploaded
        if (
            in_array($record->self_certificate_status, ['uploaded', 'completed']) && 
            in_array($record->gps_photo_status, ['uploaded', 'completed']) && 
            in_array($record->dcr_certificate_status, ['uploaded', 'completed']) && 
            in_array($record->model_agreement_status, ['uploaded', 'completed']) &&
            $record->status !== 'completed' &&
            app(\App\Services\WorkflowStageCatalog::class)->stages(app(\App\Services\WorkflowStageCatalog::class)->context('intimation'))->count() <= 2
        ) {
            $lastStage = app(\App\Services\WorkflowStageCatalog::class)->stages(app(\App\Services\WorkflowStageCatalog::class)->context('intimation'))->last();
            $record->update(['status' => 'completed', 'current_stage_id' => $lastStage?->id]);
            $workflowService = app(\App\Services\WorkflowService::class);
            $workflowService->advanceIfCompleted($record, Intimation::class);
        }
        
        return response()->json(['success' => true]);
    }
}