<?php

namespace App\Http\Controllers\Api\Masters;

use App\Http\Controllers\Controller;
use App\Models\LeadStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class LeadStatusController extends Controller
{
    public function store(Request $request)
    {
        if (!$request->user()->isAdmin()) {
            return response()->json(['message' => 'Only administrators can manage lead statuses.'], 403);
        }
        $data = $request->validate(['name' => ['required', 'string', 'max:100', 'unique:lead_statuses,name']]);
        // Stable codes keep existing leads and workflow meanings intact when labels change.
        $data['code'] = 'custom_'.Str::uuid();
        return response()->json(['data' => LeadStatus::create($data)], 201);
    }

    public function update(Request $request, LeadStatus $leadStatus)
    {
        if (!$request->user()->isAdmin()) {
            return response()->json(['message' => 'Only administrators can manage lead statuses.'], 403);
        }
        $data = $request->validate(['name' => [
            'required', 'string', 'max:100', Rule::unique('lead_statuses', 'name')->ignore($leadStatus->id),
        ]]);
        $leadStatus->update($data);
        return response()->json(['data' => $leadStatus]);
    }

    public function destroy(Request $request, LeadStatus $leadStatus)
    {
        if (!$request->user()->isAdmin()) {
            return response()->json(['message' => 'Only administrators can manage lead statuses.'], 403);
        }
        return DB::transaction(function () use ($leadStatus) {
            $status = LeadStatus::whereKey($leadStatus->id)->lockForUpdate()->firstOrFail();
            // The initial status is also the default for integrations which omit status.
            if ($status->code === 'new'
                || DB::table('leads')->where('status', $status->code)->exists()
                || DB::table('lead_status_histories')->where('status', $status->code)->exists()) {
                return response()->json(['message' => 'This status is a default or is already used by leads or history and cannot be deleted.'], 422);
            }
            $status->delete();
            return response()->json(['message' => 'Lead status deleted.']);
        });
    }
}
