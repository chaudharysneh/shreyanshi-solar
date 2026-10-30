<?php
namespace App\Http\Controllers;

use App\Services\SubsidyBondDocument;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class SubsidyBondController extends Controller
{
    public function export(Request $request, SubsidyBondDocument $document)
    {
        $data=$request->validate([
            'agreement_date'=>['required','date_format:Y-m-d'],
            'consumer_name'=>['required','string','max:200'],
            'consumer_number'=>['required','string','max:100'],
            'address'=>['required','string','max:2000'],
            'filename'=>['required','string','max:180'],
            'format'=>['required','in:word,pdf'],
        ]);
        $pages=$document->pages($data);
        $filename=trim(preg_replace('/[\\\\\/:*?"<>|\x00-\x1F]/u','-', $data['filename']),'. ');
        $filename=preg_replace('/\.(docx|pdf)$/i','',$filename) ?: 'Subsidy Bond';
        $word=$data['format']==='word';
        $content=$word ? $document->word($pages) : Pdf::loadView('documents.subsidy-bond',compact('pages'))->setPaper('a4')->output();
        return response($content)->withHeaders([
            'Content-Type'=>$word ? 'application/vnd.openxmlformats-officedocument.wordprocessingml.document' : 'application/pdf',
            'Content-Disposition'=>\Symfony\Component\HttpFoundation\HeaderUtils::makeDisposition('attachment',$filename.($word?'.docx':'.pdf'),'subsidy-bond'.($word?'.docx':'.pdf')),
            'Cache-Control'=>'private, no-store',
            'X-Content-Type-Options'=>'nosniff',
        ]);
    }
}
