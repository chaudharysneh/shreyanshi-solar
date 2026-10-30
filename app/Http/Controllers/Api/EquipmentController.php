<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Estimate;
use App\Models\Invoice;
use App\Models\Customer;
use Illuminate\Http\Request;

class EquipmentController extends Controller
{
    private function check(Request $request, Estimate $estimate): void
    {
        $user=$request->user();
        if ($request->filled('invoice_id')) {
            $invoice=Invoice::whereKey($request->integer('invoice_id'))->where('estimate_id',$estimate->estimate_id)->where('customer_id',$estimate->customer_id)->firstOrFail();
            abort_unless($user->hasMatrixPermission('edit_invoices'),403);
            $this->authorize('update',$invoice);
        } elseif ($request->filled('customer_id')) {
            $customer=Customer::findOrFail($request->integer('customer_id'));
            abort_unless((int) $customer->id === (int) $estimate->customer_id,404);
            abort_unless($user->hasMatrixPermission('edit_customers'),403);
            $this->authorize('update',$customer);
        } else {
            abort_unless($user->hasMatrixPermission('edit_estimates'),403);
            $this->authorize('update',$estimate);
        }
    }
    public function show(Request $request, Estimate $estimate)
    {
        $this->check($request,$estimate);
        $ratio=(int) ($estimate->equipment_serials['panels_per_kw'] ?? 2);
        return response()->json(['quantity'=>(float) $estimate->quantity,'panels_per_kw'=>$ratio,'count'=>(int) ceil($estimate->quantity*$ratio),'equipment'=>$estimate->equipment_serials,'progress'=>$estimate->equipment_progress]);
    }
    public function update(Request $request, Estimate $estimate)
    {
        $this->check($request,$estimate);
        $ratio=$request->input('equipment_serials.panels_per_kw',2);
        $count=max(0,(int) ceil($estimate->quantity*(in_array($ratio,[1,'1'],true)?1:2)));
        $validator=\Illuminate\Support\Facades\Validator::make($request->all(),[
            'equipment_serials'=>['required','array:panels,inverters,notes,panels_per_kw'],
            'equipment_serials.panels_per_kw'=>['sometimes','required','integer','in:1,2'],
            'equipment_serials.panels'=>['nullable','array','max:'.$count],
            'equipment_serials.inverters'=>['nullable','array'],
            'equipment_serials.panels.*'=>['nullable','string','max:100'],
            'equipment_serials.inverters.*'=>['nullable','string','max:100'],
            'equipment_serials.notes'=>['nullable','string','max:4000'],
        ]);
        $validator->after(function ($validator) use ($request) {
            foreach (['panels','inverters'] as $type) {
                $values=$request->input('equipment_serials.'.$type,[]);
                if (!is_array($values)) continue;
                $values=array_filter(array_map(fn ($value)=>is_string($value)?trim($value):'',$values),fn ($value)=>$value!=='');
                if (count($values)!==count(array_unique($values))) $validator->errors()->add('equipment_serials.'.$type,'Each serial number must be unique.');
            }
        });
        $data=$validator->validate()['equipment_serials'];
        foreach (['panels','inverters'] as $type) $data[$type]=array_values(array_map(fn ($value)=>trim((string) $value),$data[$type] ?? []));
        $estimate->update(['equipment_serials'=>$data]);
        return response()->json(['success'=>true,'progress'=>$estimate->fresh()->equipment_progress,'message'=>'Equipment details saved.']);
    }
}
