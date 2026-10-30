<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Country;
use App\Models\City;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Response;

class CustomerController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', Customer::class);
        return view('masters.customers.index');
    }

    public function create()
    {
        $this->authorize('create', Customer::class);
        $countries = Country::orderBy('name')->get();
        return view('masters.customers.create', compact('countries'));
    }


    public function edit(Customer $customer)
    {
        $this->authorize('update', $customer);
        $countries = Country::orderBy('name')->get();
        $cities = City::where('country_id', $customer->country_id)->orderBy('name')->get();
        return view('masters.customers.edit', compact('customer', 'countries', 'cities'));
    }

    public function show(Customer $customer)
    {
        $this->authorize('view', $customer);

        $customer->load([
            'country',
            'city',
            'creator',
            'updater',
            'estimates',
            'meetings.assignedUser',
            'deals.stage',
            'deals.status',
            'deals.currency',
            'deals.creator',
            'deals.assignedUser',
        ]);

        $overview = app(\App\Services\CustomerOverviewService::class)->build($customer);

        $formDocuments=\App\Models\DashboardDocument::where('customer_id',$customer->id)->withCount('versions')->latest('updated_at')->get();
        return view('masters.customers.show', compact('customer', 'overview', 'formDocuments'));
    }

    public function downloadRelatedDocument(Customer $customer, string $documentKey)
    {
        $this->authorize('view', $customer);
        $customer->load(['meetings.assignedUser', 'deals.stage', 'deals.status', 'deals.currency', 'deals.creator', 'deals.assignedUser']);
        $overview = app(\App\Services\CustomerOverviewService::class)->build($customer);
        $document = $overview['documents']->get($documentKey);
        abort_unless($document && $document['available'], 404);

        return Storage::disk('public')->download($document['path'], $document['name']);
    }

    public function customerDocuments(Customer $customer)
    {
        $this->authorize('view', $customer);
        $overview = app(\App\Services\CustomerOverviewService::class)->build($customer);
        return response()->json(['success' => true, 'data' => [
            'docs' => $overview['documents']->map(function ($doc) use ($customer) {
                return [
                    'original_name' => $doc['name'], 'category' => $doc['category'], 'label' => $doc['label'],
                    'download_url' => $doc['available'] ? route('masters.customers.related-documents.download', [$customer, $doc['key']]) : null,
                    'delete_url' => $doc['can_delete'] ? route('masters.customers.related-documents.destroy', [$customer, $doc['key']]) : null,
                ];
            })->values(),
        ]]);
    }

    public function uploadCustomerDocuments(Request $request, Customer $customer)
    {
        $this->authorize('update', $customer);
        abort_unless(auth()->user()->hasMatrixPermission('edit_customers'), 403);
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), \App\Support\CustomerDocumentFields::rules());
        $groups = $validator->validate()['documents'];
        $documents = (array) $customer->customer_docs;
        foreach ($groups as $group) {
            foreach ($group['files'] as $file) {
                $documents[] = [
                    'path' => $file->store('customers/' . $customer->id . '/documents', 'public'),
                    'original_name' => $file->getClientOriginalName(),
                    'category' => $group['category'],
                    'label' => \App\Support\CustomerDocumentFields::REQUIRED[$group['category']] ?? trim($group['label']),
                    'uploaded_at' => now()->toDateTimeString(),
                ];
            }
        }
        $customer->customer_docs = $documents;
        $customer->save();
        return $this->customerDocuments($customer->fresh());
    }

    public function deleteRelatedDocument(Customer $customer, string $documentKey)
    {
        $this->authorize('view', $customer);
        $overview = app(\App\Services\CustomerOverviewService::class)->build($customer);
        $document = $overview['documents']->get($documentKey);
        abort_unless($document, 404);
        abort_unless($document['can_delete'] && $document['owner'] && $document['field'], 403);
        $owner = $document['owner'];
        $field = $document['field'];
        if ($field === 'customer_docs') {
            $owner->$field = array_values(array_filter((array) $owner->$field, function ($doc) use ($document) {
                return (is_array($doc) ? ($doc['path'] ?? null) : $doc) !== $document['path'];
            }));
            $owner->save();
        } elseif ($field === 'file_path') {
            $owner->delete();
        } else {
            $owner->$field = null;
            $owner->save();
        }
        Storage::disk('public')->delete($document['path']);
        return response()->json(['success' => true, 'message' => 'Document deleted successfully.']);
    }

    public function image(Customer $customer)
    {
        $this->authorize('view', $customer);

        if (!$customer->image || !Storage::disk('public')->exists($customer->image)) {
            abort(404);
        }

        $fullPath = Storage::disk('public')->path($customer->image);

        if (!file_exists($fullPath)) {
            abort(404);
        }

        return response()->file($fullPath, [
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0'
        ]);
    }


    public function export()
    {
        $this->authorize('viewAny', Customer::class);
        $fileName = 'customers_' . date('Y-m-d_H-i-s') . '.csv';
        $query = $this->scopeOwnedRecords(
            Customer::with(['country', 'city'])
        )->latest()
            ->when(request('search'), function ($q) {
                $search = request('search');
                $q->where(function ($sub) use ($search) {
                    $sub->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('whatsapp', 'like', "%{$search}%");
                });
            })
            ->when(request('type'), fn($q) => $q->where('type', request('type')))
            ->when(request()->filled('is_active'), fn($q) => $q->where('is_active', request('is_active')))
            ->when(request('country_id'), fn($q) => $q->where('country_id', request('country_id')))
            ->when(request('city_id'), fn($q) => $q->where('city_id', request('city_id')))
            ->when(request('from_date') && request('to_date'), function ($q) {
                $q->whereBetween('created_at', [
                    request('from_date') . ' 00:00:00',
                    request('to_date') . ' 23:59:59',
                ]);
            });

        $customers = $query->get();

        $headers = [
            "Content-type" => "text/csv",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma" => "no-cache",
            "Cache-Control" => "must-revalidate, post-check=0, pre-check=0",
            "Expires" => "0"
        ];

        $columns = ['No', 'Name', 'Email', 'Phone', 'WhatsApp', 'Type', 'Country', 'City', 'Status', 'Created At'];

        $callback = function () use ($customers, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            $i = 1;
            foreach ($customers as $customer) {
                fputcsv($file, [
                    $i++,
                    $customer->name,
                    $customer->email,
                    $customer->phone,
                    $customer->whatsapp,
                    $customer->type,
                    $customer->country?->name ?? '--',
                    $customer->city?->name ?? '--',
                    $customer->is_active ? 'Active' : 'Inactive',
                    $customer->created_at?->timezone('Asia/Kolkata')->format('d-m-Y h:i A') ?? '--',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function apiSearch(Request $request)
    {
        $this->authorize('viewAny', Customer::class);
        $search = $request->get('q');

        // For duplicate checking, we need to search ALL customers regardless of visibility
        // This ensures staff users can detect duplicates created by other staff members
        $customers = Customer::query()
            ->whereNull('deleted_at')
            ->when($search, function ($query, $search) {
                return $query->where(function ($searchQuery) use ($search) {
                    $searchQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('exclude_id'), function ($query) use ($request) {
                return $query->where('id', '<>', (int) $request->get('exclude_id'));
            })
            ->orderBy('name')
            ->limit(20)
            ->get(['id', 'name', 'email', 'phone']);

        return response()->json($customers);
    }

    public function apiEstimateCustomerSearch(Request $request)
    {
        $this->authorize('viewAny', Customer::class);
        $search = $request->get('q');

        $customers = Customer::query()
            ->whereHas('estimates')
            ->when($search, function ($query, $search) {
                return $query->where(function ($searchQuery) use ($search) {
                    $searchQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('exclude_id'), function ($query) use ($request) {
                return $query->where('id', '<>', (int) $request->get('exclude_id'));
            })
            ->orderBy('name')
            ->limit(20)
            ->get(['id', 'name', 'email', 'phone']);

        return response()->json($customers);
    }

    public function updatePipelineStage(Request $request, Customer $customer)
    {
        $this->authorize('update', $customer);

        $request->validate([
            'stage_id' => 'required|exists:stages,id,is_active,1',
        ]);

        $customer->update([
            'pipeline_stage_id' => $request->stage_id,
        ]);

        app(\App\Services\WorkflowService::class)->syncWorkflowToPipelineStage($customer->id, $request->stage_id);

        return response()->json([
            'success' => true,
            'message' => 'Pipeline stage updated successfully.',
            'stage_id' => $customer->pipeline_stage_id,
        ]);
    }
}
