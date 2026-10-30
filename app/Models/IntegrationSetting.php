<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IntegrationSetting extends Model
{
    use HasFactory;

    protected $table = 'integration_settings';

    protected $fillable = [
        'social_media_integration',
        'whatsapp_integration',
        'email_smtp',
        'google_connection',
    ];

    protected $casts = [
        'social_media_integration' => 'boolean',
        'whatsapp_integration' => 'boolean',
        'email_smtp' => 'boolean',
        'google_connection' => 'boolean',
    ];
}
