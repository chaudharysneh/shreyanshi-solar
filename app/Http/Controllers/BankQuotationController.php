<?php
namespace App\Http\Controllers;

use App\Services\HtmlWordDocument;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\HeaderUtils;

class BankQuotationController extends Controller
{
    public function export(Request $request)
    {
        $data = $request->validate([
            'consumer_name' => ['required', 'string', 'max:200'],
            'mobile_number' => ['required', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:2000'],
            'quotation_date' => ['required', 'date_format:Y-m-d'],
            'total_amount' => ['required', 'numeric', 'min:0'],
            'amount_received' => ['required', 'numeric', 'min:0', 'lte:total_amount'],
            'filename' => ['required', 'string', 'max:180'],
            'format' => ['required', 'in:word,pdf'],
        ]);
        $data['amount_pending'] = (float) $data['total_amount'] - (float) $data['amount_received'];
        $data['company'] = \App\Models\Setting::query()->whereIn('key', ['company_name', 'company_address', 'company_tax_id', 'company_logo_path', 'phone', 'email', 'bank_name', 'account_name', 'account_number', 'ifsc_code', 'branch_name', 'show_bank_details'])->pluck('value', 'key');
        $data['logo'] = null;
        $logoPath = $data['company']['company_logo_path'] ?? null;
        if ($logoPath && \Illuminate\Support\Facades\Storage::disk('public')->exists($logoPath)) {
            $bytes = \Illuminate\Support\Facades\Storage::disk('public')->get($logoPath);
            $image = @getimagesizefromstring($bytes);
            if ($image && in_array($image['mime'], ['image/png', 'image/jpeg'], true)) $data['logo'] = 'data:'.$image['mime'].';base64,'.base64_encode($bytes);
        }
        $section = view('documents.bank-quotation-content', $data)->render();
        $word = $data['format'] === 'word';
        $content = $word ? app(HtmlWordDocument::class)->render([$section], true) : Pdf::loadView('documents.bank-quotation', compact('section'))->setPaper('a4')->output();
        $filename = trim(preg_replace('~[\\\\/:*?"<>|\x00-\x1F]~u', '-', $data['filename']), '. ');
        $filename = preg_replace('/\.(docx|pdf)$/i', '', $filename) ?: 'Bank Quotation';
        return response($content)->withHeaders([
            'Content-Type' => $word ? 'application/vnd.openxmlformats-officedocument.wordprocessingml.document' : 'application/pdf',
            'Content-Disposition' => HeaderUtils::makeDisposition('attachment', $filename.($word ? '.docx' : '.pdf'), 'bank-quotation'.($word ? '.docx' : '.pdf')),
            'Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
