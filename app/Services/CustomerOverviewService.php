<?php

namespace App\Services;

use App\Models;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class CustomerOverviewService
{
    public function build(Models\Customer $customer): array
    {
        $user = auth()->user();
        $visible = function ($records, string $permission) use ($user) {
            if (!$user->hasMatrixPermission($permission)) return collect();
            return $records->filter(fn ($record) => !Gate::getPolicyFor($record) || $user->can('view', $record))->values();
        };
        $related = function (string $model) use ($customer) {
            $instance = new $model;
            if (!Schema::hasTable($instance->getTable())) return collect();
            return $model::where('customer_id', $customer->id)->latest()->get();
        };
        $estimates = $visible($related(Models\Estimate::class)->filter(fn ($row) => !$row->isDeleted), 'view_estimates');
        $invoices = $visible($related(Models\Invoice::class), 'view_invoices');
        $projects = $visible($related(Models\Project::class), 'view_projects');
        $sales = $visible($related(Models\Sales::class)->filter(fn ($row) => !$row->isDeleted), 'view_products');
        $purchases = $visible($related(Models\Purchase::class)->filter(fn ($row) => !$row->isDeleted), 'view_products');
        $bookings = $visible($related(Models\Booking::class), 'view_bookings');
        $messages = $visible($related(Models\SmsLog::class), 'view_sms');
        $pipelines = $visible($related(Models\Pipeline::class), 'view_pipeline');
        $tickets = $visible($related(Models\SupportTicket::class), 'view_tickets');
        $leads = $visible(Models\Lead::where('converted_customer_id', $customer->id)->latest()->get(), 'view_leads');
        $meetings = $visible($customer->meetings, 'view_meetings');
        $deals = $visible($customer->deals, 'view_deals');
        $followUps = Models\FollowUp::with('assignedUser')->where(function ($query) use ($customer, $leads) {
            $query->whereIn('lead_id', $leads->pluck('id'));
            if (Schema::hasColumn('follow_ups', 'customer_id')) $query->orWhere('customer_id', $customer->id);
        })->latest('follow_up_at')->get();
        $followUps = $visible($followUps, 'view_followups');
        $tasks = $visible(Models\Task::with('assignedUser')->where(function ($query) use ($customer, $projects, $estimates) {
            $query->where(function ($direct) use ($customer) {
                $direct->where('related_type', 'customer')->where('related_id', $customer->id);
            })->orWhereIn('project_id', $projects->pluck('id'))->orWhereIn('estimate_id', $estimates->pluck('estimate_id'));
        })->latest()->get(), 'view_tasks');
        $customer->setRelation('meetings', $meetings);
        $customer->setRelation('deals', $deals);
        $customer->setRelation('followUps', $followUps);

        $sections = [
            'estimates' => ['label' => 'Estimates & Quotations', 'records' => $estimates, 'name' => 'estimate_name', 'number' => 'estimate_no', 'date' => 'estimate_date', 'route' => 'estimates.show'],
            'invoices' => ['label' => 'Invoices', 'records' => $invoices, 'name' => 'invoice_name', 'number' => 'invoice_no', 'date' => 'invoice_date', 'route' => 'invoices.show'],
            'tasks' => ['label' => 'Tasks', 'records' => $tasks, 'name' => 'title', 'date' => 'due_date', 'route' => 'tasks.show'],
            'tickets' => ['label' => 'Support Tickets', 'records' => $tickets, 'name' => 'ticket_name', 'date' => 'created_at', 'route' => 'tickets.show'],
            'sales' => ['label' => 'Material Out', 'records' => $sales, 'name' => 'invoice_name', 'number' => 'invoice_no', 'date' => 'invoice_date', 'route' => 'sales.show'],
            'pipelines' => ['label' => 'Pipeline Records', 'records' => $pipelines, 'name' => 'pipeline_name', 'date' => 'created_at', 'route' => 'pipeline.show'],
            'purchases' => ['label' => 'Material In', 'records' => $purchases, 'name' => 'invoice_name', 'number' => 'invoice_no', 'date' => 'invoice_date', 'route' => 'purchases.show'],
            'bookings' => ['label' => 'Bookings', 'records' => $bookings, 'name' => 'booking_no', 'date' => 'travel_start_date', 'route' => 'bookings.show'],
            'messages' => ['label' => 'SMS History', 'records' => $messages, 'name' => 'message_body', 'date' => 'send_date', 'route' => null],
            'leads' => ['label' => 'Converted Leads', 'records' => $leads, 'name' => 'name', 'date' => 'created_at', 'route' => 'leads.show'],
        ];

        $workflow = [];
        foreach (['DocumentVerification', 'Registration', 'Feasibility', 'Finance', 'WaitingFloor', 'Dispatch', 'Installation', 'Intimation', 'NetMetering', 'Subsidy'] as $type) {
            $model = 'App\\Models\\'.$type;
            $rows = $related($model);
            if ($rows->isNotEmpty()) {
                $rows->load('assignedTo');
                if (method_exists($rows->first(), 'currentStage')) $rows->load('currentStage');
            }
            $workflow[$type] = $rows;
        }

        $documents = collect();
        $canManageDocuments = function ($record) use ($user) {
            $permission = match (get_class($record)) {
                Models\Customer::class => 'edit_customers', Models\Estimate::class => 'edit_estimates',
                Models\Invoice::class => 'edit_invoices', Models\Project::class => 'edit_projects',
                Models\Task::class => 'edit_tasks', Models\Lead::class => 'edit_leads',
                Models\Deal::class => 'edit_deals', Models\Sales::class, Models\Purchase::class => 'edit_products',
                default => null,
            };
            return ($permission ? $user->hasMatrixPermission($permission) : $user->isAdmin())
                && (!Gate::getPolicyFor($record) || $user->can('update', $record));
        };
        $addDocument = function ($path, $name, $source, $uploadedAt = null, array $metadata = []) use ($documents) {
            if (!is_string($path) || $path === '') return;
            $key = hash('sha256', $source.'|'.$path);
            $documents->put($key, array_merge([
                'key' => $key, 'path' => $path, 'name' => $name ?: basename($path),
                'source' => $source, 'uploaded_at' => $uploadedAt,
                'available' => Storage::disk('public')->exists($path),
                'category' => null, 'label' => null, 'owner' => null,
                'field' => null, 'can_delete' => false,
            ], $metadata));
        };
        foreach ((array) $customer->customer_docs as $doc) {
            $addDocument($doc['path'] ?? null, $doc['original_name'] ?? null, 'Customer', $doc['uploaded_at'] ?? null, [
                'category' => $doc['category'] ?? null, 'label' => $doc['label'] ?? null,
                'owner' => $customer, 'field' => 'customer_docs',
                'can_delete' => $user->hasMatrixPermission('edit_customers') && $user->can('update', $customer),
            ]);
        }
        foreach (['Estimate' => $estimates, 'Invoice' => $invoices, 'Material Out' => $sales, 'Material In' => $purchases] as $label => $records) {
            foreach ($records as $record) {
                $source = $label.' #'.($record->estimate_no ?: $record->invoice_no ?: $record->getKey());
                foreach ((array) $record->customer_docs as $doc) {
                    $permission = match ($label) {
                        'Estimate' => 'edit_estimates', 'Invoice' => 'edit_invoices', default => 'edit_products',
                    };
                    $metadata = [
                        'owner' => $record, 'field' => 'customer_docs',
                        'can_delete' => $user->hasMatrixPermission($permission)
                            && (!Gate::getPolicyFor($record) || $user->can('update', $record)),
                        'category' => is_array($doc) ? ($doc['category'] ?? null) : null,
                        'label' => is_array($doc) ? ($doc['label'] ?? null) : null,
                    ];
                    if (is_array($doc)) $addDocument($doc['path'] ?? null, $doc['original_name'] ?? null, $source, $doc['uploaded_at'] ?? null, $metadata);
                    elseif (is_string($doc)) $addDocument($doc, null, $source, null, $metadata);
                }
                $attachment = $record->attach_file;
                if (is_string($attachment) && $attachment !== '' && !str_contains($attachment, '/')) {
                    $attachment = $record->getTable().'/'.$attachment;
                }
                $addDocument($attachment, null, $source.' attachment', $record->created_at, [
                    'owner' => $record, 'field' => 'attach_file', 'can_delete' => $canManageDocuments($record),
                ]);
            }
        }
        $owners = collect([$customer])->merge($projects)->merge($tasks)->merge($leads)->merge($deals)->merge($estimates)->merge($invoices)->merge($sales)->merge($purchases)->merge($pipelines)->merge($meetings)->merge($followUps)->merge($bookings);
        foreach ($workflow as $rows) $owners = $owners->merge($rows);
        if (Schema::hasTable('documents')) {
            $docs = Models\Document::where(function ($query) use ($owners) {
                foreach ($owners->groupBy(fn ($owner) => $owner->getMorphClass()) as $type => $records) {
                    $query->orWhere(function ($group) use ($type, $records) {
                        $group->where('documentable_type', $type)->whereIn('documentable_id', $records->map(fn ($record) => $record->getKey())->all());
                    });
                }
            })->get();
            foreach ($docs as $doc) {
                $owner = $owners->first(fn ($owner) => $owner->getMorphClass() === $doc->documentable_type && (int) $owner->getKey() === (int) $doc->documentable_id);
                $addDocument($doc->file_path, $doc->title, 'Document #'.$doc->id, $doc->created_at, [
                    'owner' => $doc, 'field' => 'file_path', 'can_delete' => $owner && $canManageDocuments($owner),
                ]);
            }
        }
        if (Schema::hasTable('project_documents')) {
            foreach (Models\ProjectDocument::whereIn('project_id', $projects->pluck('id'))->get() as $doc) {
                $project = $projects->firstWhere('id', $doc->project_id);
                $addDocument($doc->file_path, $doc->title, 'Project #'.$doc->project_id, $doc->created_at, [
                    'owner' => $doc, 'field' => 'file_path', 'can_delete' => $project && $canManageDocuments($project),
                ]);
            }
        }
        foreach ($workflow['Intimation'] as $record) {
            foreach (['self_certificate_file', 'gps_photo_file', 'dcr_certificate_file', 'model_agreement_file'] as $field) {
                $addDocument($record->$field, ucwords(str_replace('_', ' ', $field)), 'Intimation #'.$record->id, $record->updated_at, [
                    'owner' => $record, 'field' => $field, 'can_delete' => $canManageDocuments($record),
                ]);
            }
        }
        $stages = Models\Stage::where(function ($query) use ($customer) {
            $query->where('is_active', true)->orWhere('id', $customer->pipeline_stage_id);
        })->orderBy('sort_order')->orderBy('id')->get();
        $customFields = Models\CustomField::where('module', 'Customer')->where('is_active', true)->orderBy('sort_order')->get();
        $customer->load('customFieldValues');
        $financeMaster = $customer->finance_id ? Models\FinanceMaster::find($customer->finance_id) : null;
        $installationMaster = $customer->installation_master_id ? Models\InstallationMaster::find($customer->installation_master_id) : null;
        return compact('sections', 'documents', 'workflow', 'stages', 'customFields', 'financeMaster', 'installationMaster');
    }
}
