<?php
namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Route;

class SolarAssistantService
{
    private function allowed($user,string $key,array $definition): bool
    {
        return $key==='staff' ? $user->isAdmin() : $user->hasMatrixPermission('view_'.$definition[1]);
    }
    public function catalog($user): array
    {
        $modules=[];
        foreach (config('solar_assistant.modules') as $key=>$definition) if ($key!=='pipeline' && $this->allowed($user,$key,$definition)) $modules[]=['key'=>$key,'label'=>$definition[0]];
        return ['reply'=>'Choose a topic or ask a question.','modules'=>$modules,'examples'=>['Pending estimates','Unpaid invoices','Overdue invoices','Equipment pending','Partners this month','Partner 1 report','Customers today','Follow-ups this week']];
    }
    public function query($user,string $key,array $definition)
    {
        $model=new $definition[2];$table=$model->getTable();$query=$model->newQuery();
        if (Schema::hasColumn($table,'isDeleted')) $query->where(fn ($q)=>$q->where('isDeleted',false)->orWhereNull('isDeleted'));
        if (!$user->isAdmin() && $key!=='customers' && Gate::getPolicyFor($model)) {
            $columns=array_filter(['created_by','assigned_user_id','user_id'],fn ($column)=>Schema::hasColumn($table,$column));
            if ($columns) $query->where(function ($q) use ($columns,$table,$user) { foreach ($columns as $column) $q->orWhere($table.'.'.$column,$user->id); });
            else $query->whereRaw('1 = 0');
        }
        return $query;
    }
    public function answer(Request $request): array
    {
        $user=$request->user();
        if ($request->input('action')==='catalog') return $this->catalog($user);
        $message=trim((string) $request->input('message',''));
        $text=strtolower($message);
        if (preg_match('/\b(update|delete|approve|reject|mark|pay|create)\b/',$text)) return ['reply'=>'Please make changes from the relevant CRM page. I can safely look up records, balances, equipment progress and partner reports.'];
        if (!$request->filled('module') && in_array($text,['help','hi','hello','what can you do',''],true)) return $this->catalog($user)+['data'=>[]];
        if (preg_match('/^partners?\s+(report|earnings|commission|balance)$/',$text)) return ['reply'=>'Ask “Partner 1 report” with the partner ID, or search partners by name and open their dashboard.'];
        if (preg_match('/\bpartner\s*#?\s*(\d+)\s*(?:report|dashboard|earnings|commission|balance)?\b/',$text,$match)) return $this->partnerReport($user,(int) $match[1]);
        if (in_array($text,['overview','dashboard','solar overview','summary','business summary'],true)) return $this->overview($user);
        $key=$request->input('module');$period=$request->input('period','all');
        if (preg_match('/^fetch_(\w+?)_(today|yesterday|this_week|last_week|this_month|last_month|this_year|last_year|all)$/',$text,$match)) { $key=$match[1];$period=$match[2]; }
        elseif ($text==='fetch_customers') $key='customers';
        if (!$key) foreach (['equipment'=>'estimates','estimate'=>'estimates','quotation'=>'estimates','invoice'=>'invoices','payment'=>'invoices','partner'=>'partners','customer'=>'customers','follow-up'=>'followups','followup'=>'followups','lead'=>'leads','ticket'=>'tickets','task'=>'tasks','meeting'=>'meetings','project'=>'projects','deal'=>'deals','bom'=>'products','product'=>'products','pipeline'=>'pipeline','service'=>'services','staff'=>'staff'] as $word=>$module) if (str_contains($text,$word)) { $key=$module;break; }
        $definition=config('solar_assistant.modules.'.$key);
        if (!$definition) return ['reply'=>'Try “pending estimates”, “unpaid invoices”, “equipment partial”, “partner 1 report”, or choose a topic below.']+$this->catalog($user);
        if (!$this->allowed($user,$key,$definition)) return ['reply'=>'You do not have permission to view '.$definition[0].'.','data'=>[]];
        $query=$this->query($user,$key,$definition);$table=$query->getModel()->getTable();
        $now=Carbon::now('Asia/Kolkata');
        foreach (['today','yesterday','this week','last week','this month','last month','this year','last year'] as $phrase) if (str_contains(str_replace('_',' ',$text),$phrase)) { $period=str_replace(' ','_',$phrase);break; }
        $bounds=match($period) {
            'today'=>[$now->copy()->startOfDay(),$now->copy()->endOfDay()],
            'yesterday'=>[$now->copy()->subDay()->startOfDay(),$now->copy()->subDay()->endOfDay()],
            'this_week'=>[$now->copy()->startOfWeek(),$now->copy()->endOfWeek()],
            'last_week'=>[$now->copy()->subWeek()->startOfWeek(),$now->copy()->subWeek()->endOfWeek()],
            'this_month'=>[$now->copy()->startOfMonth(),$now->copy()->endOfMonth()],
            'last_month'=>[$now->copy()->subMonthNoOverflow()->startOfMonth(),$now->copy()->subMonthNoOverflow()->endOfMonth()],
            'this_year'=>[$now->copy()->startOfYear(),$now->copy()->endOfYear()],
            'last_year'=>[$now->copy()->subYear()->startOfYear(),$now->copy()->subYear()->endOfYear()],default=>null,
        };
        $dateColumn=Schema::hasColumn($table,$definition[5])?$definition[5]:'created_at';
        if ($bounds) {
            if (Schema::getColumnType($table,$dateColumn)==='date') $query->whereDate($dateColumn,'>=',$bounds[0]->toDateString())->whereDate($dateColumn,'<=',$bounds[1]->toDateString());
            else $query->whereBetween($dateColumn,array_map(fn ($date)=>$date->setTimezone(config('app.timezone'))->format('Y-m-d H:i:s'),$bounds));
        }
        $status=$request->input('status');
        foreach (['unpaid','pending','approved','rejected','inactive','active','paid','completed','open'] as $word) if (preg_match('/\b'.$word.'\b/',$text)) { $status=$word;break; }
        $equipment=str_contains($text,'equipment') || $request->boolean('equipment');
        if ($equipment && $key!=='estimates') return ['reply'=>'Equipment progress is tracked against estimates. Try “equipment pending”, “equipment partial” or “equipment complete”.'];
        if ($equipment) $query->whereIn('status',['approved','completed']);
        if (!$equipment && $status && Schema::hasColumn($table,'status') && in_array($status,['unpaid','pending','approved','rejected','inactive','active','paid','completed','open'],true)) $query->where('status',$status);
        if (str_contains($text,'overdue')) {
            if (in_array($key,['invoices','tasks','followups'],true)) {
                $due=$key==='invoices'?'due_date':$dateColumn;
                $query->where($due,'<',$now->copy()->startOfDay()->setTimezone(config('app.timezone')));
                if (Schema::hasColumn($table,'status')) $query->whereNotIn('status',['paid','completed','cancelled','void','draft']);
            }
        }
        $search=trim((string) $request->input('search',''));
        if (!$search && preg_match('/^(?:find|search|show)?\s*(?:customer|partner|lead|estimate|invoice)s?\s+(.+)$/i',$message,$match)) {
            $candidate=trim($match[1]);
            if (!preg_match('/\b(today|yesterday|this|last|pending|approved|paid|unpaid|overdue|active|inactive|all)\b/i',$candidate)) $search=$candidate;
        }
        if ($search) $query->where(function ($q) use ($table,$definition,$search) {
            foreach (array_unique([$definition[3],'phone','email','estimate_no','invoice_no','partner_code']) as $column) if (Schema::hasColumn($table,$column)) $q->orWhere($column,'like','%'.$search.'%');
        });
        $page=max(1,min(10000,$request->integer('page',1)));$size=10;
        if ($equipment) {
            $wanted='';foreach (['pending','partial','complete'] as $value) if (preg_match('/\b'.$value.'\b/',$text)) $wanted=ucfirst($value);
            $records=collect();$total=0;
            foreach ($query->orderBy($query->getModel()->getKeyName())->cursor() as $record) {
                if ($wanted && $record->equipment_progress!==$wanted) continue;
                if ($total>=($page-1)*$size && $records->count()<$size) $records->push($record);
                $total++;
            }
        } else {
            $total=(clone $query)->count();$records=$query->orderByDesc($query->getModel()->getKeyName())->forPage($page,$size)->get();
        }
        $data=$records->filter(fn ($record)=>!Gate::getPolicyFor($record) || $user->can('view',$record))->map(fn ($record)=>$this->record($record,$key,$definition))->values();
        return ['reply'=>$total ? $definition[0].': '.$total.' matching records'.($bounds?' · '.str_replace('_',' ',$period):'').'. Showing page '.$page.'.' : 'No matching '.$definition[0].' found.',
            'data'=>$data,'next'=>$total>$page*$size ? ['message'=>$message,'module'=>$key,'period'=>$period,'status'=>$status,'search'=>$search,'equipment'=>$equipment,'page'=>$page+1] : null,
            'as_of'=>$now->format('d M Y, h:i A').' IST'];
    }
    public function record($record,string $key,array $definition): array
    {
        $fields=[];
        if ($key==='partners') $fields[]=['label'=>'Partner ID','value'=>$record->getKey()];
        foreach (['phone'=>'Phone','email'=>'Email','status'=>'Status','estimate_no'=>'Estimate No.','invoice_no'=>'Invoice No.','quantity'=>'Capacity (kW)','due_date'=>'Due Date'] as $field=>$label) {
            $value=$record->getAttribute($field);
            if (is_scalar($value) && $value!=='') $fields[]=['label'=>$label,'value'=>$value];
        }
        if ($key==='estimates') $fields[]=['label'=>'Equipment','value'=>$record->equipment_progress];
        if (in_array($key,['estimates','invoices'])) {
            $code=$key==='estimates' ? ($record->currency ?: \App\Support\DocumentCurrency::defaultCode()) : ($record->currency?->code ?: $record->estimate?->currency ?: \App\Support\DocumentCurrency::defaultCode());
            $fields[]=['label'=>'Amount','value'=>number_format((float) $record->amount,2).' '.$code];
        }
        $url=Route::has($definition[4]) ? route($definition[4],$record->getKey()) : null;
        $name=$record->getAttribute($definition[3]);
        return ['id'=>$record->getKey(),'type'=>$key,'name'=>is_string($name)&&$name!==''?$name:$definition[0].' #'.$record->getKey(),'fields'=>$fields,'url'=>$url];
    }
    private function partnerReport($user,int $id): array
    {
        if (!$user->hasMatrixPermission('view_partners')) return ['reply'=>'You do not have permission to view partner reports.'];
        $partner=\App\Models\ChannelPartner::find($id);
        if (!$partner) return ['reply'=>'Partner not found.'];
        $report=app(PartnerReportService::class)->build($partner);$fields=[];
        foreach (['leads'=>'Leads given','converted'=>'Converted leads','collected'=>'Customer collections','earned'=>'Partner earned','paid'=>'Paid to partner','pending'=>'Pending commission','net_after_commission'=>'Collections after commission'] as $key=>$label) $fields[]=['label'=>$label,'value'=>in_array($key,['leads','converted'])?$report[$key]:number_format($report[$key],2).' '.$report['currency']];
        return ['reply'=>'Current report for '.$partner->name.'. Commission uses current partner settings. Collections after commission are not net profit.','data'=>[['type'=>'partners','name'=>$partner->name,'url'=>route('partners.show',$partner),'fields'=>$fields]]];
    }
    private function overview($user): array
    {
        $fields=[];
        foreach (['partners','leads','customers','estimates','invoices'] as $key) {
            $definition=config('solar_assistant.modules.'.$key);
            if (!$this->allowed($user,$key,$definition)) continue;
            $query=$this->query($user,$key,$definition);
            $fields[]=['label'=>$definition[0],'value'=>$query->count()];
            if ($key==='estimates') $fields[]=['label'=>'Awaiting approval','value'=>(clone $query)->where('status','pending')->count()];
            if ($key==='invoices') $fields[]=['label'=>'Unpaid invoices','value'=>(clone $query)->where('status','unpaid')->count()];
        }
        return ['reply'=>'Live solar CRM overview, limited to your access.','data'=>[['type'=>'summary','name'=>'Business overview','fields'=>$fields]],'as_of'=>Carbon::now('Asia/Kolkata')->format('d M Y, h:i A').' IST'];
    }
}
