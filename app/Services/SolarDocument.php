<?php
namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\HeaderUtils;

class SolarDocument
{
    public function sections(array $fields, ?string $aadhaarPath = null): array
    {
        // Accept original form keys as well as conventional names used by saved integrations.
        $fields['no_of_pv_module'] ??= $fields['no_of_p_v_module'] ?? '';
        $fields['sub_division'] ??= $fields['sub-_division'] ?? '';
        $fields['sub_division_address'] ??= $fields['sub-_division_address'] ?? '';
        $value=fn ($key)=>isset($fields[$key]) && trim((string) $fields[$key])!=='' ? (string) $fields[$key] : '-';
        $aadhaarImage=null;
        $aadhaarWidth=0; $aadhaarHeight=0;
        if ($aadhaarPath && Storage::disk('local')->exists($aadhaarPath)) {
            $bytes=Storage::disk('local')->get($aadhaarPath);
            $image=@getimagesizefromstring($bytes);
            if ($image && in_array($image['mime'],['image/jpeg','image/png','image/webp'],true)) {
                if ($image['mime']==='image/webp') {
                    $resource=imagecreatefromstring($bytes);
                    ob_start(); imagepng($resource); $bytes=ob_get_clean(); imagedestroy($resource);
                    $image['mime']='image/png';
                }
                $scale=min(495/$image[0],310/$image[1]);
                $aadhaarWidth=round($image[0]*$scale,2); $aadhaarHeight=round($image[1]*$scale,2);
                $aadhaarImage='data:'.$image['mime'].';base64,'.base64_encode($bytes);
            }
        }
        $date=$value('installation_date');
        if ($date!=='-') {
            try { $date=\Carbon\Carbon::createFromFormat('Y-m-d',$date)->format('d/m/Y'); } catch (\Throwable $error) { /* Preserve older saved date text. */ }
        }
        $data=compact('value','date','aadhaarImage','aadhaarWidth','aadhaarHeight');
        $sections=[];
        // The agreement closes with its redressal continuation and witness/signature page.
        foreach (['completion','guarantee','commissioning','declaration','net-metering','agreement-ending'] as $name) {
            $sections[]=view('documents.solar.'.$name,$data)->render();
        }
        return $sections;
    }

    public function download(array $fields, ?string $aadhaarPath, string $filename, string $format)
    {
        $sections=$this->sections($fields,$aadhaarPath);
        $word=$format==='word';
        $content=$word ? app(HtmlWordDocument::class)->render($sections) : Pdf::loadView('documents.solar-document',compact('sections'))->setPaper('a4')->output();
        $filename=preg_replace('~[\\\\/:*?"<>|\x00-\x1F]~u','-',trim($filename));
        $filename=preg_replace('/\.(pdf|docx)$/i','',trim($filename,'. ')) ?: 'Solar Documents';
        $extension=$word?'.docx':'.pdf';
        return response($content)->withHeaders([
            'Content-Type'=>$word?'application/vnd.openxmlformats-officedocument.wordprocessingml.document':'application/pdf',
            'Content-Disposition'=>HeaderUtils::makeDisposition('attachment',$filename.$extension,'solar-documents'.$extension),
            'Cache-Control'=>'private, no-store','X-Content-Type-Options'=>'nosniff',
        ]);
    }
}
