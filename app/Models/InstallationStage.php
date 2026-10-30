<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InstallationStage extends Model
{
    use HasFactory;

    protected $fillable = [
        'system_key','installation_master_id', 'stage_name', 'stage_order', 'is_active'];

    public function installationMaster()
    {
        return $this->belongsTo(InstallationMaster::class);
    }
}
