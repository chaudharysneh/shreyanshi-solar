<?php

namespace App\Models;

use App\Traits\Blameable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductInventory extends Model
{
    use SoftDeletes, Blameable;

    protected $table = 'product_inventory';

    protected $fillable = [
        'product_id',
        'initial_stock',
        'current_stock',
        'type',
        'branch_id',
        'date',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'initial_stock' => 'integer',
        'current_stock' => 'integer',
        'date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
