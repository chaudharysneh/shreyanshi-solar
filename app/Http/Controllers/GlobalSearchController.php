<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Schema,Gate};
use App\Services\SolarAssistantService;
class GlobalSearchController extends Controller {
    public function __invoke(Request $request,SolarAssistantService $search){
        $definitions=config('solar_assistant.modules');
        $definitions['inventory']=['Inventory','inventory',\App\Models\Product::class,'name','inventory.history','created_at'];
        $request->validate(['q'=>['required','string','max:120'],'module'=>['nullable','string','in:'.implode(',',array_keys($definitions))],'page'=>['nullable','integer','min:1','max:10000']]);
        $user=$request->user();$term=trim($request->q);$groups=[];$page=$request->integer('page',1);
        if(mb_strlen($term)<2)return response()->json(['groups'=>[],'total'=>0]);
        $allowed=array_column($search->catalog($user)['modules'],'key');
        if($user->hasMatrixPermission('view_inventory'))$allowed[]='inventory';
        foreach($definitions as $key=>$definition){
            if(!in_array($key,$allowed)||($request->filled('module')&&$key!==$request->module))continue;
            $model=new $definition[2];if(!Schema::hasTable($model->getTable()))continue;
            $columns=array_values(array_filter(array_unique([$definition[3],'name','title','phone','email','estimate_no','invoice_no','partner_code','ticket_no','serial_no']),fn($column)=>Schema::hasColumn($model->getTable(),$column)));
            if(!$columns)continue;
            $aliases=['products'=>'bom bill of materials products','followups'=>'follow ups follow-ups followups','staff'=>'staff users employees','estimates'=>'estimates quotations quotes','partners'=>'partners channel partners','inventory'=>'inventory stock'];
            $moduleTerm=preg_replace('/^manage\s+/i','',mb_strtolower($term));
            $moduleMatch=str_contains(mb_strtolower($definition[0].' '.$key.' '.($aliases[$key]??'')),$moduleTerm);
            $query=$search->query($user,$key,$definition);
            if(!$moduleMatch)$query->where(function($q)use($columns,$term,$model){foreach($columns as $column)$q->orWhere($model->qualifyColumn($column),'like','%'.$term.'%');
                if(method_exists($model,'customer'))$q->orWhereHas('customer',fn($customer)=>$customer->where('name','like','%'.$term.'%')->orWhere('phone','like','%'.$term.'%'));
            });
            $count=(clone $query)->count();if(!$count&&!$moduleMatch)continue;
            $items=$query->orderByDesc($model->getKeyName())->forPage($page,8)->get()->filter(fn($record)=>!Gate::getPolicyFor($record)||$user->can('view',$record))->map(function($record)use($search,$key,$definition){$item=$search->record($record,$key,$definition);if(method_exists($record,'customer')&&$record->customer)$item['fields'][]=['label'=>'Customer','value'=>$record->customer->name];return $item;})->values();
            if($items->isEmpty()&&!$moduleMatch)continue;
            $groups[]=['key'=>$key,'label'=>$definition[0],'total'=>$count,'items'=>$items,'has_more'=>$count>$page*8];
        }
        return response()->json(['groups'=>$groups,'total'=>array_sum(array_column($groups,'total')),'page'=>$page]);
    }
}
