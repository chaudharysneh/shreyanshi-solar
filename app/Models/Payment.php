<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'source','created_by','voided_at','voided_by','void_reason',
        'invoice_id',
        'transaction_id',
        'amount',
        'payment_method',
        'payment_date',
        'notes',
    ];

    protected $casts=['payment_date'=>'date','amount'=>'decimal:2','voided_at'=>'datetime'];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }
}
