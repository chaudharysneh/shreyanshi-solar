<?php

namespace App\Models;

use App\Traits\Blameable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Estimate extends Model
{
    use SoftDeletes, Blameable;

    protected $table = 'estimates';
    protected $primaryKey = 'estimate_id';
    public $incrementing = true;
    protected $appends = ['equipment_progress'];

    public function getEquipmentProgressAttribute(): string
    {
        $equipment=$this->equipment_serials ?? [];
        $required=max(0,(int) ceil((float) $this->quantity*(int) ($equipment['panels_per_kw'] ?? 2)));
        $filled=fn ($type)=>count(array_filter($equipment[$type] ?? [],fn ($serial)=>trim((string) $serial)!==''));
        $panels=$filled('panels');$inverters=$filled('inverters');
        if ($required>0 && $panels >= $required && $inverters>0 && $inverters === count($equipment['inverters'] ?? [])) return 'Complete';
        return $panels+$inverters>0 ? 'Partial' : 'Pending';
    }

    protected static function booted(): void
    {
        static::creating(fn ($document) => app(\App\Services\CustomerDocumentName::class)->assign($document, 'EST'));
    }

    protected $fillable = [
        'customer_id',
        'user_id',
        'product_id',
        'template_id',
        'estimate_name',
        'estimate_no',
        'reference_no',
        'terms_conditions',
        'bom_specifications',
        'type',
        'estimate_date',
        'valid_until',
        'currency',
        'quantity',
        'price',
        'price_mode',
        'solar_structure_charges',
        'solar_meter_charges',
        'total',
        'gst',
        'gst_amount',
        'gst_breakdown',
        'discount',
        'subsidy_amount',
        'other_charges',
        'amount',
        'product_name',
        'status',
        'comment',
        'attach_file',
        'customer_docs',
        'equipment_serials',
        'generation_data',
        'is_quotation',
        'isDeleted',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'bom_specifications' => 'array',
        'estimate_date' => 'date',
        'valid_until' => 'date',
        'quantity' => 'decimal:2',
        'price' => 'float',
        'solar_structure_charges' => 'float',
        'total' => 'float',
        'gst' => 'float',
        'gst_amount' => 'float',
        'gst_breakdown' => 'array',
        'discount' => 'float',
        'subsidy_amount' => 'float',
        'other_charges' => 'float',
        'amount' => 'float',
        'customer_docs' => 'array',
        'equipment_serials' => 'array',
        'generation_data' => 'array',
        'is_quotation' => 'boolean',
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
