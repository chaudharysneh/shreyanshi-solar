<div class="quotation">
    <table class="plain header"><tr><td style="width:35%">@if($logo)<img src="{{ $logo }}" style="width:85pt;height:45pt" alt="Company logo">@endif</td><td style="width:65%"><strong>{{ $company['company_name'] ?? 'SHREYANSHI ENTERPRISES' }}</strong><br>{{ $company['company_address'] ?? '' }}<br>{{ $company['phone'] ?? '' }} @if(!empty($company['email'])) | {{ $company['email'] }} @endif<br>GSTIN/UIN: {{ $company['company_tax_id'] ?? '--' }}</td></tr></table>
    <h2>BANK QUOTATION</h2>
    <table class="plain metadata"><tr><td>GSTIN: {{ $company['company_tax_id'] ?? '--' }}</td><td>Date: {{ \Carbon\Carbon::parse($quotation_date)->format('d-M-Y') }}</td></tr></table>
    <table class="grid customer"><tr><th>Billing Address</th><th>Shipping Address</th></tr><tr>
        <td><strong>{{ $consumer_name }}</strong><br>Phone: {{ $mobile_number }}@if(!empty($address))<br>{{ $address }}@endif</td>
        <td><strong>{{ $consumer_name }}</strong><br>Phone: {{ $mobile_number }}@if(!empty($address))<br>{{ $address }}@endif</td>
    </tr></table>
    <table class="grid items"><tr><th style="width:70%">Description</th><th style="width:30%">Amount (Rs.)</th></tr>
        <tr><td>Total Amount</td><td>{{ number_format((float) $total_amount, 2) }}</td></tr>
        <tr><td>Amount Received</td><td>{{ number_format((float) $amount_received, 2) }}</td></tr>
        <tr><th>Amount Pending</th><th>{{ number_format((float) $amount_pending, 2) }}</th></tr>
    </table>
    <table class="grid closing"><tr><td style="width:60%"><strong>Bank Details:</strong>
        @if(!empty($company['bank_name']))<br>Bank Name: {{ $company['bank_name'] }}@endif
        @if(!empty($company['account_name']))<br>A/C Name: {{ $company['account_name'] }}@endif
        @if(!empty($company['account_number']))<br>A/C No.: {{ $company['account_number'] }}@endif
        @if(!empty($company['ifsc_code']))<br>IFSC Code: {{ $company['ifsc_code'] }}@endif
        @if(!empty($company['branch_name']))<br>Branch: {{ $company['branch_name'] }}@endif
        </td><td style="width:40%"><strong>Total Amount (Rs.): {{ number_format((float) $total_amount, 2) }}</strong><br>Amount Received (Rs.): {{ number_format((float) $amount_received, 2) }}<br><strong>Amount Pending (Rs.): {{ number_format((float) $amount_pending, 2) }}</strong></td></tr>
        <tr><td><strong>Total Payable (Rs.): {{ number_format((float) $amount_pending, 2) }}</strong><br>This is a computer-generated quotation. E. &amp; O. E.</td><td>For, {{ $company['company_name'] ?? 'SHREYANSHI ENTERPRISES' }}<br><br><br><strong>Authorised Signatory</strong></td></tr>
    </table>
</div>
