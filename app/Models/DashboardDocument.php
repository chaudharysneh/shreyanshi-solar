<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class DashboardDocument extends Model {
    protected $fillable=['user_id','customer_id','type','filename','fields','aadhaar_path'];
    protected $casts=['fields'=>'array'];
    protected $hidden=['aadhaar_path'];
    public function versions() { return $this->hasMany(DashboardDocumentVersion::class)->orderByDesc('version'); }
    public function customer() { return $this->belongsTo(Customer::class); }
}
