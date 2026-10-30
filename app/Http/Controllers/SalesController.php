<?php

namespace App\Http\Controllers;

use App\Models\Categories;
use App\Models\Sales;
use App\Models\Customer;
use App\Models\Product;
use App\Models\HandoverPerson;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SalesController extends Controller
{
    public function index(Request $request)
    {
        return view('crm.sales.index');
    }

    public function create()
    {
        $customers = Customer::orderBy('name')->get();
        $products = Product::with('inventories')->orderBy('name')->get();
        $categories = Categories::whereNull('deleted_at')->orderBy('name')->get(['id', 'name']);
        $handoverPersons = HandoverPerson::orderBy('name')->get();

        return view('crm.sales.create', compact('customers', 'products', 'categories', 'handoverPersons'));
    }

    public function show(Sales $sale)
    {
        $sale->load(['customer', 'product', 'handoverPerson', 'creator']);

        return view('crm.sales.view', compact('sale'));
    }

    public function edit(Sales $sale)
    {
        $customers = Customer::orderBy('name')->get();
        $products = Product::with('inventories')->orderBy('name')->get();
        $categories = Categories::whereNull('deleted_at')->orderBy('name')->get(['id', 'name']);
        $handoverPersons = HandoverPerson::orderBy('name')->get();
        $sale->load(['customer', 'product', 'handoverPerson']);

        return view('crm.sales.edit', compact('sale', 'customers', 'products', 'categories', 'handoverPersons'));
    }

    public function downloadPdf(Sales $sale)
    {
        $sale->load(['customer', 'product', 'handoverPerson']);

        // Generate PDF using DomPDF
        $pdf = \PDF::loadView('crm.sales.pdf', compact('sale'));
        return $pdf->download('Material-OUT-' . $sale->invoice_no . '.pdf');
    }

    public function export(Request $request): StreamedResponse
    {
        $fileName = 'sales_' . date('Y-m-d_H-i-s') . '.csv';
        $search = trim((string) $request->get('search', ''));

        $sales = $this->scopeOwnedRecords(
            Sales::with(['customer', 'product', 'handoverPerson', 'creator'])
        )
            ->when($search !== '', fn ($query) => $query->where(function ($subQuery) use ($search) {
                $subQuery->where('invoice_no', 'like', "%{$search}%")
                    ->orWhere('invoice_name', 'like', "%{$search}%")
                    ->orWhereHas('customer', fn ($customerQuery) => $customerQuery->where('name', 'like', "%{$search}%"));
            }))
            ->latest()
            ->get();

        $headers = [
            'Content-type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=$fileName",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $columns = ['No', 'Site Name', 'OUT No', 'OUT Date', 'Product', 'Quantity', 'Price', 'Total', 'Handover Person', 'Created By', 'Created At'];

        $callback = function () use ($sales, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            foreach ($sales as $index => $sale) {
                fputcsv($file, [
                    $index + 1,
                    optional($sale->customer)->name ?? '-',
                    $sale->invoice_no ?? '-',
                    optional($sale->invoice_date)?->format('d-m-Y') ?? '-',
                    optional($sale->product)->name ?? ($sale->product_name ?? '-'),
                    $sale->quantity ?? 0,
                    $sale->price ?? 0,
                    $sale->total ?? 0,
                    optional($sale->handoverPerson)->name ?? '-',
                    optional($sale->creator)->name ?? '-',
                    optional($sale->created_at)?->timezone('Asia/Kolkata')->format('d-m-Y h:i A') ?? '--',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
