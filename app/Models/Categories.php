<?php

namespace App\Models;

use App\Traits\Blameable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Categories extends Model
{
    use SoftDeletes, Blameable;

    protected $table = 'product_categories';

    protected $primaryKey = 'id';

    protected $fillable = [
        'name',
        'image',
        'branch_id',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];
}
