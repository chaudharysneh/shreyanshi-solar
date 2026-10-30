<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InstallationMaster extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'description', 'is_active'];

    public function stages()
    {
        return $this->hasMany(InstallationStage::class)->orderBy('stage_order');
    }

    public function customers()
    {
        return $this->hasMany(Customer::class, 'installation_master_id');
    }
}
