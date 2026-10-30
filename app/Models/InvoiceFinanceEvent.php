<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class InvoiceFinanceEvent extends Model {
    protected $guarded=['id'];protected $casts=['details'=>'array'];
    public function creator(){return $this->belongsTo(User::class,'created_by');}
}
