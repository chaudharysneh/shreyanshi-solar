<?php

namespace App\Http\Controllers;

use App\Models\{Invoice, Setting};
use App\Services\InvoiceFinanceService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class InvoiceReceiptController extends Controller
{
    public function index(Invoice $invoice, InvoiceFinanceService $finance)
    {
        $this->authorize('view', $invoice);
        $summary = $finance->summary($invoice);
        $payments = $invoice->payments()->orderByDesc('payment_date')->orderByDesc('id')->get();
        return view('crm.invoices.receipts', compact('invoice', 'summary', 'payments'));
    }

    public function pdf(Request $request, Invoice $invoice, InvoiceFinanceService $finance)
    {
        $this->authorize('view', $invoice);
        $data = $request->validate(['source' => 'required|in:customer,bank', 'payment' => 'nullable|integer|min:1']);
        $source = $data['source'];
        $payments = $invoice->payments()->where('source', $source)
            ->when(isset($data['payment']), fn ($q) => $q->whereKey($data['payment']))
            ->orderBy('payment_date')->orderBy('id')->get();
        abort_if($payments->isEmpty(), 404, 'No recorded payments found for this receipt.');
        $summary = $finance->summary($invoice);
        $settings = Setting::whereIn('key', ['company_name', 'company_address', 'company_tax_id', 'company_logo_path', 'phone', 'email'])->pluck('value', 'key');
        $logo = null;
        if (!empty($settings['company_logo_path']) && Storage::disk('public')->exists($settings['company_logo_path'])) {
            $path = Storage::disk('public')->path($settings['company_logo_path']);
            $logo = 'data:' . mime_content_type($path) . ';base64,' . base64_encode(file_get_contents($path));
        }
        $single = isset($data['payment']);
        $reference = $single ? 'RCT-' . str_pad($payments->first()->id, 6, '0', STR_PAD_LEFT) : 'STATEMENT-' . $invoice->id . '-' . strtoupper($source);
        return Pdf::loadView('crm.invoices.receipt-pdf', compact('invoice', 'summary', 'payments', 'source', 'settings', 'logo', 'single', 'reference'))
            ->setPaper('a4')->download($reference . '.pdf');
    }
}
