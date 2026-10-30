<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LeadSource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class LeadSourceMasterController extends Controller
{
    public function store(Request $request)
    {
        if (!$request->user()->isAdmin()) return response()->json(['message' => 'Only administrators can manage lead sources.'], 403);
        $data = $request->validate(['name' => ['required', 'string', 'max:255', 'unique:lead_sources,name']]);
        return response()->json(['data' => LeadSource::create($data)->fresh()], 201);
    }

    public function update(Request $request, LeadSource $leadSource)
    {
        if (!$request->user()->isAdmin()) return response()->json(['message' => 'Only administrators can manage lead sources.'], 403);
        $data = $request->validate(['name' => ['required', 'string', 'max:255', Rule::unique('lead_sources', 'name')->ignore($leadSource->id)]]);
        DB::transaction(function () use ($leadSource, $data) {
            DB::table('leads')->where('lead_source_id', $leadSource->id)->update(['source' => $data['name']]);
            if (\Illuminate\Support\Facades\Schema::hasColumn('customers','lead_source_id')) DB::table('customers')->where('lead_source_id',$leadSource->id)->update(['source'=>$data['name']]);
            $leadSource->update($data);
        });
        return response()->json(['data' => $leadSource]);
    }

    public function destroy(Request $request, LeadSource $leadSource)
    {
        if (!$request->user()->isAdmin()) return response()->json(['message' => 'Only administrators can manage lead sources.'], 403);
        $usedByCustomer = \Illuminate\Support\Facades\Schema::hasColumn('customers','lead_source_id') && DB::table('customers')->where('lead_source_id',$leadSource->id)->exists();
        if ($usedByCustomer || $leadSource->kind !== 'standard' || DB::table('leads')->where('lead_source_id', $leadSource->id)->orWhere('source', $leadSource->name)->exists()) {
            return response()->json(['message' => 'This source is a default or is already used by leads and cannot be deleted.'], 422);
        }
        $leadSource->delete();
        return response()->json(['message' => 'Lead source deleted.']);
    }
}
