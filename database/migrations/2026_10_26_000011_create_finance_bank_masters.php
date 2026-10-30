<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{Schema,DB};
return new class extends Migration {
    public function up(): void {
        Schema::create('finance_banks', function(Blueprint $table){$table->id();$table->string('name')->unique();$table->timestamps();});
        Schema::create('finance_bank_branches', function(Blueprint $table){$table->id();$table->foreignId('bank_id')->constrained('finance_banks');$table->string('name');$table->timestamps();$table->unique(['bank_id','name']);});
        if(Schema::hasTable('invoice_finance_plans')) foreach(DB::table('invoice_finance_plans')->where('funding_type','bank')->get() as $plan){
            $name=trim($plan->bank_name??'');$branch=trim($plan->branch_name??'');if(!$name)continue;
            DB::table('finance_banks')->insertOrIgnore(['name'=>$name,'created_at'=>now(),'updated_at'=>now()]);
            $id=DB::table('finance_banks')->where('name',$name)->value('id');
            if($branch)DB::table('finance_bank_branches')->insertOrIgnore(['bank_id'=>$id,'name'=>$branch,'created_at'=>now(),'updated_at'=>now()]);
        }
    }
    public function down(): void {Schema::dropIfExists('finance_bank_branches');Schema::dropIfExists('finance_banks');}
};
