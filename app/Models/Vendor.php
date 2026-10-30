<?php

namespace App\Models;

use App\Traits\Blameable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Vendor extends Model
{
    use HasFactory, SoftDeletes, Blameable;

    protected $table = 'vendors';

    protected $fillable = [
        'name',
        'email',
        'phone',
        'address',
        'image',
        'status',
        'created_by',
        'updated_by',
        'deleted_by',
    ];
}
