<!doctype html><html><head><meta charset="utf-8"><style>
@page { size: A4; margin: 44pt 48pt; }
body { font-family: "Times-Roman", serif; font-size: 10pt; line-height: 1.35; color: #000; }
.document-section { page-break-before: always; } .document-section:first-child { page-break-before: auto; }
h2 { text-align: center; font-size: 11pt; margin: 0 0 18pt; } h3 { font-size: 10pt; margin: 16pt 0 8pt; page-break-after: avoid; }
p { text-align: justify; margin: 0 0 12pt; } .center { text-align: center; }
table { width:100%; border-collapse:collapse; font-size: 9pt; line-height: 1.3; margin-bottom:12pt; table-layout:fixed; }
td,th { border: .5pt solid #777; padding: 3pt 4pt; vertical-align: top; overflow-wrap:break-word; } th { font-weight:bold; }
tr { page-break-inside:avoid; } thead { display:table-header-group; }
.signatures { margin-top: 34pt; } .signatures td { border:0; width:50%; } .signatures td:last-child { text-align:right; }
.aadhaar { text-align:center; margin-top:28pt; } .aadhaar img { max-width:100%; max-height:235pt; }
.spaced { margin-top:30pt; } .serials { white-space:pre-wrap; overflow-wrap:break-word; }
.completion-report { font-size: 9pt; line-height: 1.2; }
.completion-report h2 { margin-bottom: 10pt; }
.completion-report table { font-size: 8.5pt; line-height: 1.12; margin-bottom: 8pt; }
.completion-report td, .completion-report th { padding: 1.8pt 4pt; }
.completion-report p { margin-bottom: 8pt; }
.completion-report .signatures { margin-top: 16pt; }
.agreement-ending .signatures { margin-top: 12pt; }
.agreement-ending .signatures td { text-align: left; padding: 8pt 5pt; }
.clause { margin-bottom:10pt; } .clause-number { font-weight:bold; }
</style></head><body>@foreach($sections as $section)<div class="document-section {{ $loop->first ? 'completion-report' : '' }} {{ $loop->last ? 'agreement-ending' : '' }}">{!! $section !!}</div>@endforeach</body></html>