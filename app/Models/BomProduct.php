<?php

namespace App\Models;

use App\Traits\Blameable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class BomProduct extends Model
{
    use HasFactory, SoftDeletes, Blameable;

    protected $table = 'product';

    protected $fillable = [
        'user_id',
        'product_name',
        'hsn_sac',
        'price',
        'tax_type',
        'tax_rate',
        'category_id',
        'technology_id',
        'warranty_id',
        'description',
        'height',
        'fitting_material',
        'fitting_type',
        'thickness',
        'size_of_pipe',
        'capacity',
        'meter',
        'nos',
        'image',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function categories()
    {
        return $this->belongsToMany(Category::class, 'bom_product_category', 'product_id', 'category_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function technology(): BelongsTo
    {
        return $this->belongsTo(Technology::class, 'technology_id');
    }

    public function warranty(): BelongsTo
    {
        return $this->belongsTo(Warranty::class, 'warranty_id');
    }
}
