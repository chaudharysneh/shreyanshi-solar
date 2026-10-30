<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class FinanceBankController extends Controller {
    public function index(){return response()->json(['banks'=>DB::table('finance_banks')->orderBy('name')->get(),'branches'=>DB::table('finance_bank_branches')->orderBy('name')->get()]);}
    public function store(Request $request){
        $data=$request->validate(['name'=>['required','string','max:255'],'bank_id'=>['nullable','integer','exists:finance_banks,id']]);
        $name=trim($data['name']);abort_if($name==='',422,'Enter a name.');
        $table=isset($data['bank_id'])?'finance_bank_branches':'finance_banks';$keys=['name'=>$name];if(isset($data['bank_id']))$keys['bank_id']=$data['bank_id'];
        DB::table($table)->insertOrIgnore($keys+['created_at'=>now(),'updated_at'=>now()]);
        return response()->json(['record'=>DB::table($table)->where($keys)->first()]);
    }
}
