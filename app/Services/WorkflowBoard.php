<?php

namespace App\Services;

use Illuminate\Support\Collection;

class WorkflowBoard
{
    public function defaultStages(Collection $stages): array
    {
        $names = $stages->filter(fn ($stage) => !isset($stage->is_active) || $stage->is_active)
            ->map(fn ($stage) => strtolower(trim(str_replace(['_', '-'], ' ', $stage->name))))
            ->all();
        $defaults = [];
        foreach (['pending' => 'Pending', 'in_progress' => 'In Progress', 'completed' => 'Completed'] as $key => $name) {
            if (!in_array(strtolower($name), $names, true)) {
                $defaults[] = ['id' => $key, 'key' => $key, 'name' => $name, 'is_system' => true];
            }
        }
        return $defaults;
    }

    public function group(Collection $records, Collection $stages): array
    {
        if ($stages->contains(fn ($stage) => !empty($stage->managed))) {
            $columns = $stages->map(fn ($stage) => ['name' => $stage->name, 'key' => 'stage_'.$stage->id])->all();
            $groups = collect($columns)->mapWithKeys(fn ($column) => [$column['key'] => collect()])->all();
            $legacy = $stages->filter(fn ($stage) => !empty($stage->system_key))->mapWithKeys(fn ($stage) => [$stage->system_key => 'stage_'.$stage->id]);
            foreach ($records as $record) {
                $saved = 'stage_'.$record->current_stage_id;
                $key = $record->current_stage_id !== null && isset($groups[$saved]) ? $saved : ($legacy[$record->status] ?? ($columns[0]['key'] ?? null));
                if ($key !== null) $groups[$key]->push($record);
            }
            return [$columns, $groups];
        }
        $columns = [];
        $groups = [];
        $legacyKeys = [];
        $statuses = ['pending' => 'Pending', 'in_progress' => 'In Progress', 'completed' => 'Completed'];

        foreach ($stages as $stage) {
            $key = 'stage_' . $stage->id;
            $columns[] = ['name' => $stage->name, 'key' => $key];
            $groups[$key] = collect();
            $normalizedName = strtolower(trim(str_replace(['_', '-'], ' ', $stage->name)));
            foreach ($statuses as $status => $label) {
                if ($normalizedName === strtolower($label)) {
                    $legacyKeys[$status] ??= $key;
                }
            }
        }

        // A saved stage is authoritative, including for completed records.
        // Records predating custom stages retain their status instead of moving
        // to whichever column happens to be first or last today.
        foreach ($records as $record) {
            $savedKey = 'stage_' . $record->current_stage_id;
            $key = $record->current_stage_id !== null && isset($groups[$savedKey])
                ? $savedKey
                : ($legacyKeys[$record->status] ?? $record->status);
            if (!isset($groups[$key])) {
                $groups[$key] = collect();
            }
            $groups[$key]->push($record);
        }

        $fallbackColumns = [];
        foreach ($this->defaultStages($stages) as $default) {
            $status = $default['key'];
            $fallbackColumns[] = ['name' => $default['name'], 'key' => $status];
            $groups[$status] ??= collect();
        }

        return [array_merge($fallbackColumns, $columns), $groups];
    }
}
