<?php
namespace App\Http\Controllers;
use App\Models\Customer;
use App\Models\DashboardDocument;
use App\Services\SubsidyBondDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CustomerFormDocumentController extends Controller
{
    private function find(Customer $customer,int $document) {
        $this->authorize('view',$customer);
        return DashboardDocument::where('customer_id',$customer->id)->findOrFail($document);
    }
    public function show(Request $request,Customer $customer,int $document) {
        $record=$this->find($customer,$document);
        $versions=$record->versions()->with('creator:id,name')->get();
        $selected=$request->filled('version') ? $record->versions()->where('version',$request->integer('version'))->firstOrFail() : $versions->first();
        abort_unless($selected,404);
        return view('masters.customers.document-history',compact('customer','record','versions','selected'));
    }
    public function download(Request $request,Customer $customer,int $document,int $version) {
        $record=$this->find($customer,$document);
        $snapshot=$record->versions()->where('version',$version)->firstOrFail();
        if ($request->query('format')==='aadhaar') {
            abort_unless($snapshot->aadhaar_path && Storage::disk('local')->exists($snapshot->aadhaar_path),404);
            return Storage::disk('local')->download($snapshot->aadhaar_path,'consumer-aadhaar.'.pathinfo($snapshot->aadhaar_path,PATHINFO_EXTENSION),['Cache-Control'=>'private, no-store']);
        }
        $request->validate(['format'=>['required','in:pdf,word']]);
        if ($record->type==='solar') return app(\App\Services\SolarDocument::class)->download($snapshot->fields,$snapshot->aadhaar_path,mb_substr($snapshot->filename,0,165).' - v'.$snapshot->version,$request->query('format'));
        $data=$snapshot->fields;
        $data['filename']=mb_substr($snapshot->filename,0,165).' - v'.$snapshot->version;
        $data['format']=$request->query('format');
        return app(SubsidyBondController::class)->export(new Request($data),app(SubsidyBondDocument::class));
    }
}
