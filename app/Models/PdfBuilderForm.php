<?php

namespace App\Models;

use App\Traits\Blameable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PdfBuilderForm extends Model
{
    use HasFactory, SoftDeletes, Blameable;

    protected $fillable = [
        'user_id',
        'form_title',
        'template_name',
        'form_data',
        'company_information',
        'time_line',
        'components',
        'payment_terms',
        'environment_impact',
        'footer',
        'image_paths',
        'first_img',
        'pdf_file',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'form_data' => 'array',
        'company_information' => 'array',
        'time_line' => 'array',
        'components' => 'array',
        'payment_terms' => 'array',
        'environment_impact' => 'array',
        'footer' => 'array',
        'image_paths' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
