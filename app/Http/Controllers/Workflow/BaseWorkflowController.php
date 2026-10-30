<?php
 
namespace App\Http\Controllers\Workflow;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\WorkflowService;

abstract class BaseWorkflowController extends Controller
{
    protected $modelClass;
    protected $storeRequestClass;
    protected $updateRequestClass;
    protected $resourceClass;

    public function index(Request $request)
    {
        $query = $this->modelClass::with(['customer.city', 'assignedTo'])->has('customer');

        $workflowService = app(\App\Services\WorkflowService::class);
        $stageModelMap = [
            'Document Verification' => \App\Models\DocumentVerification::class,
            'Registration' => \App\Models\Registration::class,
            'Meter Charge' => \App\Models\Feasibility::class,
            'Finance' => \App\Models\Finance::class,
            'Waiting Floor' => \App\Models\WaitingFloor::class,
            'Dispatch' => \App\Models\Dispatch::class,
            'Installations' => \App\Models\Installation::class,
            'Intimation' => \App\Models\Intimation::class,
            'Net Metering' => \App\Models\NetMetering::class,
            'Subsidy' => \App\Models\Subsidy::class,
        ];

        // Search logic
        $request->validate(['search' => ['nullable', 'string', 'max:255']]);
        $search = trim((string) $request->input('search', ''));
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('aspl_id', 'like', "%{$search}%")
                  ->orWhereHas('customer', function ($cq) use ($search) {
                      $cq->where('name', 'like', "%{$search}%")
                         ->orWhere('phone', 'like', "%{$search}%")
                         ->orWhereHas('city', fn ($city) => $city->where('name', 'like', "%{$search}%"));
                  });
            });
        }

        // Determine workflow type from request path or model
        $workflowType = array_search($this->modelClass, \App\Services\WorkflowStageCatalog::TYPES, true); // api/workflow/document-verification -> segment 3 is document-verification

        // Filter by finance_id / installation_master_id if provided
        if ($financeId = $request->input('finance_id')) {
            if ($workflowType === 'installations') {
                $query->whereHas('customer', function ($q) use ($financeId) {
                    $q->where('installation_master_id', $financeId);
                });
            } else {
                $query->whereHas('customer', function ($q) use ($financeId) {
                    $q->where('finance_id', $financeId);
                });
            }
        }

        // Get all records for the view
        $records = $query->latest()->get();

        $catalog = app(\App\Services\WorkflowStageCatalog::class);
        $masterId = in_array($workflowType, ['finance', 'installations']) && $request->filled('finance_id') ? (int) $request->finance_id : null;
        $dynamicStages = $catalog->stages($catalog->context($workflowType, $masterId));
        if (in_array($workflowType, ['finance', 'installations'])) {
            // IDs belong to different tables in All vs master-specific views.
            $records = $records->map(function ($record) use ($masterId) {
                if (($masterId && $record->current_stage_type === 'workflow') || (!$masterId && $record->current_stage_type !== 'workflow')) {
                    $record = clone $record;
                    $record->current_stage_id = null;
                }
                return $record;
            });
        }

        [$columns, $groups] = app(\App\Services\WorkflowBoard::class)->group($records, $dynamicStages);
        $data = [];
        foreach ($columns as $column) {
            $stageRecords = $groups[$column['key']];
            $data[$column['key']] = [
                'count' => $stageRecords->count(),
                'records' => $this->resourceClass::collection($stageRecords),
            ];
        }
        return response()->json([
            'success' => true,
            'message' => 'Data retrieved successfully.',
            'columns' => $columns,
            'data' => $data
        ]);
    }

    public function store(Request $request)
    {
        $validatedRequest = app($this->storeRequestClass);
        $validatedRequest->setContainer(app())->setRedirector(app(\Illuminate\Routing\Redirector::class));
        $validatedRequest->validateResolved();

        $data = $validatedRequest->validated();
        $data = array_merge($data, app(\App\Services\WorkflowStageCatalog::class)->initialState($this->modelClass, (int) $data['customer_id']));
        $record = $this->modelClass::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Record created successfully.',
            'data' => new $this->resourceClass($record->load(['customer', 'assignedTo']))
        ], 201);
    }

    public function show($id)
    {
        $record = $this->modelClass::with(['customer', 'assignedTo'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'message' => 'Record retrieved successfully.',
            'data' => new $this->resourceClass($record)
        ]);
    }

    public function update(Request $request, $id)
    {
        $validatedRequest = app($this->updateRequestClass);
        $validatedRequest->setContainer(app())->setRedirector(app(\Illuminate\Routing\Redirector::class));
        $validatedRequest->validateResolved();

        $record = $this->modelClass::findOrFail($id);
        $oldStatus = $record->status;
        $oldStageId = $record->current_stage_id;
        
        $updateData = $validatedRequest->validated();

        // Moving to a legacy status must clear any previous custom stage.
        if (isset($updateData['status']) && !str_starts_with($updateData['status'], 'stage_')) {
            $updateData['current_stage_id'] = null;
        }

        if (isset($updateData['status']) && str_starts_with($updateData['status'], 'stage_')) {
            $workflowType = array_search($this->modelClass, \App\Services\WorkflowStageCatalog::TYPES, true);
            $catalog = app(\App\Services\WorkflowStageCatalog::class);
            $masterId = null;
            if (in_array($workflowType, ['finance', 'installations']) && $request->filled('finance_id')) {
                $record->loadMissing('customer');
                $masterId = (int) $request->finance_id;
                $customerMaster = $workflowType === 'finance' ? $record->customer?->finance_id : $record->customer?->installation_master_id;
                abort_unless($masterId === (int) $customerMaster, 422, 'This stage belongs to a different customer master.');
            }
            $stages = $catalog->stages($catalog->context($workflowType, $masterId));
            $stageId = (int) substr($updateData['status'], 6);
            $target = $stages->firstWhere('id', $stageId);
            if (!$target) throw \Illuminate\Validation\ValidationException::withMessages(['status' => 'Choose an active stage from this workflow.']);
            $updateData['current_stage_id'] = $stageId;
            $updateData['status'] = $stages->last()->id === $stageId ? 'completed' : ($stages->first()->id === $stageId ? 'pending' : 'in_progress');
            if (in_array($workflowType, ['finance', 'installations'])) $updateData['current_stage_type'] = $masterId ? 'master' : 'workflow';
        }

        $record->update($updateData);

        // Check if status changed to completed, and trigger workflow progression
        if (($oldStatus !== 'completed' || $oldStageId !== $record->current_stage_id) && $record->status === 'completed') {
            $workflowService = app(WorkflowService::class);
            $workflowService->advanceIfCompleted($record, $this->modelClass);
        }

        return response()->json([
            'success' => true,
            'message' => 'Record updated successfully.',
            'data' => new $this->resourceClass($record->fresh(['customer', 'assignedTo']))
        ]);
    }

    public function destroy($id)
    {
        $record = $this->modelClass::findOrFail($id);
        $record->delete();

        return response()->json([
            'success' => true,
            'message' => 'Record deleted successfully.',
            'data' => null
        ]);
    }
}
