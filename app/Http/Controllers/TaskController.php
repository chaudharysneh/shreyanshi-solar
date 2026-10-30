<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\User;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\BomProduct;
use App\Models\Category;
use App\Models\PdfBuilderForm;
use App\Models\Subsidy;
use App\Models\Tax;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    public function index()
    {
        return view('crm.tasks.index');
    }

    public function create()
    {
        $users = auth()->user()->isAdmin()
            ? User::orderBy('name')->get()
            : User::where('id', auth()->id())->orderBy('name')->get();
        $invoices = $this->visibleInvoicesQuery()->get();

        return view('crm.tasks.create', compact('users', 'invoices'));
    }

    public function edit(string $id)
    {
        $task = Task::with(['statusHistories.updater'])->findOrFail($id);
        $this->authorize('update', $task);
        $users = auth()->user()->isAdmin()
            ? User::orderBy('name')->get()
            : User::where('id', auth()->id())->orderBy('name')->get();
        $invoices = $this->visibleInvoicesQuery()->get();
        $selectedInvoiceId = $task->invoice_id;

        $workflowSteps=app(\App\Services\TaskWorkflowService::class)->steps($task);
        return view('crm.tasks.edit', compact('task', 'users', 'invoices', 'selectedInvoiceId','workflowSteps'));
    }

    public function show(string $id)
    {
        $task = Task::with([
            'assignedUser',
            'owner',
            'customer',
            'invoice.customer',
            'project.customer',
            'project.creator',
            'documents',
            'statusHistories.updater',
            'workflowEvents.updater',
        ])->findOrFail($id);
        $this->authorize('view', $task);

        $customer = $task->customer
            ?: $task->invoice?->customer
            ?: $task->project?->customer;

        $invoice = $task->invoice;
        if ($invoice && (!auth()->user()->hasMatrixPermission('view_invoices') || auth()->user()->cannot('view', $invoice))) $invoice = null;
        $workflowSteps = app(\App\Services\TaskWorkflowService::class)->steps($task);
        return view('crm.tasks.show', compact('task', 'customer', 'invoice', 'workflowSteps'));
    }

    public function workflowAttachment(Task $task, int $event, int $attachment)
    {
        $this->authorize('view', $task);
        $entry = $task->workflowEvents()->findOrFail($event);
        $file = $entry->attachments[$attachment] ?? null;
        abort_unless($file && \Illuminate\Support\Facades\Storage::disk('local')->exists($file['path']), 404);
        return \Illuminate\Support\Facades\Storage::disk('local')->download($file['path'], $file['name']);
    }

    public function export(Request $request)
    {
        $fileName = 'tasks_' . date('Y-m-d_H-i-s') . '.csv';
        $query = $this->scopeOwnedRecords(
            Task::with(['assignedUser', 'invoice.customer'])
        )->latest()
            ->when($request->search, function ($q) use ($request) {
                $search = $request->search;
                $q->where(function ($sub) use ($search) {
                    $sub->where('title', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhere('status', 'like', "%{$search}%")
                        ->orWhere('priority', 'like', "%{$search}%")
                        ->orWhereHas('project', fn($project) => $project->where('name', 'like', "%{$search}%"));
                });
            })
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->priority, fn($q) => $q->where('priority', $request->priority))
            ->when($request->project_id, fn($q) => $q->where('project_id', $request->project_id))
            ->when($request->assigned_user_id, fn($q) => $q->where('assigned_user_id', $request->assigned_user_id))
            ->when($request->from_date && $request->to_date, function ($q) use ($request) {
                $q->whereBetween('created_at', [
                    $request->from_date . ' 00:00:00',
                    $request->to_date . ' 23:59:59',
                ]);
            });

        $tasks = $query->get();

        $headers = [
            "Content-type" => "text/csv",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma" => "no-cache",
            "Cache-Control" => "must-revalidate, post-check=0, pre-check=0",
            "Expires" => "0"
        ];

        $columns = ['No', 'Title', 'Status', 'Task Type', 'Due Date', 'Assigned To', 'Customer', 'Invoice', 'Created At'];

        $callback = function () use ($tasks, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            $i = 1;
            foreach ($tasks as $task) {
                fputcsv($file, [
                    $i++,
                    $task->title,
                    ucfirst(str_replace('_', ' ', $task->status)),
                    $task->task_type,
                    $task->due_date ? $task->due_date->format('Y-m-d') : '',
                    $task->assignedUser ? $task->assignedUser->name : '',
                    $task->invoice?->customer?->name ?? $task->customer?->name ?? '',
                    $task->invoice?->invoice_name ?? '',
                    $task->created_at?->timezone('Asia/Kolkata')->format('d-m-Y h:i A') ?? '--',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    private function visibleInvoicesQuery()
    {
        return Invoice::with('customer')
            ->when(!auth()->user()->isAdmin(), fn ($q) => $q->where(fn ($owned) => $owned->where('user_id', auth()->id())->orWhere('created_by', auth()->id())))
            ->whereNotNull('customer_id')
            ->whereHas('customer', function ($query) {
                $query->visibleTo(auth()->user());
            })
            ->orderByDesc('invoice_date')
            ->orderByDesc('id');
    }

}
