<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Lead;
use App\Models\Stage;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LeadConversionService
{
    public function convert(Lead $lead): array
    {
        return DB::transaction(function () use ($lead) {
            // Serialize repeated conversion requests for the same lead.
            $lead = Lead::whereKey($lead->id)->lockForUpdate()->firstOrFail();
            $customer = $lead->converted_customer_id
                ? Customer::whereKey($lead->converted_customer_id)->lockForUpdate()->first() : null;
            if (!$customer && filled($lead->email)) {
                $customer = Customer::where('email', $lead->email)->lockForUpdate()->first();
            }
            if (!$customer && filled($lead->phone)) {
                $customer = Customer::where('phone', $lead->phone)->lockForUpdate()->first();
            }
            $created = !$customer;
            $alreadyConverted = $lead->is_converted && $customer && (int) $lead->converted_customer_id === (int) $customer->id;
            $initializeStage = !$customer || !$customer->pipeline_stage_id;
            $stage = null;
            if ($initializeStage) {
                $stage = Stage::where('is_active', true)->orderBy('sort_order')->orderBy('id')->first();
                if (!$stage) {
                    throw ValidationException::withMessages(['pipeline_stage_id' => 'Please configure an active pipeline stage before converting this lead.']);
                }
            }
            if (!$customer) {
                $sourceDetails = \Illuminate\Support\Facades\Schema::hasColumn('customers','lead_source_id')
                    ? $lead->only(['lead_source_id','source','referral_name','channel_partner_id']) : [];
                $customer = Customer::create([
                    ...$sourceDetails,
                    'name' => $lead->name, 'email' => $lead->email, 'phone' => $lead->phone,
                    'whatsapp' => $lead->whatsapp, 'address' => $lead->address,
                    'company_name' => $lead->company_name, 'image' => $lead->image,
                    'type' => 'Individual', 'is_active' => true, 'pipeline_stage_id' => $stage->id,
                ]);
            } elseif ($initializeStage) {
                $customer->update(['pipeline_stage_id' => $stage->id]);
            }
            if (!$created && !$customer->lead_source_id && \Illuminate\Support\Facades\Schema::hasColumn('customers','lead_source_id')) {
                $customer->update($lead->only(['lead_source_id','source','referral_name','channel_partner_id']));
            }
            if ($initializeStage) {
                app(WorkflowService::class)->syncWorkflowToPipelineStage($customer->id, $stage->id);
            }
            if (!$alreadyConverted) {
                $lead->update(['is_converted' => true, 'converted_customer_id' => $customer->id]);
            }
            return ['customer' => $customer, 'created' => $created, 'already_converted' => (bool) $alreadyConverted];
        });
    }
}
