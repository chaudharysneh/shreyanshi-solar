<?php
namespace App\Http\Controllers;
use App\Models\DashboardDocument;
use App\Models\Customer;
use App\Services\SolarDocument;
use Illuminate\Http\Request;

class SolarDocumentController extends Controller
{
    public function export(Request $request,SolarDocument $service) {
        $data=$request->validate(['document_id'=>['required','integer'],'filename'=>['required','string','max:180'],'format'=>['required','in:pdf,word']]);
        $document=DashboardDocument::where('user_id',$request->user()->id)->where('type','solar')->findOrFail($data['document_id']);
        $this->authorize('view',Customer::findOrFail($document->customer_id));
        return $service->download($document->fields,$document->aadhaar_path,$data['filename'],$data['format']);
    }
}
