<?php

namespace App\Support;

use App\Models\Customer;
use App\Models\LeadSource;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CustomerSourceDetails
{
    public static function rules(Request $request, ?Customer $customer = null): array
    {
        $source = LeadSource::find($request->input('lead_source_id',$customer?->lead_source_id));
        return [
            'lead_source_id'=>['nullable','integer','exists:lead_sources,id'],
            'source'=>['nullable','string','max:255'],
            'referral_name'=>[Rule::requiredIf($source?->kind === 'referral'),'nullable','string','max:255'],
            'channel_partner_id'=>[Rule::requiredIf($source?->kind === 'channel_partner'),'nullable','integer',Rule::exists('channel_partners','id')->where(function ($query) use ($customer) {
                $query->where(function ($q) use ($customer) { $q->where('status','active');if ($customer?->channel_partner_id) $q->orWhere('id',$customer->channel_partner_id); });
            })],
        ];
    }
    public static function normalize(array $data, ?Customer $customer = null): array
    {
        if (!array_key_exists('lead_source_id',$data)) return $data;
        $source=LeadSource::find($data['lead_source_id']);
        $data['source']=$source?->name;
        $data['referral_name']=$source?->kind === 'referral' ? ($data['referral_name'] ?? $customer?->referral_name) : null;
        $data['channel_partner_id']=$source?->kind === 'channel_partner' ? ($data['channel_partner_id'] ?? $customer?->channel_partner_id) : null;
        return $data;
    }
}
