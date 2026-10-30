<?php

namespace App\Http\Controllers;

use App\Models\Purchase;
use App\Models\Vendor;
use App\Models\Product;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PurchaseController extends Controller
{
    public function index(Request $request)
    {
        return view('crm.purchases.index');
    }

    public function create()
    {
        $vendors = Vendor::orderBy('name')->get();
        $products = Product::orderBy('name')->get();
        // Only get categories that are NOT soft-deleted
        $categories = \App\Models\Categories::where('deleted_at', null)->orderBy('name')->get();

        return view('crm.purchases.create', compact('vendors', 'products', 'categories'));
    }

    public function show(Purchase $purchase)
    {
        $purchase->load(['customer', 'product', 'creator']);

        return view('crm.purchases.view', compact('purchase'));
    }

    public function edit(Purchase $purchase)
    {
        $vendors = Vendor::orderBy('name')->get();
        $products = Product::orderBy('name')->get();
        // Only get categories that are NOT soft-deleted
        $categories = \App\Models\Categories::where('deleted_at', null)->orderBy('name')->get();
        $purchase->load(['customer', 'product']);

        return view('crm.purchases.edit', compact('purchase', 'vendors', 'products', 'categories'));
    }

    public function downloadPdf(Purchase $purchase)
    {
        $purchase->load(['customer', 'product']);

        // Generate PDF using DomPDF
        $pdf = \PDF::loadView('crm.purchases.pdf', compact('purchase'));
        return $pdf->download('Material-IN-' . $purchase->invoice_no . '.pdf');
    }

    public function export(Request $request): StreamedResponse
    {
        $fileName = 'purchases_' . date('Y-m-d_H-i-s') . '.csv';
        $search = trim((string) $request->get('search', ''));

        $purchases = $this->scopeOwnedRecords(
            Purchase::with(['vendor', 'product', 'creator'])
        )
            ->when($search !== '', fn ($query) => $query->where(function ($subQuery) use ($search) {
                $subQuery->where('invoice_no', 'like', "%{$search}%")
                    ->orWhere('invoice_name', 'like', "%{$search}%")
                    ->orWhereHas('vendor', fn ($vendorQuery) => $vendorQuery->where('name', 'like', "%{$search}%"));
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

        $columns = ['No', 'Vendor Name', 'IN No', 'IN Date', 'Product', 'Quantity', 'Price', 'Total', 'Created By', 'Created At'];

        $callback = function () use ($purchases, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            foreach ($purchases as $index => $purchase) {
                fputcsv($file, [
                    $index + 1,
                    optional($purchase->vendor)->name ?? '-',
                    $purchase->invoice_no ?? '-',
                    optional($purchase->invoice_date)?->format('d-m-Y') ?? '-',
                    optional($purchase->product)->name ?? ($purchase->product_name ?? '-'),
                    $purchase->quantity ?? 0,
                    $purchase->price ?? 0,
                    $purchase->total ?? 0,
                    optional($purchase->creator)->name ?? '-',
                    optional($purchase->created_at)?->timezone('Asia/Kolkata')->format('d-m-Y h:i A') ?? '--',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
