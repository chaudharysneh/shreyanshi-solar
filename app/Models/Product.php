<?php

namespace App\Models;

use App\Traits\Blameable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\HasCustomFields;

class Product extends Model
{
    use HasFactory, HasCustomFields, SoftDeletes, Blameable;

    protected $table = 'products';

    protected $fillable = [
        'category_id',
        'serial_no',
        'name',
        'description',
        'quantity',
        'status',
        'availability',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function category()
    {
        return $this->belongsTo(Categories::class, 'category_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function invoiceItems()
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function inventories()
    {
        return $this->hasMany(ProductInventory::class, 'product_id');
    }
}
