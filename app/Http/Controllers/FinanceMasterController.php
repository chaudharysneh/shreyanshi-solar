<?php

namespace App\Http\Controllers;

use App\Models\FinanceMaster;
use Illuminate\Http\Request;

class FinanceMasterController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $financeMasters = FinanceMaster::orderBy('name')->paginate(15);

        return view('masters.finance_masters.index', compact('financeMasters'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('masters.finance_masters.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:finance_masters,name'],
            'description' => ['nullable', 'string', 'max:255'],
            'stages' => ['nullable', 'array'],
            'stages.*.name' => ['required', 'string', 'max:255'],
        ]);

        $financeMaster = FinanceMaster::create([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'is_active' => $request->boolean('is_active'),
        ]);

        if (isset($data['stages'])) {
            foreach ($data['stages'] as $index => $stage) {
                $financeMaster->stages()->create([
                    'stage_name' => $stage['name'],
                    'stage_order' => $index + 1,
                ]);
            }
        }

        return redirect()->route('masters.finance_masters.index')->with('success', 'Finance master option created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        return redirect()->route('masters.finance_masters.index');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $financeMaster = FinanceMaster::with('stages')->findOrFail($id);

        return view('masters.finance_masters.edit', compact('financeMaster'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $financeMaster = FinanceMaster::findOrFail($id);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:finance_masters,name,'.$id],
            'description' => ['nullable', 'string', 'max:255'],
            'stages' => ['nullable', 'array'],
            'stages.*.id' => ['nullable', 'integer'],
            'stages.*.name' => ['required', 'string', 'max:255'],
        ]);

        $financeMaster->update([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'is_active' => $request->boolean('is_active'),
        ]);

        $submittedStageIds = [];
        if (isset($data['stages'])) {
            foreach ($data['stages'] as $index => $stageData) {
                if (!empty($stageData['id'])) {
                    // Update existing
                    $stage = $financeMaster->stages()->find($stageData['id']);
                    if ($stage) {
                        $stage->update([
                            'stage_name' => $stageData['name'],
                            'stage_order' => $index + 1,
                        ]);
                        $submittedStageIds[] = $stage->id;
                    }
                } else {
                    // Create new
                    $newStage = $financeMaster->stages()->create([
                        'stage_name' => $stageData['name'],
                        'stage_order' => $index + 1,
                    ]);
                    $submittedStageIds[] = $newStage->id;
                }
            }
        }

        // Check for deleted stages
        $stagesToDelete = $financeMaster->stages()->whereNotIn('id', $submittedStageIds)->get();
        foreach ($stagesToDelete as $stage) {
            // Check if it's being used by any finance record
            $isInUse = \App\Models\Finance::where('current_stage_id', $stage->id)->exists();
            if ($isInUse) {
                return back()->withInput()->withErrors(['stages' => 'Cannot delete stage "' . $stage->stage_name . '" because there are customers currently assigned to it.']);
            }
            $stage->delete();
        }

        return redirect()->route('masters.finance_masters.index')->with('success', 'Finance master option updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $financeMaster = FinanceMaster::with('stages')->findOrFail($id);
        
        // Check if any stage is in use
        $stageIds = $financeMaster->stages->pluck('id');
        if ($stageIds->isNotEmpty()) {
            $isInUse = \App\Models\Finance::whereIn('current_stage_id', $stageIds)->exists();
            if ($isInUse) {
                return back()->with('error', 'Cannot delete finance option because some of its stages are in use by customers.');
            }
        }
        
        $financeMaster->delete();

        return redirect()->route('masters.finance_masters.index')->with('success', 'Finance master option deleted successfully.');
    }
}
