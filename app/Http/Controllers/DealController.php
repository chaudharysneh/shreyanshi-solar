<?php

namespace App\Http\Controllers;

use App\Models\Currency;
use App\Models\Customer;
use App\Models\Deal;
use App\Models\Estimate;
use App\Models\Lead;
use App\Models\Project;
use App\Models\Stage;
use App\Models\Status;
use App\Models\User;
use App\Models\BomProduct;
use App\Models\Category;
use App\Models\PdfBuilderForm;
use App\Models\Subsidy;
use App\Models\Tax;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class DealController extends Controller
{
    private const DEAL_STATUS_DEFAULTS = [
        'Pending' => '#64748B',
        'In-Process' => '#0EA5E9',
        'Paused' => '#F59E0B',
        'Lost' => '#EF4444',
        'Won/Confirm' => '#22C55E',
    ];

    public function pipeline()
    {
        $statuses = Status::dealChoices()->get();
        $deals = Deal::with(['customer', 'currency', 'status', 'assignedUser', 'stage'])
            ->latest()
            ->get();

        $dealsByStatus = $deals->groupBy('status_id');

        return view('crm.pipeline.index', compact('statuses', 'dealsByStatus'));
    }

    public function pipelineCreate()
    {
        $customers = Customer::visibleTo(auth()->user())->orderBy('name')->get();
        $stages = Stage::where('is_active', true)->orderBy('name')->get();
        $statuses = Status::dealChoices()->get();

        return view('crm.pipeline.create', compact('customers', 'stages', 'statuses'));
    }

    public function index()
    {
        $this->ensureDealStatuses();
        return view('crm.deals.index');
    }

    public function create()
    {
        $this->ensureDealStatuses();
        $invoices = \App\Models\Invoice::with('customer')
            ->whereNotNull('customer_id')
            ->when(!auth()->user()->isAdmin(), fn ($q) => $q->where(fn ($owned) => $owned->where('user_id', auth()->id())->orWhere('created_by', auth()->id())))
            ->orderByDesc('invoice_date')
            ->orderBy('invoice_name')
            ->get();
        $customers = Customer::visibleTo(auth()->user())
            ->orderBy('name')
            ->get();
        $statuses = Status::dealChoices()->get();
        $stages = Stage::where('is_active', true)->orderBy('name')->get();
        $users = auth()->user()->isAdmin()
            ? User::orderBy('name')->get()
            : User::where('id', auth()->id())->orderBy('name')->get();

        return view('crm.deals.create', array_merge(
            compact('customers', 'invoices', 'statuses', 'stages', 'users'),
            $this->quickEstimateFormData(),
            ['defaultSignatureUrl' => $this->defaultCompanySignatureUrl()]
        ));
    }

    public function edit(string $id)
    {
        $deal = Deal::with(['statusHistories.updater'])->findOrFail($id);
        $this->authorize('update', $deal);
        $this->ensureDealStatuses();
        $invoices = \App\Models\Invoice::with('customer')
            ->whereNotNull('customer_id')
            ->when(!auth()->user()->isAdmin(), fn ($q) => $q->where(fn ($owned) => $owned->where('user_id', auth()->id())->orWhere('created_by', auth()->id())))
            ->orderByDesc('invoice_date')
            ->orderBy('invoice_name')
            ->get();
        $customers = Customer::visibleTo(auth()->user())
            ->orderBy('name')
            ->get();
        $statuses = Status::dealChoices()->get();
        $stages = Stage::where('is_active', true)->orderBy('name')->get();
        $users = auth()->user()->isAdmin()
            ? User::orderBy('name')->get()
            : User::where('id', auth()->id())->orderBy('name')->get();
        return view('crm.deals.edit', array_merge(
            compact('deal', 'customers', 'invoices', 'statuses', 'stages', 'users'),
            $this->quickEstimateFormData(),
            ['defaultSignatureUrl' => $this->defaultCompanySignatureUrl()]
        ));
    }

    public function show(string $id)
    {
        $deal = Deal::with(['customer', 'currency', 'status', 'assignedUser', 'stage', 'creator', 'statusHistories.updater'])
            ->findOrFail($id);
        $this->authorize('view', $deal);

        return view('crm.deals.show', compact('deal'));
    }

    public function pdf(string $id)
    {
        $deal = Deal::with(['customer', 'estimate', 'invoice.currency', 'currency', 'status', 'stage', 'assignedUser', 'creator', 'owner'])->findOrFail($id);
        $this->authorize('view', $deal);
        $settings = \App\Models\Setting::pluck('value', 'key');
        $images = [];
        foreach (['company_logo_path', 'sidebar_icon_path', 'company_qr_code_path', 'company_signature_path'] as $key) {
            $path = $settings[$key] ?? null;
            if ($path && \Illuminate\Support\Facades\Storage::disk('public')->exists($path)) {
                $disk = \Illuminate\Support\Facades\Storage::disk('public');
                $images[$key] = 'data:' . $disk->mimeType($path) . ';base64,' . base64_encode($disk->get($path));
            }
        }
        if ($deal->signature_path && \Illuminate\Support\Facades\Storage::disk('public')->exists($deal->signature_path)) {
            $disk = \Illuminate\Support\Facades\Storage::disk('public');
            $images['deal_signature_path'] = 'data:' . $disk->mimeType($deal->signature_path) . ';base64,' . base64_encode($disk->get($deal->signature_path));
        }
        $images['company_logo_path'] = $images['company_logo_path'] ?? $images['sidebar_icon_path'] ?? null;

        return \Barryvdh\DomPDF\Facade\Pdf::loadView('crm.deals.pdf', compact('deal', 'settings', 'images'))
            ->setPaper('a4', 'portrait')->stream('deal-' . $deal->id . '.pdf');
    }

    public function signatureImage(string $id)
    {
        $deal = Deal::findOrFail($id);
        $this->authorize('view', $deal);

        $path = $deal->signature_path
            ?: \App\Models\Setting::query()->where('key', 'company_signature_path')->value('value');

        if (!$path || !Storage::disk('public')->exists($path)) {
            abort(404);
        }

        return response()->file(Storage::disk('public')->path($path));
    }

    public function export(Request $request)
    {
        $fileName = 'deals_' . date('Y-m-d_H-i-s') . '.csv';
        $query = $this->scopeOwnedRecords(
            Deal::with(['customer', 'currency', 'status', 'assignedUser', 'creator', 'stage'])
        )->latest()
            ->when($request->search, function ($q) use ($request) {
                $search = $request->search;
                $q->where(function ($sub) use ($search) {
                    $sub->where('title', 'like', "%{$search}%")
                        ->orWhereHas('customer', fn($customer) => $customer->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('status', fn($status) => $status->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('stage', fn($stage) => $stage->where('name', 'like', "%{$search}%"));
                });
            })
            ->when($request->status_id, fn($q) => $q->where('status_id', $request->status_id))
            ->when($request->stage_id, fn($q) => $q->where('stage_id', $request->stage_id))
            ->when($request->customer_id, fn($q) => $q->where('customer_id', $request->customer_id))
            ->when($request->from_date && $request->to_date, function ($q) use ($request) {
                $q->whereBetween('created_at', [
                    $request->from_date . ' 00:00:00',
                    $request->to_date . ' 23:59:59',
                ]);
            });

        $deals = $query->get();

        $headers = [
            "Content-type" => "text/csv",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma" => "no-cache",
            "Cache-Control" => "must-revalidate, post-check=0, pre-check=0",
            "Expires" => "0"
        ];

        $columns = ['No', 'Deal Name', 'Customer', 'Created By', 'Assigned To', 'Stage', 'Status', 'Deal Value', 'Expected Close Date', 'Created At'];

        $callback = function () use ($deals, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            $i = 1;
            foreach ($deals as $deal) {
                fputcsv($file, [
                    $i++,
                    $deal->title,
                    $deal->customer?->name ?? '--',
                    $deal->creator?->name ?? '--',
                    $deal->assignedUser?->name ?? '--',
                    $deal->stage?->name ?? '--',
                    $deal->status?->name ?? '--',
                    ($deal->currency?->symbol ?? $deal->currency?->code ?? '') . number_format((float) $deal->amount, 2, '.', ''),
                    $deal->expected_close_date?->format('Y-m-d') ?? '--',
                    $deal->created_at?->timezone('Asia/Kolkata')->format('d-m-Y h:i A') ?? '--',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    private function ensureDealStatuses(): void
    {
        $wonConfirmStatus = Status::where('type', 'deal')
            ->where('name', 'Won/Confirm')
            ->first();

        if (!$wonConfirmStatus) {
            Status::where('type', 'deal')
                ->where('name', 'Won')
                ->limit(1)
                ->update([
                    'name' => 'Won/Confirm',
                    'color' => self::DEAL_STATUS_DEFAULTS['Won/Confirm'],
                    'is_active' => true,
                ]);
        }

        foreach (self::DEAL_STATUS_DEFAULTS as $name => $color) {
            Status::updateOrCreate(
                ['type' => 'deal', 'name' => $name],
                ['color' => $color, 'is_active' => true]
            );
        }
        $canonicalWon = Status::where('type', 'deal')->where('name', 'Won/Confirm')->value('id');
        $legacyWon = Status::where('type', 'deal')->where('name', 'Won')->pluck('id');
        if ($legacyWon->isNotEmpty()) {
            Deal::whereIn('status_id', $legacyWon)->update(['status_id' => $canonicalWon]);
            Status::whereIn('id', $legacyWon)->update(['is_active' => false]);
        }

    }

    private function quickEstimateFormData(): array
    {
        return [
            'templates' => PdfBuilderForm::orderBy('template_name')->get(),
            'bomProducts' => BomProduct::with('categories')->orderBy('product_name')->get(),
            'categories' => Category::orderBy('name')->get(),
            'gstTaxes' => Tax::active()->orderBy('name')->orderBy('rate')->get(),
            'subsidies' => Subsidy::active()->get(),
        ];
    }

    private function defaultCompanySignatureUrl(): ?string
    {
        $path = \App\Models\Setting::query()
            ->where('key', 'company_signature_path')
            ->value('value');

        return $path && Storage::disk('public')->exists($path)
            ? route('profile.company_signature.image') . '?v=' . Storage::disk('public')->lastModified($path)
            : null;
    }
}
