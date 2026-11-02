@php
    $tableDisplay = \App\Support\InvoiceTableDisplay::resolve(old('table_display', $invoice->table_display ?? null));
@endphp
<div class="invoice-display-options mt-3 mb-3">
    <button type="button" class="btn invoice-display-toggle" data-bs-toggle="collapse"
        data-bs-target="#invoiceTableDisplay" aria-expanded="false" aria-controls="invoiceTableDisplay">
        <i class="bi bi-layout-three-columns me-2" aria-hidden="true"></i> Table display options
        <i class="bi bi-chevron-down ms-2 invoice-display-chevron" aria-hidden="true"></i>
    </button>
    <div class="collapse mt-2" id="invoiceTableDisplay">
        <div class="invoice-display-panel">
            <div class="invoice-display-heading">
                <div class="invoice-display-icon"><i class="bi bi-sliders" aria-hidden="true"></i></div>
                <div>
                    <h3 class="invoice-display-title">Customize your product table</h3>
                    <p class="invoice-display-subtitle">Choose what appears on your invoice PDF.</p>
                </div>
                <span class="invoice-display-badge">PDF appearance</span>
            </div>
            <div class="invoice-display-grid">
                @foreach (\App\Support\InvoiceTableDisplay::OPTIONS as $key => $label)
                <div>
                    <input type="hidden" name="table_display[{{ $key }}]" value="0">
                    <label class="invoice-display-choice" for="table-display-{{ $key }}">
                        <input type="checkbox" id="table-display-{{ $key }}"
                            name="table_display[{{ $key }}]" value="1" @checked($tableDisplay[$key])>
                        <span>{{ $label }}</span>
                    </label>
                </div>
                @endforeach
            </div>
            <div class="invoice-display-hint"><i class="bi bi-info-circle" aria-hidden="true"></i><span>Product names stay visible. Columns resize automatically; calculations and totals stay the same.</span></div>
            @error('table_display')<div class="text-danger small mt-2">{{ $message }}</div>@enderror
        </div>
    </div>
</div>
