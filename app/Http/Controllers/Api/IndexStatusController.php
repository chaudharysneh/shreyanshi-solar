<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models;
use Illuminate\Http\Request;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class IndexStatusController extends Controller
{
    private const MODULES = [
        'leads' => [Models\Lead::class, 'edit_leads', []],
        'follow-ups' => [Models\FollowUp::class, 'edit_followups', ['pending', 'resheduled', 'completed', 'cancelled']],
        'meetings' => [Models\Meeting::class, 'edit_meetings', ['scheduled', 'completed', 'cancelled']],
        'tickets' => [Models\SupportTicket::class, 'edit_tickets', ['Open', 'In Progress', 'Resolved', 'Closed']],
        'projects' => [Models\Project::class, 'edit_projects', ['pending', 'ongoing', 'completed', 'canceled']],
        'pipelines' => [Models\Pipeline::class, 'edit_pipeline', ['in_progress', 'paused', 'completed']],
        'services' => [Models\Service::class, 'edit_services', ['active', 'inactive']],
        'products' => [Models\Product::class, 'edit_products', ['active', 'inactive']],
        'deals' => [Models\Deal::class, 'edit_deals', []],
        'estimates' => [Models\Estimate::class, 'edit_estimates', ['pending', 'approved', 'rejected']],
        'invoices' => [Models\Invoice::class, 'edit_invoices', ['paid', 'unpaid']],
        'stages' => [Models\Stage::class, 'edit_stages', []],
        'customers' => [Models\Customer::class, 'edit_customers', []],
        'users' => [Models\User::class, null, []],
        'lead-sources' => [Models\LeadSource::class, null, []],
        'statuses' => [Models\Status::class, null, []],
        'finance-masters' => [Models\FinanceMaster::class, null, []],
        'installation-masters' => [Models\InstallationMaster::class, null, []],
        'countries' => [Models\Country::class, null, []],
        'cities' => [Models\City::class, null, []],
        'agents' => [Models\Agent::class, null, []],
        'currencies' => [Models\Currency::class, null, []],
        'suppliers' => [Models\Supplier::class, null, []],
        'hotels' => [Models\Hotel::class, null, []],
        'room-categories' => [Models\RoomCategory::class, null, []],
        'travel-types' => [Models\TravelType::class, null, []],
        'transport-types' => [Models\TransportType::class, null, []],
    ];

    private const COMMENT_MODULES = ['leads', 'follow-ups', 'meetings', 'tickets'];

    private function field(string $module): string
    {
        return match ($module) {
            'stages' => 'is_active', 'deals' => 'status_id', 'customers' => 'pipeline_stage_id',
            default => self::MODULES[$module][1] === null ? 'is_active' : 'status',
        };
    }

    private function resolve(Request $request, string $module, string $id): array
    {
        abort_unless(isset(self::MODULES[$module]), 404);
        [$class, $permission, $values] = self::MODULES[$module];
        $allowed = $permission === null ? $request->user()->isAdmin() : $request->user()->hasMatrixPermission($permission);
        if (!$allowed) $this->forbidden();
        $record = ($module === 'stages' ? $class::withoutGlobalScope('active') : $class::query())->findOrFail($id);
        if (Gate::getPolicyFor($record) && Gate::denies('update', $record)) $this->forbidden();
        $options = array_combine($values, array_map(fn ($v) => ucwords(str_replace('_', ' ', $v)), $values));
        if ($module === 'leads') $options = Models\LeadStatus::options();
        if ($module === 'deals') $options = Models\Status::dealChoices()->pluck('name', 'id')->all();
        if ($module === 'customers') $options = Models\Stage::where('is_active', true)->orderBy('sort_order')->orderBy('id')->pluck('name', 'id')->all();
        if ($this->field($module) === 'is_active') $options = ['1' => 'Active', '0' => 'Inactive'];
        if ($module === 'follow-ups') $options['resheduled'] = 'Rescheduled';
        return [$record, $options];
    }

    private function forbidden(): never
    {
        throw new HttpResponseException(response()->json(['success' => false, 'message' => 'You do not have permission to change this status.'], 403));
    }

    public function show(Request $request, string $module, string $id)
    {
        [$record, $options] = $this->resolve($request, $module, $id);
        $field = $this->field($module);
        return response()->json([
            'options' => collect($options)->map(fn ($label, $value) => ['value' => (string) $value, 'label' => $label])->values(),
            'value' => (string) ($field === 'is_active' ? (int) $record->$field : $record->$field),
            'requires_comment' => in_array($module, self::COMMENT_MODULES, true),
        ]);
    }

    public function update(Request $request, string $module, string $id)
    {
        [$record, $options] = $this->resolve($request, $module, $id);
        $data = $request->validate([
            'status' => ['required', Rule::in(array_keys($options))],
            'comment' => [in_array($module, self::COMMENT_MODULES, true) ? 'required' : 'nullable', 'string', 'max:2000'],
            'follow_up_at' => $module === 'follow-ups' && $request->input('status') === 'resheduled'
                ? ['required', 'date_format:Y-m-d\TH:i'] : ['exclude'],
        ]);
        $status = $data['status']; $field = $this->field($module);
        $rescheduleAt = isset($data['follow_up_at'])
            ? \Illuminate\Support\Carbon::createFromFormat('!Y-m-d\TH:i', $data['follow_up_at'], config('app.timezone'))->format('Y-m-d H:i:s') : null;
        $current = (string) ($field === 'is_active' ? (int) $record->$field : $record->$field);
        if ($current === (string) $status && (!$rescheduleAt || $record->follow_up_at?->format('Y-m-d H:i:s') === $rescheduleAt)) return response()->json(['success' => true, 'message' => 'Status unchanged.']);
        // Keep existing conversion, billing and pipeline synchronization rules.
        if ($module === 'deals') {
            $request->merge(['status_id' => $status]);
            return app(DealController::class)->updateStatus($request, $record);
        }
        if ($module === 'estimates') return app(EstimateController::class)->updateStatus($request, $id);
        if ($module === 'invoices') return app(InvoiceController::class)->updateStatus($request, $record);
        if ($module === 'customers') {
            $request->merge(['stage_id' => $status]);
            return app(\App\Http\Controllers\CustomerController::class)->updatePipelineStage($request, $record);
        }
        if ($module === 'users') {
            $request->merge(['is_active' => (bool) $status]);
            return app(UserController::class)->updateStatus($request, $record);
        }
        DB::transaction(function () use ($record, $status, $module, $request, $field, $data, $rescheduleAt) {
            $record = $record->newQuery()->when($module === 'stages', fn ($query) => $query->withoutGlobalScope('active'))->lockForUpdate()->findOrFail($record->getKey());
            if ((string) $record->$field === (string) $status && (!$rescheduleAt || $record->follow_up_at?->format('Y-m-d H:i:s') === $rescheduleAt)) return;
            $record->$field = $status;
            if ($rescheduleAt) $record->follow_up_at = $rescheduleAt;
            $record->save();
            $history = ['status' => $status, 'comment' => $data['comment'] ?? null, 'updated_by' => $request->user()->id];
            if ($module === 'leads') Models\LeadStatusHistory::create($history + ['lead_id' => $record->id]);
            elseif ($module === 'follow-ups') Models\FollowUpStatusHistory::create($history + ['follow_up_id' => $record->id]);
            elseif ($module === 'meetings') Models\MeetingStatusHistory::create($history + ['meeting_id' => $record->id]);
            elseif (in_array($module, ['tickets', 'projects', 'pipelines', 'services'], true)) Models\ModuleStatusHistory::create($history + ['historable_type' => $record->getMorphClass(), 'historable_id' => $record->id]);
        });
        return response()->json(['success' => true, 'message' => 'Status updated.']);
    }
}
