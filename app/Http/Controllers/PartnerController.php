<?php

namespace App\Http\Controllers;

use App\Models\ChannelPartner;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class PartnerController extends Controller
{
    private function filtered(Request $request)
    {
        return ChannelPartner::query()
            ->when($request->filled('search'), function ($query) use ($request) {
                $query->where(function ($query) use ($request) {
                    foreach (['name', 'partner_code', 'phone', 'email', 'company_name'] as $column) $query->orWhere($column, 'like', '%'.$request->input('search').'%');
                });
            })
            ->when(in_array($request->status, ['active', 'inactive']), fn ($query) => $query->where('status', $request->status))
            ->when(in_array($request->commission_type, ['fixed', 'percent']), fn ($query) => $query->where('commission_type', $request->commission_type))
            ->when($request->filled('assigned_user_id'), fn ($query) => $query->where('assigned_user_id', $request->integer('assigned_user_id')));
    }

    public function index(Request $request)
    {
        $query = $this->filtered($request);
        $ids = (clone $query)->select('channel_partners.id');
        $kpis = [
            'Total Partners' => (clone $query)->count(),
            'Active Partners' => (clone $query)->where('status', 'active')->count(),
            'Referred Leads' => Lead::whereIn('channel_partner_id', clone $ids)->count(),
            'Converted Leads' => Lead::whereIn('channel_partner_id', clone $ids)->where('is_converted', true)->count(),
        ];
        $perPage = in_array($request->integer('per_page', 10), [10, 25, 50, 100]) ? $request->integer('per_page', 10) : 10;
        $partners = $query->with('assignedUser')->withCount('leads')->latest('id')->paginate($perPage)->withQueryString();
        if ($request->expectsJson()) return response()->json([
            'html' => view('crm.partners.partials.table', compact('partners'))->render(), 'kpis' => array_values($kpis),
        ]);
        $users = User::orderBy('name')->get();
        return view('crm.partners.index', compact('partners', 'kpis', 'users'));
    }

    public function create()
    {
        return view('crm.partners.form', ['partner' => new ChannelPartner, 'users' => User::orderBy('name')->get()]);
    }

    public function edit(ChannelPartner $partner)
    {
        return view('crm.partners.form', ['partner' => $partner, 'users' => User::orderBy('name')->get()]);
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'], 'phone' => ['required', 'string', 'max:50'],
            'whatsapp' => ['nullable', 'string', 'max:50'], 'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:2000'], 'company_name' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'assigned_user_id' => ['nullable', 'integer', 'exists:users,id'], 'joining_date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:10000'],
            'commission_type' => ['required', Rule::in(['fixed', 'percent'])],
            'commission_value' => ['required', 'numeric', 'min:0', 'max:'.($request->commission_type === 'percent' ? '100' : '999999999999.99')],
            'commission_basis' => ['required', Rule::in(['estimate_subtotal', 'amount_received'])],
            'commission_currency' => ['sometimes', 'required', 'regex:/^[A-Z]{3}$/'],
            'payable_on' => ['required', Rule::in(['conversion', 'installation', 'payment_collection'])],
            'pan_number' => ['nullable', 'regex:/^[A-Za-z]{5}[0-9]{4}[A-Za-z]$/'],
            'gst_number' => ['nullable', 'regex:/^[0-9]{2}[A-Za-z]{5}[0-9]{4}[A-Za-z][A-Za-z0-9]Z[A-Za-z0-9]$/'],
            'bank_name' => ['nullable', 'string', 'max:255'], 'account_holder' => ['nullable', 'string', 'max:255'],
            'account_number' => ['nullable', 'string', 'max:40'], 'ifsc_code' => ['nullable', 'regex:/^[A-Za-z]{4}0[A-Za-z0-9]{6}$/'],
            'agreement' => ['nullable', 'file', 'mimes:pdf,doc,docx,jpg,jpeg,png', 'max:10240'],
        ]);
        unset($data['agreement']);
        foreach (['pan_number', 'gst_number', 'ifsc_code'] as $field) if (!empty($data[$field])) $data[$field] = strtoupper($data[$field]);
        return $data;
    }

    private function save(Request $request, ChannelPartner $partner)
    {
        $data = $this->validated($request);
        if ($partner->exists && isset($data['commission_currency']) && $data['commission_currency'] !== $partner->commission_currency && $partner->payments()->exists()) {
            throw \Illuminate\Validation\ValidationException::withMessages(['commission_currency'=>'Currency cannot be changed after partner payments have been recorded.']);
        }
        $previousAgreement = $partner->agreement_path;
        $newAgreement = null;
        if ($request->hasFile('agreement')) {
            $newAgreement = $request->file('agreement')->store('partner-agreements', 'local');
            if (!$newAgreement) throw \Illuminate\Validation\ValidationException::withMessages(['agreement' => 'Unable to store the agreement. Please try again.']);
            $data['agreement_path'] = $newAgreement;
            $data['agreement_name'] = $request->file('agreement')->getClientOriginalName();
        }
        try {
            $partner->fill($data)->save();
        } catch (\Throwable $exception) {
            if ($newAgreement) Storage::disk('local')->delete($newAgreement);
            throw $exception;
        }
        if ($newAgreement && $previousAgreement) Storage::disk('local')->delete($previousAgreement);
        return redirect()->route('partners.show', $partner)->with('success', 'Partner saved successfully.');
    }

    public function store(Request $request) { return $this->save($request, new ChannelPartner); }
    public function update(Request $request, ChannelPartner $partner) { return $this->save($request, $partner); }

    public function show(ChannelPartner $partner)
    {
        $partner->load('assignedUser');
        $leads = $partner->leads()->when(!auth()->user()->hasMatrixPermission('view_leads'), fn ($query) => $query->whereRaw('1 = 0'))->latest()->paginate(10)->withQueryString();
        $report = app(\App\Services\PartnerReportService::class)->build($partner);
        $payments = $partner->payments()->with('creator')->latest('payment_date')->latest('id')->paginate(10, ['*'], 'payments_page')->withQueryString();
        return view('crm.partners.show', compact('partner', 'leads', 'report', 'payments'));
    }

    public function recordPayment(Request $request, ChannelPartner $partner)
    {
        $data = $request->validate([
            'amount'=>['required','numeric','decimal:0,2','min:0.01','max:999999999999.99'],
            'payment_date'=>['required','date','before_or_equal:'.now()->timezone('Asia/Kolkata')->toDateString()],
            'method'=>['required',Rule::in(['bank_transfer','upi','cash','cheque','other'])],
            'reference'=>['nullable','string','max:255'],'notes'=>['nullable','string','max:2000'],
            'submission_key'=>['required','uuid'],
        ]);
        \Illuminate\Support\Facades\DB::transaction(function () use ($partner,$data) {
            $locked = ChannelPartner::whereKey($partner->id)->lockForUpdate()->firstOrFail();
            $existing = \App\Models\ChannelPartnerPayment::where('submission_key',$data['submission_key'])->first();
            if ($existing && (int) $existing->channel_partner_id === (int) $locked->id) return;
            if ($existing) throw \Illuminate\Validation\ValidationException::withMessages(['amount'=>'This payment request has already been used.']);
            $report = app(\App\Services\PartnerReportService::class)->build($locked);
            if (round($data['amount']*100) > round($report['pending']*100)) {
                throw \Illuminate\Validation\ValidationException::withMessages(['amount'=>'Payment cannot exceed the pending commission of '.number_format($report['pending'],2).' '.$report['currency'].'.']);
            }
            \App\Models\ChannelPartnerPayment::create(array_merge($data,[
                'channel_partner_id'=>$locked->id,'currency'=>$report['currency'],'created_by'=>auth()->id(),
            ]));
        });
        return redirect()->to(route('partners.show',$partner).'#partner-payments')->with('success','Partner payment recorded.');
    }

    public function voidPayment(Request $request, ChannelPartner $partner, \App\Models\ChannelPartnerPayment $payment)
    {
        abort_unless((int) $payment->channel_partner_id === (int) $partner->id,404);
        $data = $request->validate(['void_reason'=>['required','string','max:2000']]);
        \Illuminate\Support\Facades\DB::transaction(function () use ($partner,$payment,$data) {
            ChannelPartner::whereKey($partner->id)->lockForUpdate()->firstOrFail();
            $locked = \App\Models\ChannelPartnerPayment::whereKey($payment->id)->lockForUpdate()->firstOrFail();
            if (!$locked->voided_at) $locked->forceFill(['voided_at'=>now(),'voided_by'=>auth()->id(),'void_reason'=>$data['void_reason']])->save();
        });
        return redirect()->to(route('partners.show',$partner).'#partner-payments')->with('success','Payment voided; pending commission has been updated.');
    }

    public function destroy(Request $request, ChannelPartner $partner)
    {
        $hasCustomers = \Illuminate\Support\Facades\Schema::hasColumn('customers','channel_partner_id') && \Illuminate\Support\Facades\DB::table('customers')->where('channel_partner_id',$partner->id)->exists();
        if ($hasCustomers || $partner->payments()->exists() || \Illuminate\Support\Facades\DB::table('leads')->where('channel_partner_id', $partner->id)->exists()) {
            return back()->with('error', 'This partner has referred leads. Set their status to Inactive to preserve the lead history.');
        }
        $path = $partner->agreement_path;
        $partner->delete();
        if ($path) Storage::disk('local')->delete($path);
        return redirect()->route('partners.index')->with('success', 'Partner deleted successfully.');
    }

    public function agreement(ChannelPartner $partner)
    {
        abort_unless($partner->agreement_path && Storage::disk('local')->exists($partner->agreement_path), 404);
        return Storage::disk('local')->download($partner->agreement_path, $partner->agreement_name);
    }

    public function export(Request $request)
    {
        return response()->streamDownload(function () use ($request) {
            $output = fopen('php://output', 'w');
            fputcsv($output, ['Code', 'Name', 'Company', 'Phone', 'WhatsApp', 'Email', 'Status', 'Commission Type', 'Commission Value', 'Basis', 'Payable On', 'Joining Date']);
            foreach ($this->filtered($request)->orderBy('id')->cursor() as $partner) {
                $row = [$partner->partner_code, $partner->name, $partner->company_name, $partner->phone, $partner->whatsapp, $partner->email, $partner->status, $partner->commission_type, $partner->commission_value, $partner->commission_basis, $partner->payable_on, $partner->joining_date?->format('Y-m-d')];
                fputcsv($output, array_map(fn ($value) => preg_match('/^[=+@\-\t\r]/', (string) $value) ? "'".$value : $value, $row));
            }
            fclose($output);
        }, 'partners.csv', ['Content-Type' => 'text/csv']);
    }
}
