<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChannelPartnerPayment extends Model
{
    protected $fillable = ['channel_partner_id','amount','currency','payment_date','method','reference','notes','submission_key','created_by'];
    protected $casts = ['amount'=>'decimal:2','payment_date'=>'date','voided_at'=>'datetime'];
    public function creator() { return $this->belongsTo(User::class,'created_by'); }
}
