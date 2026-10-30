<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class DashboardDocumentVersion extends Model {
    protected $guarded=['id'];
    protected $casts=['fields'=>'array'];
    protected $hidden=['aadhaar_path'];
    public function document() { return $this->belongsTo(DashboardDocument::class,'dashboard_document_id'); }
    public function creator() { return $this->belongsTo(User::class,'user_id'); }
}
