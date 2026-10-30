@php
    $dashboardDocuments = [
        'solar' => [
            'title' => 'Solar Documents', 'icon' => 'bi-sun',
            'description' => 'Consumer, installation and equipment details',
            'fields' => [
                ['Consumer Name', 'text'], ['Consumer Number', 'text'], ['Address', 'textarea'],
                ['Billing Unit', 'text'], ['City', 'text'], ['Sub-Division', 'text'],
                ['Sanction No.', 'text'], ['Sanctioned Capacity', 'number'], ['Capacity (Solar)', 'number'],
                ['Make of Solar', 'text'], ['Wattage Per Module', 'number'], ['No. Of PV Module', 'integer'],
                ['Total Capacity', 'number'], ['Warranty', 'text'], ['Make of Inverter', 'text'],
                ['Capacity (Inverter)', 'number'], ['Year Of Manufacturing', 'year'], ['Aadhaar Number', 'aadhaar'],
                ['Mobile Number', 'tel'], ['Installation Date', 'date'], ['State', 'text'],
                ['Panel Serial Numbers', 'textarea'], ['Inverter Serial Number', 'textarea'], ['Date Day', 'day'],
                ['Date Month Short', 'month'], ['Year', 'year'], ['Sub-Division Address', 'textarea'],
                ['Consumer Aadhaar Image', 'file'],
            ],
        ],
        'subsidy' => [
            'title' => 'Subsidy Bond', 'icon' => 'bi-file-earmark-text',
            'description' => 'Agreement and consumer details',
            'fields' => [['Agreement Date', 'date'], ['Consumer Name', 'text'], ['Consumer Number', 'text'], ['Address', 'textarea']],
        ],
    ];
    $dashboardDocumentSections = [
        'solar' => [
            'Consumer Details' => [0, 1, 17, 18],
            'Address & Electricity Connection' => [4, 20, 3, 5, 6, 7, 2, 26],
            'Solar Panel Details' => [9, 8, 10, 11, 12, 13],
            'Inverter Details' => [14, 15, 16],
            'Equipment Serial Numbers' => [21, 22],
            'Installation & Document Date' => [19, 23, 24, 25],
            'Consumer Documents' => [27],
        ],
        'subsidy' => [
            'Agreement Details' => [0],
            'Consumer Details' => [1, 2, 3],
        ],
    ];
@endphp
<div class="dashboard-documents mb-4" data-document-base="{{ route('dashboard.documents.index') }}" data-customer-base="{{ route('dashboard.documents.customers') }}">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
        <div><h5 class="fw-bold mb-1">Document Forms</h5><p class="text-muted small mb-0">Prepare solar documents and subsidy bond details.</p></div>
        <div class="d-flex flex-wrap gap-2">
            <button type="button" class="btn btn-outline-dark-blue" data-bs-toggle="modal" data-bs-target="#dashboard-document-list"><i class="bi bi-folder2-open me-2"></i>Saved Documents</button>
            @foreach($dashboardDocuments as $key => $document)
                <button type="button" class="btn btn-dark-blue dashboard-document-trigger" data-bs-toggle="modal" data-bs-target="#dashboard-document-{{ $key }}"><i class="bi {{ $document['icon'] }} me-2" aria-hidden="true"></i>{{ $document['title'] }}</button>
            @endforeach
        </div>
    </div>
