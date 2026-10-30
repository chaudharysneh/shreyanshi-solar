<?php

namespace App\Services;

use App\Models;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class WorkflowStageCatalog
{
    public const TYPES = [
        'document-verification' => Models\DocumentVerification::class, 'registration' => Models\Registration::class,
        'feasibility' => Models\Feasibility::class, 'finance' => Models\Finance::class,
        'waiting-floor' => Models\WaitingFloor::class, 'dispatch' => Models\Dispatch::class,
        'installations' => Models\Installation::class, 'intimation' => Models\Intimation::class,
        'net-metering' => Models\NetMetering::class, 'subsidy' => Models\Subsidy::class,
    ];

    public function scope(Request $request): array
    {
        $request->validate(['workflow_type' => ['required', Rule::in(array_keys(self::TYPES))], 'finance_id' => ['nullable', 'integer', 'min:1']]);
        return $this->context($request->workflow_type, $request->filled('finance_id') ? (int) $request->finance_id : null);
    }

    public function context(string $type, ?int $master = null): array
    {
        if ($master && $type === 'finance') {
            Models\FinanceMaster::findOrFail($master);
            return ['type' => $type, 'model' => Models\FinanceStage::class, 'field' => 'finance_master_id', 'value' => $master, 'name' => 'stage_name', 'order' => 'stage_order'];
        }
        if ($master && $type === 'installations') {
            Models\InstallationMaster::findOrFail($master);
            return ['type' => $type, 'model' => Models\InstallationStage::class, 'field' => 'installation_master_id', 'value' => $master, 'name' => 'stage_name', 'order' => 'stage_order'];
        }
        return ['type' => $type, 'model' => Models\WorkflowStage::class, 'field' => 'workflow_type', 'value' => $type, 'name' => 'name', 'order' => 'order'];
    }

    public function query(array $scope)
    {
        return $scope['model']::where($scope['field'], $scope['value']);
    }

    public function ensureDefaults(array $scope): void
    {
        if ($this->query($scope)->whereNotNull('system_key')->exists()) return;
        DB::transaction(function () use ($scope) {
            $rows = $this->query($scope)->orderBy($scope['order'])->orderBy('id')->lockForUpdate()->get();
            if ($rows->contains(fn ($row) => $row->system_key !== null)) return;
            $defaults = $scope['type'] === 'intimation'
                ? ['pending' => 'Intimation Pending', 'completed' => 'Intimation Done']
                : ['pending' => 'Pending', 'in_progress' => 'In Progress', 'completed' => 'Completed'];
            $missing = [];
            foreach ($defaults as $key => $name) {
                $match = $rows->first(fn ($row) => $row->is_active && strtolower(str_replace(['_', '-'], ' ', trim($row->{$scope['name']}))) === strtolower($name));
                if ($match) $match->update(['system_key' => $key]);
                else $missing[] = $scope['model']::create([$scope['field'] => $scope['value'], $scope['name'] => $name, $scope['order'] => 0, 'system_key' => $key, 'is_active' => true]);
            }
            $position = 1;
            foreach (collect($missing)->concat($rows->where('is_active', true)) as $row) $row->update([$scope['order'] => $position++]);
        });
    }

    public function stages(array $scope)
    {
        $this->ensureDefaults($scope);
        return $this->query($scope)->where('is_active', true)->orderBy($scope['order'])->orderBy('id')->get()->map(fn ($row) => (object) [
            'id' => $row->id, 'name' => $row->{$scope['name']}, 'order' => (int) $row->{$scope['order']},
            'system_key' => $row->system_key, 'is_active' => true, 'managed' => true,
        ]);
    }

    public function initialState(string $model, int $customerId): array
    {
        $type = array_search($model, self::TYPES, true);
        if ($type === false) return [];
        $master = null;
        if (in_array($type, ['finance', 'installations'])) {
            $customer = Models\Customer::find($customerId);
            $master = $type === 'finance' ? $customer?->finance_id : $customer?->installation_master_id;
        }
        $first = $this->stages($this->context($type, $master ? (int) $master : null))->first();
        $state = ['current_stage_id' => $first?->id];
        if (in_array($type, ['finance', 'installations'])) $state['current_stage_type'] = $master ? 'master' : 'workflow';
        return $state;
    }

    public function save(array $scope, array $data, ?int $id = null)
    {
        $this->ensureDefaults($scope);
        return DB::transaction(function () use ($scope, $data, $id) {
            $rows = $this->query($scope)->where('is_active', true)->orderBy($scope['order'])->orderBy('id')->lockForUpdate()->get();
            $row = $id ? $rows->firstWhere('id', $id) : null;
            if ($id && !$row) abort(404);
            $name = trim($data['name'] ?? ($row?->{$scope['name']} ?? ''));
            if ($name === '') throw ValidationException::withMessages(['name' => 'Please enter a stage name.']);
            if ($rows->contains(fn ($item) => $item->id !== $id && mb_strtolower(trim($item->{$scope['name']})) === mb_strtolower($name))) {
                throw ValidationException::withMessages(['name' => 'This stage name already exists in this workflow.']);
            }
            if (!$row) $row = $scope['model']::create([$scope['field'] => $scope['value'], $scope['name'] => $name, $scope['order'] => $rows->count() + 1, 'is_active' => true]);
            else $row->update([$scope['name'] => $name]);
            $ids = $rows->pluck('id')->reject(fn ($value) => $value === $row->id)->values()->all();
            $position = isset($data['order']) ? max(1, min((int) $data['order'], count($ids) + 1)) : ($id ? (int) $row->{$scope['order']} : count($ids) + 1);
            array_splice($ids, $position - 1, 0, [$row->id]);
            foreach ($ids as $index => $stageId) $this->query($scope)->whereKey($stageId)->update([$scope['order'] => $index + 1]);
            return $row->fresh();
        });
    }

    public function delete(array $scope, int $id): void
    {
        DB::transaction(function () use ($scope, $id) {
            $rows = $this->query($scope)->where('is_active', true)->lockForUpdate()->get();
            $stage = $rows->firstWhere('id', $id);
            if (!$stage) abort(404);
            if ($rows->count() < 2) throw ValidationException::withMessages(['stage' => 'Keep at least one workflow stage.']);
            $model = self::TYPES[$scope['type']];
            $records = $model::query();
            if ($scope['field'] !== 'workflow_type') $records->whereHas('customer', fn ($query) => $query->where($scope['type'] === 'finance' ? 'finance_id' : 'installation_master_id', $scope['value']));
            if (in_array($scope['type'], ['finance', 'installations'])) {
                if ($scope['field'] === 'workflow_type') $records->where(fn ($query) => $query->where('current_stage_type', 'workflow')->orWhereNull('current_stage_id'));
                else $records->where(fn ($query) => $query->whereNull('current_stage_type')->orWhere('current_stage_type', 'master'));
            }
            $used = (clone $records)->where(function ($query) use ($stage) {
                $query->where('current_stage_id', $stage->id);
                if ($stage->system_key) $query->orWhere(fn ($legacy) => $legacy->whereNull('current_stage_id')->where('status', $stage->system_key));
            })->exists();
            if ($used) throw ValidationException::withMessages(['stage' => 'Move the customers out of this stage before deleting it.']);
            // Retain the system key so removed defaults never reappear on refresh.
            $stage->update(['is_active' => false]);
            foreach ($this->query($scope)->where('is_active', true)->orderBy($scope['order'])->orderBy('id')->get() as $index => $row) $row->update([$scope['order'] => $index + 1]);
        });
    }
}
