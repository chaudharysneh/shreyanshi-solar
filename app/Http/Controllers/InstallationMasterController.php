<?php

namespace App\Http\Controllers;

use App\Models\InstallationMaster;
use Illuminate\Http\Request;

class InstallationMasterController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $installationMasters = InstallationMaster::orderBy('name')->paginate(15);

        return view('masters.installation_masters.index', compact('installationMasters'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('masters.installation_masters.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:installation_masters,name'],
            'description' => ['nullable', 'string', 'max:255'],
            'stages' => ['nullable', 'array'],
            'stages.*.name' => ['required', 'string', 'max:255'],
        ]);

        $installationMaster = InstallationMaster::create([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'is_active' => $request->boolean('is_active'),
        ]);

        if (isset($data['stages'])) {
            foreach ($data['stages'] as $index => $stage) {
                $installationMaster->stages()->create([
                    'stage_name' => $stage['name'],
                    'stage_order' => $index + 1,
                ]);
            }
        }

        return redirect()->route('masters.installation_masters.index')->with('success', 'Installation type created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        return redirect()->route('masters.installation_masters.index');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $installationMaster = InstallationMaster::with('stages')->findOrFail($id);

        return view('masters.installation_masters.edit', compact('installationMaster'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $installationMaster = InstallationMaster::findOrFail($id);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:installation_masters,name,'.$id],
            'description' => ['nullable', 'string', 'max:255'],
            'stages' => ['nullable', 'array'],
            'stages.*.id' => ['nullable', 'integer'],
            'stages.*.name' => ['required', 'string', 'max:255'],
        ]);

        $installationMaster->update([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'is_active' => $request->boolean('is_active'),
        ]);

        $submittedStageIds = [];
        if (isset($data['stages'])) {
            foreach ($data['stages'] as $index => $stageData) {
                if (!empty($stageData['id'])) {
                    // Update existing
                    $stage = $installationMaster->stages()->find($stageData['id']);
                    if ($stage) {
                        $stage->update([
                            'stage_name' => $stageData['name'],
                            'stage_order' => $index + 1,
                        ]);
                        $submittedStageIds[] = $stage->id;
                    }
                } else {
                    // Create new
                    $newStage = $installationMaster->stages()->create([
                        'stage_name' => $stageData['name'],
                        'stage_order' => $index + 1,
                    ]);
                    $submittedStageIds[] = $newStage->id;
                }
            }
        }

        // Check for deleted stages
        $stagesToDelete = $installationMaster->stages()->whereNotIn('id', $submittedStageIds)->get();
        foreach ($stagesToDelete as $stage) {
            // Check if it's being used by any installation record
            $isInUse = \App\Models\Installation::where('current_stage_id', $stage->id)->exists();
            if ($isInUse) {
                return back()->withInput()->withErrors(['stages' => 'Cannot delete stage "' . $stage->stage_name . '" because there are customers currently assigned to it.']);
            }
            $stage->delete();
        }

        return redirect()->route('masters.installation_masters.index')->with('success', 'Installation type updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $installationMaster = InstallationMaster::with('stages')->findOrFail($id);
        
        // Check if any stage is in use
        $stageIds = $installationMaster->stages->pluck('id');
        if ($stageIds->isNotEmpty()) {
            $isInUse = \App\Models\Installation::whereIn('current_stage_id', $stageIds)->exists();
            if ($isInUse) {
                return back()->with('error', 'Cannot delete installation type because some of its stages are in use by customers.');
            }
        }
        
        $installationMaster->delete();

        return redirect()->route('masters.installation_masters.index')->with('success', 'Installation type deleted successfully.');
    }
}
