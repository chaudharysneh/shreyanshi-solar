<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class InvoiceFinancePlan extends Model {
    protected $guarded=['id'];
    protected $casts=['expected_bank_date'=>'date','bank_amount'=>'decimal:2','down_payment'=>'decimal:2'];
}
