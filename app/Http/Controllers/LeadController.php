<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Lead;
use App\Models\LeadSource;
use App\Models\Stage;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class LeadController extends Controller
{
    public function index()
    {
        $sources = LeadSource::orderBy('name')->get();
        $creators = User::whereIn('id', Lead::select('created_by'))->orderBy('name')->get(['id', 'name']);
        return view('crm.leads.index', compact('sources', 'creators'))->with('leadStatusOptions', \App\Models\LeadStatus::options());
    }

    // public function create()
    // {
    //     $sources = LeadSource::orderBy('name')->get();
    //     $stages = Stage::orderBy('name')->get();
    //     $users = User::orderBy('name')->get();

    //     return view('crm.leads.create', compact('sources', 'stages', 'users'));
    // }

    public function create()
    {
        $sources = LeadSource::orderBy('name')->get();
        $stages = Stage::orderBy('name')->get();

        if (auth()->user()->isAdmin()) {
            $users = User::orderBy('name')->get();
        } else {
            $users = User::where('id', auth()->id())->orderBy('name')->get();
        }

        return view('crm.leads.create', compact('sources', 'stages', 'users'))->with('leadStatuses', \App\Models\LeadStatus::orderBy('id')->get());
    }

    // public function edit(Lead $lead)
    // {
    //     $lead->load(['statusHistories.updater']);
    //     $sources = LeadSource::orderBy('name')->get();
    //     $stages = Stage::orderBy('name')->get();
    //     $users = User::orderBy('name')->get();

    //     return view('crm.leads.edit', compact('lead', 'sources', 'stages', 'users'));
    // }

    public function edit(Lead $lead)
    {
        $this->authorize('update', $lead);
        $lead->load(['statusHistories.updater']);
        $sources = LeadSource::orderBy('name')->get();
        $stages = Stage::orderBy('name')->get();

        if (auth()->user()->isAdmin()) {
            $users = User::orderBy('name')->get();
        } else {
            $users = User::where('id', auth()->id())->orderBy('name')->get();
        }

        return view('crm.leads.edit', compact('lead', 'sources', 'stages', 'users'))->with('leadStatuses', \App\Models\LeadStatus::orderBy('id')->get());
    }

    public function show(Lead $lead)
    {
        $this->authorize('view', $lead);
        $lead->load(['leadSource', 'stage', 'assignedUser', 'followUps.assignedUser', 'tasks.assignedUser', 'statusHistories.updater']);

        return view('crm.leads.show', compact('lead'));
    }

    public function image(Lead $lead)
    {
        if (!$lead->image || !Storage::disk('public')->exists($lead->image)) {
            abort(404);
        }

        return response()->file(Storage::disk('public')->path($lead->image));
    }

    public function convertToCustomer(Request $request, Lead $lead, \App\Services\LeadConversionService $converter)
    {
        $this->authorize('update', $lead);
        $this->authorize('create', Customer::class);
        $result = $converter->convert($lead);
        $customer = $result['customer'];

        if ($result['created']) {
            try {
                send_customer_welcome_notification($customer);
                send_admin_notification('Customer', 'Created (Lead Converted)', $customer->name, [
                    'Customer Name' => $customer->name,
                    'Phone' => $customer->phone ?? '-',
                    'Email' => $customer->email ?? '-',
                    'Converted From' => 'Lead #'.$lead->id,
                ], route('masters.customers.edit', $customer));
            } catch (\Throwable $exception) {
                // The conversion has committed; notification failures must not report it as failed.
                \Illuminate\Support\Facades\Log::warning('Lead conversion notification failed.', [
                    'lead_id' => $lead->id, 'customer_id' => $customer->id, 'exception' => get_class($exception),
                ]);
            }
        }

        $message = $result['already_converted'] ? 'Lead already converted.' : 'Lead converted successfully.';
        $redirect = route('masters.customers.edit', $customer);
        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => $message, 'customer_id' => $customer->id,
                'pipeline_stage_id' => $customer->pipeline_stage_id, 'redirect' => $redirect]);
        }
        return redirect($redirect)->with('success', $message);
    }

    public function export(Request $request)
    {
        $fileName = 'leads_' . date('Y-m-d_H-i-s') . '.csv';

        $query = $this->scopeOwnedRecords(
            Lead::with(['leadSource', 'stage', 'assignedUser'])
        )->latest()
            ->when($request->search, function ($q) use ($request) {
                $search = $request->search;
                $q->where(function ($sub) use ($search) {
                    $sub->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('whatsapp', 'like', "%{$search}%")
                        ->orWhere('status', 'like', "%{$search}%");
                });
            })
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->source_id, fn($q) => $q->where('lead_source_id', $request->source_id))
            ->when($request->stage_id, fn($q) => $q->where('stage_id', $request->stage_id))
            ->when($request->from_date && $request->to_date, function ($q) use ($request) {
                $q->whereBetween('created_at', [
                    $request->from_date . ' 00:00:00',
                    $request->to_date . ' 23:59:59',
                ]);
            });

        $leads = $query->get();

        $headers = [
            'Content-type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=$fileName",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $columns = ['No', 'Name', 'Email', 'Phone', 'WhatsApp', 'Status', 'Source', 'Stage', 'Notes', 'Created At'];

        $callback = function () use ($leads, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            $i = 1;
            foreach ($leads as $lead) {
                fputcsv($file, [
                    $i++,
                    $lead->name,
                    $lead->email,
                    $lead->phone,
                    $lead->whatsapp,
                    ucfirst(str_replace('_', ' ', $lead->status)),
                    $lead->leadSource?->name ?? $lead->source,
                    $lead->stage?->name,
                    $lead->notes,
                    $lead->created_at?->timezone('Asia/Kolkata')->format('d-m-Y h:i A') ?? '--',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
