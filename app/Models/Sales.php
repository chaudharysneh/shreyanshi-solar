<?php

namespace App\Models;

use App\Traits\Blameable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Sales extends Model
{
    use SoftDeletes, Blameable;

    protected $table = 'sales';
    protected $primaryKey = 'invoice_id';
    public $incrementing = true;

    protected $fillable = [
        'customer_id',
        'user_id',
        'handover_id',
        'product_id',
        'template_id',
        'invoice_name',
        'invoice_no',
        'type',
        'invoice_date',
        'due_date',
        'currency',
        'quantity',
        'price',
        'solar_structure_charges',
        'solar_meter_charges',
        'total',
        'gst',
        'discount',
        'subsidy_amount',
        'other_charges',
        'amount',
        'product_name',
        'status',
        'comment',
        'attach_file',
        'customer_docs',
        'isDeleted',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'due_date' => 'date',
        'quantity' => 'integer',
        'price' => 'decimal:2',
        'solar_structure_charges' => 'decimal:2',
        'solar_meter_charges' => 'decimal:2',
        'total' => 'decimal:2',
        'gst' => 'decimal:2',
        'discount' => 'decimal:2',
        'subsidy_amount' => 'decimal:2',
        'other_charges' => 'decimal:2',
        'amount' => 'decimal:2',
        'customer_docs' => 'array',
        'isDeleted' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function handoverPerson()
    {
        return $this->belongsTo(HandoverPerson::class, 'handover_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function deleter()
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }
}
