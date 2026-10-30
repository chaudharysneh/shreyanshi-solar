<?php
namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\DashboardDocument;
use App\Models\Stage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class DashboardDocumentController extends Controller
{
    private function records(Request $request) {
        return DashboardDocument::query()->where('user_id',$request->user()->id);
    }
    public function customers(Request $request) {
        $this->authorize('viewAny',Customer::class);
        $data=$request->validate(['q'=>['nullable','string','max:200']]);
        return Customer::visibleToUser($request->user())->when($data['q'] ?? '', fn ($query,$q)=>$query->where(fn ($query)=>$query->where('name','like','%'.$q.'%')->orWhere('phone','like','%'.$q.'%')))->orderBy('name')->limit(30)->get(['id','name','phone','address']);
    }
    public function createCustomer(Request $request) {
        $this->authorize('create',Customer::class);
        abort_unless($request->user()->hasMatrixPermission('create_customers'),403);
        $data=$request->validate(['name'=>['required','string','max:200'],'phone'=>['required','regex:/^[0-9]{10}$/',Rule::unique('customers','phone')->whereNull('deleted_at')]]);
        $data['is_active']=true;
        $data['pipeline_stage_id']=Stage::orderBy('sort_order')->value('id');
        $customer=Customer::create($data);
        if ($customer->pipeline_stage_id) app(\App\Services\WorkflowService::class)->syncWorkflowToPipelineStage($customer->id,$customer->pipeline_stage_id);
        return response()->json($customer->only(['id','name','phone','address']),201);
    }
    public function index(Request $request) {
        $data=$request->validate(['q'=>['nullable','string','max:200'],'type'=>['nullable','in:solar,subsidy']]);
        $documents=$this->records($request)->select(['id','customer_id','type','filename','updated_at'])->with('customer:id,name,phone')
            ->when($data['type'] ?? null,fn ($query,$type)=>$query->where('type',$type))
            ->when($data['q'] ?? null,fn ($query,$q)=>$query->where(fn ($query)=>$query->where('filename','like','%'.$q.'%')->orWhereHas('customer',fn ($query)=>$query->where('name','like','%'.$q.'%')->orWhere('phone','like','%'.$q.'%'))))
            ->latest('updated_at')->paginate(10);
        return response()->json($documents);
    }
    public function show(Request $request, int $id) {
        $document=$this->records($request)->with('customer:id,name,phone,address')->findOrFail($id);
        $this->authorize('view',$document->customer);
        return response()->json($document->toArray()+['has_aadhaar'=>(bool) ($document->aadhaar_path && Storage::disk('local')->exists($document->aadhaar_path))]);
    }
    public function save(Request $request) {
        $data=$request->validate([
            'document_id'=>['nullable','integer'], 'customer_id'=>['required','integer'],
            'type'=>['required','in:solar,subsidy'], 'filename'=>['required','string','max:180'],
            'fields'=>['required','array'], 'fields.consumer_name'=>['required','string','max:200'],
            'fields.agreement_date'=>['required_if:type,subsidy','nullable','date_format:Y-m-d'],
            'fields.consumer_number'=>['required_if:type,subsidy','nullable','string','max:100'],
            'fields.address'=>['required_if:type,subsidy','nullable','string','max:2000'],
            'fields.*'=>['nullable','string','max:4000'],
            'consumer_aadhaar_image_data'=>['nullable','string','max:6990600'],
            'consumer_aadhaar_image'=>['nullable','image','mimes:jpg,jpeg,png,webp','max:5120'],
        ]);
        $customer=Customer::visibleToUser($request->user())->findOrFail($data['customer_id']);
        $this->authorize('view',$customer);
        $newPath=null;
        try {
            // JSON uploads avoid PHP multipart temporary-directory notices corrupting the response.
            if ($request->filled('consumer_aadhaar_image_data')) {
                $encoded=$data['consumer_aadhaar_image_data'];
                if (!preg_match('~^data:image/(?:jpeg|png|webp);base64,([A-Za-z0-9+/=]+)$~D',$encoded,$matches)) {
                    throw \Illuminate\Validation\ValidationException::withMessages(['consumer_aadhaar_image'=>'Choose a valid JPG, PNG or WebP image.']);
                }
                $bytes=base64_decode($matches[1],true);
                $image=$bytes!==false ? @getimagesizefromstring($bytes) : false;
                $extensions=['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'];
                if (!$image || !isset($extensions[$image['mime']]) || strlen($bytes)>5*1024*1024) {
                    throw \Illuminate\Validation\ValidationException::withMessages(['consumer_aadhaar_image'=>'Choose a valid image up to 5 MB.']);
                }
                $newPath='dashboard-documents/'.$request->user()->id.'/'.\Illuminate\Support\Str::random(40).'.'.$extensions[$image['mime']];
                if (!Storage::disk('local')->put($newPath,$bytes)) {
                    throw \Illuminate\Validation\ValidationException::withMessages(['consumer_aadhaar_image'=>'Unable to save the Aadhaar image. Please retry.']);
                }
            } elseif ($request->hasFile('consumer_aadhaar_image')) {
                $newPath=$request->file('consumer_aadhaar_image')->store('dashboard-documents/'.$request->user()->id,'local');
                if (!$newPath || !Storage::disk('local')->exists($newPath)) throw \Illuminate\Validation\ValidationException::withMessages(['consumer_aadhaar_image'=>'The Aadhaar image could not be saved. Please select the image and try again.']);
            }
            $document=DB::transaction(function () use ($request,$data,$customer,$newPath) {
                $document=empty($data['document_id']) ? new DashboardDocument(['user_id'=>$request->user()->id]) : $this->records($request)->lockForUpdate()->findOrFail($data['document_id']);
                if ($document->exists && ((int) $document->customer_id !== (int) $customer->id || $document->type !== $data['type'])) {
                    throw \Illuminate\Validation\ValidationException::withMessages(['customer_id'=>'Start a new document to change its customer or document type.']);
                }
                $document->fill(['customer_id'=>$customer->id,'type'=>$data['type'],'filename'=>$data['filename'],'fields'=>$data['fields']]);
                if ($newPath) $document->aadhaar_path=$newPath;
                $changed=!$document->exists || $document->isDirty();
                $document->save();
                if ($changed || !$document->versions()->exists()) {
                    $document->versions()->create([
                        'version'=>((int) $document->versions()->max('version'))+1,
                        'user_id'=>$request->user()->id,'filename'=>$document->filename,
                        'fields'=>$document->fields,'aadhaar_path'=>$document->aadhaar_path,
                    ]);
                }
                return $document;
            });
        } catch (\Throwable $error) {
            if ($newPath) Storage::disk('local')->delete($newPath);
            throw $error;
        }
        $document->refresh();
        return response()->json(['id'=>$document->id,'has_aadhaar'=>(bool) ($document->aadhaar_path && Storage::disk('local')->exists($document->aadhaar_path)),'message'=>'Document saved.']);
    }
    public function aadhaar(Request $request,int $id) {
        $document=$this->records($request)->findOrFail($id);
        $this->authorize('view',Customer::findOrFail($document->customer_id));
        abort_unless($document->aadhaar_path && Storage::disk('local')->exists($document->aadhaar_path),404);
        if ($request->boolean('preview')) return Storage::disk('local')->response($document->aadhaar_path,null,['Cache-Control'=>'private, no-store','X-Content-Type-Options'=>'nosniff']);
        return Storage::disk('local')->download($document->aadhaar_path,'consumer-aadhaar.'.pathinfo($document->aadhaar_path,PATHINFO_EXTENSION),['Cache-Control'=>'private, no-store']);
    }
}
