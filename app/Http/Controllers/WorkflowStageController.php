<?php

namespace App\Http\Controllers;

use App\Services\WorkflowStageCatalog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class WorkflowStageController extends Controller
{
    public function index(Request $request, WorkflowStageCatalog $catalog)
    {
        return response()->json(['success' => true, 'data' => $catalog->stages($catalog->scope($request))]);
    }

    public function store(Request $request, WorkflowStageCatalog $catalog)
    {
        $data = $request->validate(['name' => 'required|string|max:255']);
        $row = $catalog->save($catalog->scope($request), $data);
        return response()->json(['success' => true, 'message' => 'Stage added successfully.', 'data' => $row], 201);
    }

    public function update(Request $request, int $id, WorkflowStageCatalog $catalog)
    {
        $data = $request->validate(['name' => 'sometimes|required|string|max:255', 'order' => 'sometimes|required|integer|min:1']);
        $row = $catalog->save($catalog->scope($request), $data, $id);
        return response()->json(['success' => true, 'data' => $row]);
    }

    public function destroy(Request $request, int $id, WorkflowStageCatalog $catalog)
    {
        $catalog->delete($catalog->scope($request), $id);
        return response()->json(['success' => true, 'message' => 'Stage deleted.']);
    }

    public function updateOrder(Request $request, WorkflowStageCatalog $catalog)
    {
        $data = $request->validate(['stages' => 'required|array|min:1', 'stages.*.id' => 'required|integer|distinct', 'stages.*.order' => 'required|integer|min:1|distinct']);
        $scope = $catalog->scope($request);
        DB::transaction(function () use ($catalog, $scope, $data) {
            $ids = $catalog->query($scope)->where('is_active', true)->lockForUpdate()->pluck('id')->sort()->values()->all();
            if ($ids !== collect($data['stages'])->pluck('id')->sort()->values()->all()) throw ValidationException::withMessages(['stages' => 'Provide every stage from this workflow only.']);
            foreach (collect($data['stages'])->sortBy('order')->values() as $index => $stage) $catalog->query($scope)->whereKey($stage['id'])->update([$scope['order'] => $index + 1]);
        });
        return response()->json(['success' => true]);
    }
}
