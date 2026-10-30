<?php

namespace App\Http\Controllers\Api;

use App\Models\ModuleStatusHistory;
use App\Models\Pipeline;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class PipelineController extends ApiBaseController
{
    public function index(Request $request)
    {
        $search = trim((string) $request->get('search', ''));

        $query = Pipeline::with(['customer', 'stage', 'creator']);

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('pipeline_name', 'like', "%{$search}%")
                    ->orWhere('status', 'like', "%{$search}%")
                    ->orWhereHas('customer', function ($cq) use ($search) {
                        $cq->where('name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('stage', function ($sq) use ($search) {
                        $sq->where('name', 'like', "%{$search}%");
                    });
            });
        }

        $pipelines = $query->latest()->paginate(10);

        return response()->json([
            'success' => true,
            'message' => 'Pipelines retrieved successfully',
            'data' => $pipelines,
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), $this->rules(), $this->messages());

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();

        if (Schema::hasColumn('pipelines', 'created_by') && empty($data['created_by'])) {
            $data['created_by'] = Auth::id();
        }

        $pipeline = Pipeline::create($data);
        app(\App\Services\UserLogService::class)->created($pipeline);

        return response()->json([
            'success' => true,
            'message' => 'Pipeline created successfully.',
            'data' => $pipeline->fresh(['customer', 'stage', 'creator']),
            'history_entry' => null,
            'redirect' => route('pipeline.index'),
        ], 201);
    }

    public function show(Pipeline $pipeline)
    {
        $pipeline->load(['customer', 'stage', 'creator']);

        return response()->json([
            'success' => true,
            'message' => 'Pipeline retrieved successfully.',
            'data' => $pipeline,
        ]);
    }

    public function update(Request $request, Pipeline $pipeline)
    {
        $originalStatus = $pipeline->status;
        $validator = Validator::make($request->all(), $this->rules(), $this->messages());

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();
        $pipeline->update($data);
        if (
            (array_key_exists('status', $data) && $data['status'] !== $originalStatus)
            || filled($data['status_comment'] ?? null)
        ) {
            $historyEntry = $this->recordStatusHistory($pipeline, $data['status'] ?? $pipeline->status, $data['status_comment'] ?? null);
        }
        app(\App\Services\UserLogService::class)->updated($pipeline);

        return response()->json([
            'success' => true,
            'message' => 'Pipeline updated successfully.',
            'data' => $pipeline->fresh(['customer', 'stage', 'creator']),
            'history_entry' => $this->serializeHistoryEntry($historyEntry ?? null),
            'redirect' => route('pipeline.index', $pipeline),
        ]);
    }

    public function destroy(Pipeline $pipeline)
    {
        app(\App\Services\UserLogService::class)->deleted($pipeline);
        $pipeline->delete();

        return response()->json([
            'success' => true,
            'message' => 'Pipeline deleted successfully.',
        ]);

    }

    private function rules(): array
    {
        return [
            'pipeline_name' => ['required', 'string', 'max:255'],
            'customer_id' => ['required', 'integer', 'exists:customers,id'],
            'stage_id' => ['required', 'integer', 'exists:stages,id,is_active,1'],
            'status' => ['required', Rule::in(['in_progress', 'paused', 'completed'])],
            'description' => ['nullable', 'string'],
            'status_comment' => ['nullable', 'string', 'max:2000'],
        ];
    }

    private function messages(): array
    {
        return [
            'pipeline_name.required' => 'Pipeline name is required.',
            'customer_id.required' => 'Customer is required.',
            'stage_id.required' => 'Stage is required.',
            'status.required' => 'Status is required.',
        ];
    }

    private function recordStatusHistory(Pipeline $pipeline, ?string $status, ?string $comment): ?ModuleStatusHistory
    {
        if (!$status && !filled($comment)) {
            return null;
        }

        return ModuleStatusHistory::create([
            'historable_type' => Pipeline::class,
            'historable_id' => $pipeline->id,
            'status' => $status,
            'comment' => filled($comment) ? $comment : null,
            'updated_by' => Auth::id(),
        ]);
    }

    private function serializeHistoryEntry(?ModuleStatusHistory $history): ?array
    {
        if (!$history) {
            return null;
        }

        return [
            'status_label' => $history->status ? ucwords(str_replace('_', ' ', $history->status)) : '-',
            'comment' => $history->comment ?: '-',
            'updated_by' => $history->updater?->name ?? Auth::user()?->name ?? 'System',
            'created_at' => $history->created_at?->timezone('Asia/Kolkata')->format('d M Y h:i A') ?? now()->timezone('Asia/Kolkata')->format('d M Y h:i A'),
        ];
    }
}
