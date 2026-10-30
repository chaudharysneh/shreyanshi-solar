<?php
// Module, permission, model, display field, route, and date field are defined once.
return ['modules'=>[
    'partners'=>['Partners','partners',\App\Models\ChannelPartner::class,'name','partners.show','created_at'],
    'estimates'=>['Estimates','estimates',\App\Models\Estimate::class,'estimate_name','estimates.show','created_at'],
    'invoices'=>['Invoices','invoices',\App\Models\Invoice::class,'invoice_name','invoices.show','created_at'],
    'leads'=>['Leads','leads',\App\Models\Lead::class,'name','leads.show','created_at'],
    'customers'=>['Customers','customers',\App\Models\Customer::class,'name','masters.customers.show','created_at'],
    'followups'=>['Follow-ups','followups',\App\Models\FollowUp::class,'title','followups.show','follow_up_at'],
    'tasks'=>['Tasks','tasks',\App\Models\Task::class,'title','tasks.show','due_date'],
    'meetings'=>['Meetings','meetings',\App\Models\Meeting::class,'title','meetings.show','meeting_date'],
    'tickets'=>['Tickets','tickets',\App\Models\SupportTicket::class,'ticket_name','tickets.show','created_at'],
    'deals'=>['Deals','deals',\App\Models\Deal::class,'title','deals.show','created_at'],
    'pipeline'=>['Pipeline','pipeline',\App\Models\Pipeline::class,'pipeline_name','pipeline.show','created_at'],
    'products'=>['BOM','bom',\App\Models\BomProduct::class,'product_name','bom-products.edit','created_at'],
    'services'=>['Services','services',\App\Models\Service::class,'service_name','services.show','created_at'],
    'staff'=>['Staff','users',\App\Models\User::class,'name','users.show','created_at'],
]];
