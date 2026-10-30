<?php

namespace App\Models;

use App\Traits\Blameable;
use App\Traits\OwnedByUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Invoice extends Model
{
    use HasFactory, SoftDeletes, Blameable, OwnedByUser;

    protected static function booted(): void
    {
        static::creating(fn ($document) => app(\App\Services\CustomerDocumentName::class)->assign($document, 'INV'));
    }

    protected $fillable = [
        'customer_id',
        'user_id',
        'template_id',
        'product_id',
        'currency_id',
        'estimate_id',
        'invoice_name',
        'type',
        'attach_file',
        'invoice_no',
        'invoice_date',
        'due_date',
        'status',
        'is_quotation',
        'comment',
        'quantity',
        'price',
        'solar_structure_charges',
        'solar_meter_charges',
        'other_charges',
        'product_name',
        'total',
        'gst',
        'gst_amount',
        'gst_breakdown',
        'discount',
        'subsidy_amount',
        'amount',
        'generation_data',
        'customer_docs',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'due_date' => 'date',
        'is_quotation' => 'boolean',
        'quantity' => 'decimal:2',
        'price' => 'decimal:2',
        'solar_structure_charges' => 'decimal:2',
        'other_charges' => 'decimal:2',
        'total' => 'decimal:2',
        'gst' => 'decimal:2',
        'gst_amount' => 'decimal:2',
        'discount' => 'decimal:2',
        'subsidy_amount' => 'decimal:2',
        'amount' => 'decimal:2',
        'product_name' => 'array',
        'gst_breakdown' => 'array',
        'generation_data' => 'array',
        'customer_docs' => 'array',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function estimate()
    {
        return $this->belongsTo(Estimate::class, 'estimate_id', 'estimate_id');
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function items()
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function currency()
    {
        return $this->belongsTo(Currency::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function financePlan() { return $this->hasOne(InvoiceFinancePlan::class); }
    public function financeEvents() { return $this->hasMany(InvoiceFinanceEvent::class)->latest('id'); }

    public function payments()
    {
        return $this->hasMany(Payment::class)->whereNull('voided_at');
    }
}
