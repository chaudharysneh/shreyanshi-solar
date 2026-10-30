<?php

namespace App\Models;

use App\Traits\Blameable;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use Blameable;

    protected $table = 'category';

    protected $primaryKey = 'id';

    protected $fillable = [
        'name',
        'image',
        'created_by',
        'updated_by',
        'deleted_by',
    ];
}
