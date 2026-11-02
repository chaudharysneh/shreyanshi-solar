@extends('layouts.app')
@section('page_title', 'Invoice Receipts')
@section('content')
<div class="card"><div class="card-body">
    <div class="d-flex justify-content-between align-items-center mb-4"><div><h4>Payment receipts</h4><p class="text-muted mb-0">{{ $invoice->invoice_no }} · {{ $summary['customer'] }} · {{ $summary['currency'] }}</p></div><a href="{{ route('invoices.index') }}" class="btn btn-outline-secondary">Back</a></div>
    <div class="d-flex flex-wrap gap-2 mb-3">
        @foreach (['customer' => 'Customer', 'bank' => 'Bank'] as $source => $label)
            @if ($payments->where('source', $source)->isNotEmpty())
            <a class="btn btn-outline-success" href="{{ route('invoices.receipts.pdf', ['invoice' => $invoice->id, 'source' => $source]) }}">Download {{ $label }} Payment Statement</a>
            @endif
        @endforeach
    </div>
    <p class="text-muted small">Each actual payment, including a partial payment, has its own receipt. Funding plans do not create receipts. Voided payments are excluded.</p>
    <div class="table-responsive"><table class="table align-middle"><thead><tr><th>Receipt</th><th>Date</th><th>Paid by</th><th>Amount</th><th>Method / Reference</th><th>Download</th></tr></thead><tbody>
        @forelse ($payments as $payment)
        <tr><td>RCT-{{ str_pad($payment->id, 6, '0', STR_PAD_LEFT) }}</td><td>{{ $payment->payment_date?->format('d M Y') }}</td><td>{{ ucfirst($payment->source) }}</td><td>{{ number_format($payment->amount, 2) }}</td><td>{{ ucwords(str_replace('_', ' ', $payment->payment_method)) }}<div class="small text-muted">{{ $payment->transaction_id }}</div></td><td><a class="btn btn-sm btn-outline-success" aria-label="Download receipt" href="{{ route('invoices.receipts.pdf', ['invoice' => $invoice->id, 'source' => $payment->source, 'payment' => $payment->id]) }}"><i class="bi bi-download"></i> Receipt</a></td></tr>
        @empty <tr><td colspan="6" class="text-center py-4">No payments recorded yet. Save an actual payment to generate a receipt.</td></tr>
        @endforelse
    </tbody></table></div>
</div></div>
@endsection