</div>
@foreach($dashboardDocuments as $key => $document)
<div class="modal fade dashboard-document-modal" id="dashboard-document-{{ $key }}" tabindex="-1" aria-labelledby="dashboard-document-title-{{ $key }}" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <form data-export-url="{{ $key === 'subsidy' ? route('dashboard.subsidy-bond.export') : route('dashboard.solar.export') }}" data-document-type="{{ $key }}" class="modal-content dashboard-document-form" enctype="multipart/form-data">
            <div class="modal-header dashboard-document-header">
                <div><h5 class="modal-title fw-bold" id="dashboard-document-title-{{ $key }}"><i class="bi {{ $document['icon'] }} me-2" aria-hidden="true"></i>{{ $document['title'] }}</h5><p class="small mb-0 mt-1">{{ $document['description'] }}</p></div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="document-stepbar" aria-label="Document progress"><span class="is-active" data-document-step="details"><b>1</b> Document details</span><i class="bi bi-arrow-right" aria-hidden="true"></i><span data-document-step="export"><b>2</b> Save &amp; export</span><small data-document-completion></small></div>
            <div class="modal-body p-4">
                <div class="alert alert-danger d-none" role="alert" data-document-error></div>
                <div class="dashboard-document-edit">
                @foreach($dashboardDocumentSections[$key] as $sectionTitle => $fieldIndexes)
                <fieldset class="dashboard-document-section {{ count($fieldIndexes) === 4 ? 'dashboard-document-section--pairs' : '' }}">
                    <legend><span class="document-section-number">{{ $loop->iteration }}</span>{{ $sectionTitle }}<i class="bi bi-check-circle-fill document-section-complete" aria-hidden="true"></i></legend>
                    <div class="dashboard-document-fields">
                    @foreach($fieldIndexes as $fieldIndex)
                        @php([$label, $type] = $document['fields'][$fieldIndex])
                        @php($fieldId = 'document-'.$key.'-'.\Illuminate\Support\Str::slug($label))
                        <div class="dashboard-document-field {{ $type === 'file' || ($type === 'textarea' && $label === 'Address' && $key === 'subsidy') ? 'dashboard-document-field--full' : '' }}">
                            <label class="form-label fw-semibold small" for="{{ $fieldId }}">{{ $label }}</label>
                            @if($label === 'Consumer Name')
                                <input type="hidden" name="consumer_name">
                                <div class="d-flex align-items-start gap-2 dashboard-document-customer-row">
                                    <select id="{{ $fieldId }}" name="customer_id" class="form-select dashboard-document-customer" required><option value="">Select customer</option></select>
                                    @if(auth()->user()?->hasMatrixPermission('create_customers'))<button type="button" class="btn btn-dark-blue" data-customer-add aria-label="Create new customer" title="Create new customer" aria-expanded="false"><i class="bi bi-plus-lg"></i></button>@endif
                                </div>
                                <div class="border rounded-3 p-3 mt-2 d-none" data-customer-create>
                                    <div class="dashboard-document-fields">
                                        <div class="dashboard-document-field"><label for="{{ $key }}-new-customer-name" class="form-label small">Customer Name</label><input id="{{ $key }}-new-customer-name" class="form-control" data-customer-name placeholder="Customer name" maxlength="200"></div>
                                        <div class="dashboard-document-field"><label for="{{ $key }}-new-customer-phone" class="form-label small">Phone Number</label><input id="{{ $key }}-new-customer-phone" class="form-control" data-customer-phone placeholder="10-digit phone number" inputmode="numeric" maxlength="10"></div>
                                    </div>
                                    <div class="d-flex gap-2 mt-2"><button type="button" class="btn btn-sm btn-dark-blue" data-customer-create-save>Create Customer</button><button type="button" class="btn btn-sm btn-outline-dark-blue" data-customer-create-cancel>Cancel</button></div>
                                    <div class="small text-danger mt-2" role="status" data-customer-error></div>
                                </div>
                            @elseif($type === 'textarea')
                                <textarea @if($key === 'subsidy') required @endif id="{{ $fieldId }}" name="{{ \Illuminate\Support\Str::snake(str_replace(['.', '(', ')'], '', $label)) }}" class="form-control" rows="2" placeholder="{{ str_contains($label, 'Serial') ? 'Enter one serial number per line' : 'Enter '.strtolower($label) }}"></textarea>
                            @elseif($type === 'month')
                                <select id="{{ $fieldId }}" name="date_month_short" class="form-select"><option value="">Select month</option>@foreach(['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'] as $month)<option value="{{ $month }}">{{ $month }}</option>@endforeach</select>
                            @else
                                <input @if($key === 'subsidy') required @endif id="{{ $fieldId }}" name="{{ \Illuminate\Support\Str::snake(str_replace(['.', '(', ')'], '', $label)) }}" class="form-control" type="{{ in_array($type, ['integer','year','day']) ? 'number' : ($type === 'aadhaar' ? 'text' : $type) }}"
                                    @if($type === 'file') accept="image/jpeg,image/png,image/webp" aria-describedby="{{ $fieldId }}-help"
                                    @elseif($type === 'aadhaar') inputmode="numeric" pattern="[0-9]{12}" maxlength="12" placeholder="12-digit Aadhaar number"
                                    @elseif($type === 'tel') inputmode="tel" maxlength="20" placeholder="Enter mobile number"
                                    @elseif(in_array($type, ['number','integer','year','day'])) min="{{ $type === 'day' ? 1 : ($type === 'year' ? 1900 : 0) }}" step="{{ $type === 'number' ? 'any' : '1' }}" @if($type === 'day') max="31" @elseif($type === 'year') max="9999" @endif placeholder="Enter {{ strtolower($label) }}"
                                    @elseif($type === 'text') placeholder="Enter {{ strtolower($label) }}"
                                    @endif>
                                @if($type === 'file')
                                <div class="document-image-preview d-none" data-aadhaar-preview>
                                    <div class="document-image-frame"><img data-aadhaar-preview-image alt="Consumer Aadhaar preview"></div>
                                    <div class="document-image-meta"><span class="document-image-icon"><i class="bi bi-image"></i></span><div><strong data-aadhaar-preview-name>Aadhaar image</strong><small data-aadhaar-preview-detail>Ready to include in your document</small></div><button type="button" class="btn btn-sm btn-outline-dark-blue d-none" data-aadhaar-preview-clear>Undo selection</button></div>
                                </div>
                                <div class="small text-danger mt-2" data-aadhaar-preview-error role="status"></div>
                                <div class="form-text" id="{{ $fieldId }}-help">Choose a JPG, PNG or WebP image (up to 5 MB). <a class="d-none" data-saved-aadhaar>Download saved Aadhaar image</a></div>@endif
                            @endif
                        </div>
                    @endforeach
                    </div>
                </fieldset>
                @endforeach
                </div>
                <div class="dashboard-document-export d-none">
                    <div class="document-export-heading"><span class="document-success-icon"><i class="bi bi-check2-circle"></i></span><div><span class="document-eyebrow">SAVED SUCCESSFULLY</span><h5 class="fw-bold mb-1" tabindex="-1">Your document is ready</h5><span class="small text-muted" data-document-summary></span></div></div>
                    <p class="text-muted small mb-3">Choose how you want to export your customized document.</p>
                    <label for="{{ $key }}-export-filename" class="form-label fw-semibold">File Name</label>
                    <div class="input-group mb-3"><input id="{{ $key }}-export-filename" data-document-filename type="text" class="form-control" maxlength="180" placeholder="Enter file name" autocomplete="off"><span class="input-group-text"><i class="bi bi-pencil" aria-hidden="true"></i></span></div>
                    <div class="dashboard-document-export-actions">
                        <button type="button" class="document-export-card document-export-card--word" data-document-export="word"><span class="document-export-badge">DOCX</span><span class="document-export-icon"><i class="bi bi-file-earmark-word" aria-hidden="true"></i></span><strong>Download Word</strong><small>Editable document &middot; .docx</small><span class="document-export-cta">Download file<i class="bi bi-download" aria-hidden="true"></i></span></button>
                        <button type="button" class="document-export-card document-export-card--pdf" data-document-export="pdf"><span class="document-export-badge">PDF</span><span class="document-export-icon"><i class="bi bi-file-earmark-pdf" aria-hidden="true"></i></span><strong>Download PDF</strong><small>Ready to print &middot; .pdf</small><span class="document-export-cta">Download file<i class="bi bi-download" aria-hidden="true"></i></span></button>
                        <button type="button" class="document-export-card document-export-card--share" data-document-export="share"><span class="document-export-badge">SHARE</span><span class="document-export-icon"><i class="bi bi-share" aria-hidden="true"></i></span><strong>Share PDF</strong><small>Send using your device</small><span class="document-export-cta">Share document<i class="bi bi-arrow-up-right" aria-hidden="true"></i></span></button>
                    </div>
                    <p class="small text-muted mt-3 mb-0" data-export-description>Download your document as Word or PDF, or share the PDF using your device.</p>
                    <div class="document-export-status" role="status" aria-live="polite" data-export-status></div>
                </div>
                <div class="alert alert-info mt-4 mb-0 d-none dashboard-document-notice" role="status">Form details are ready. Saving will be available in the next step. Nothing has been saved yet.</div>
            </div>
            <div class="modal-footer px-4 dashboard-document-edit-footer"><button type="button" class="btn btn-outline-dark-blue" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-dark-blue"><i class="bi bi-arrow-right me-2" aria-hidden="true"></i>Continue to Save</button></div>
            <div class="modal-footer px-4 dashboard-document-export-footer d-none">
                <button type="button" class="btn btn-outline-dark-blue" data-document-back><i class="bi bi-arrow-left me-2" aria-hidden="true"></i>Back to Edit</button>
                <button type="button" class="btn btn-dark-blue" data-document-restart><i class="bi bi-arrow-clockwise me-2" aria-hidden="true"></i>Start Over</button>
            </div>
        </form>
    </div>
</div>
@endforeach
@include('dashboard.partials.document-list')
@push('styles')
<link rel="stylesheet" href="{{ crm_asset('css/dashboard-documents.css') }}">
@endpush
@push('scripts')
<script src="{{ crm_asset('js/dashboard-documents.js') }}"></script>
@endpush
