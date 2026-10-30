<?php
namespace App\Services;

/** Converts the controlled solar template markup to a native, editable DOCX. */
class HtmlWordDocument
{
    private array $images=[];
    private bool $completion=false;
    private bool $ending=false;
    private bool $signature=false;
    private int $cellIndex=0;
    private function escape(string $text): string { return htmlspecialchars($text,ENT_XML1 | ENT_QUOTES,'UTF-8'); }
    private function runs(\DOMNode $node,bool $bold=false,int $size=20): string {
        if ($node->nodeType===XML_TEXT_NODE) return '<w:r><w:rPr><w:rFonts w:ascii="Times New Roman" w:hAnsi="Times New Roman"/><w:sz w:val="'.$size.'"/>'.($bold?'<w:b/>':'').'</w:rPr><w:t xml:space="preserve">'.$this->escape($node->nodeValue).'</w:t></w:r>';
        if ($node->nodeName==='br') return '<w:r><w:br/></w:r>';
        if ($node->nodeName==='img') return $this->image($node->getAttribute('src'),$node->getAttribute('style'));
        if ($node->nodeName==='u' || $node->nodeName==='em' || $node->nodeName==='i') {
            $runs=''; foreach ($node->childNodes as $child) $runs.=$this->runs($child,$bold,$size);
            return str_replace('</w:rPr>',($node->nodeName==='u'?'<w:u w:val="single"/>':'<w:i/>').'</w:rPr>',$runs);
        }
        $result='';
        foreach ($node->childNodes as $child) $result.=$this->runs($child,$bold || in_array($node->nodeName,['b','strong','th','h2','h3']),$size);
        return $result;
    }
    private function image(string $source,string $style=''): string {
        if (!preg_match('~^data:image/(png|jpeg);base64,([A-Za-z0-9+/=]+)$~',$source,$matches)) return '';
        $bytes=base64_decode($matches[2],true);
        $size=$bytes ? @getimagesizefromstring($bytes) : false;
        if (!$size) return '';
        $id=count($this->images)+1;
        $this->images[]=['id'=>'image'.$id,'path'=>'media/image'.$id.'.'.$matches[1],'bytes'=>$bytes];
        $scale=min(450/$size[0],235/$size[1]);
        $width=$size[0]*$scale; $height=$size[1]*$scale;
        if (preg_match('/width:([0-9.]+)pt/',$style,$m)) $width=(float) $m[1];
        if (preg_match('/height:([0-9.]+)pt/',$style,$m)) $height=(float) $m[1];
        $cx=(int) round($width*12700); $cy=(int) round($height*12700);
        return '<w:r><w:drawing><wp:inline distT="0" distB="0" distL="0" distR="0"><wp:extent cx="'.$cx.'" cy="'.$cy.'"/><wp:docPr id="'.$id.'" name="Consumer Aadhaar image"/><a:graphic><a:graphicData uri="http://schemas.openxmlformats.org/drawingml/2006/picture"><pic:pic><pic:nvPicPr><pic:cNvPr id="'.$id.'" name="Aadhaar"/><pic:cNvPicPr/></pic:nvPicPr><pic:blipFill><a:blip r:embed="image'.$id.'"/><a:stretch><a:fillRect/></a:stretch></pic:blipFill><pic:spPr><a:xfrm><a:off x="0" y="0"/><a:ext cx="'.$cx.'" cy="'.$cy.'"/></a:xfrm><a:prstGeom prst="rect"><a:avLst/></a:prstGeom></pic:spPr></pic:pic></a:graphicData></a:graphic></wp:inline></w:drawing></w:r>';
    }
    private function paragraph(\DOMNode $node,int $size=20,bool $cell=false): string {
        $heading=in_array($node->nodeName,['h2','h3']);
        $center=$node->nodeName==='h2' || ($node instanceof \DOMElement && in_array($node->getAttribute('class'),['center','aadhaar']));
        $size=$cell ? ($this->completion?17:18) : ($this->completion?18:$size);
        $alignment=$center?'center':($cell?'left':'both');
        if ($this->signature && !$this->ending && $this->cellIndex>0) $alignment='right';
        $line=$cell?($this->completion?190:234):($this->completion?216:270);
        $before=$node->nodeName==='h3'?320:0;
        if ($node instanceof \DOMElement && str_contains($node->getAttribute('class'),'spaced')) $before=600;
        if ($node instanceof \DOMElement && str_contains($node->getAttribute('class'),'aadhaar')) $before=560;
        $lineRule=$node instanceof \DOMElement && $node->getElementsByTagName('img')->length ? 'atLeast' : 'exact';
        $after=$cell?0:($heading?($this->completion?200:360):($this->completion?160:240));
        return '<w:p><w:pPr><w:jc w:val="'.$alignment.'"/>'.($heading?'<w:keepNext/>':'').'<w:spacing w:before="'.$before.'" w:after="'.$after.'" w:line="'.$line.'" w:lineRule="'.$lineRule.'"/></w:pPr>'.$this->runs($node,false,$node->nodeName==='h2'?22:$size).'</w:p>';

    }
    private function table(\DOMElement $table): string {
        $rows=$table->getElementsByTagName('tr'); $columns=1;
        foreach ($rows as $row) {
            $count=0;
            foreach ($row->childNodes as $cell) if (in_array($cell->nodeName,['td','th'])) $count+=max(1,(int) $cell->getAttribute('colspan'));
            $columns=max($columns,$count);
        }
        $widths=array_fill(0,$columns,(int) floor(9986/$columns));
        foreach ($rows as $row) {
            $cells=[];
            foreach ($row->childNodes as $cell) if (in_array($cell->nodeName,['td','th'])) $cells[]=$cell;
            if (count($cells)===$columns) {
                foreach ($cells as $index=>$cell) if (preg_match('/width:\s*(\d+)%/',$cell->getAttribute('style'),$matches)) $widths[$index]=(int) round(9986*(int) $matches[1]/100);
            }
        }
        $this->signature=str_contains($table->getAttribute('class'),'signatures');
        $border=$this->signature?'nil':'single';
        $padding=$this->ending && $this->signature?160:($this->completion?36:60);
        $xml='<w:tbl><w:tblPr><w:tblW w:w="9986" w:type="dxa"/><w:tblLayout w:type="fixed"/><w:tblBorders>';
        foreach (['top','left','bottom','right','insideH','insideV'] as $side) $xml.='<w:'.$side.' w:val="'.$border.'" w:sz="4" w:color="777777"/>';
        $xml.='</w:tblBorders><w:tblCellMar><w:top w:w="'.$padding.'" w:type="dxa"/><w:left w:w="80" w:type="dxa"/><w:bottom w:w="'.$padding.'" w:type="dxa"/><w:right w:w="80" w:type="dxa"/></w:tblCellMar></w:tblPr><w:tblGrid>';
        foreach ($widths as $width) $xml.='<w:gridCol w:w="'.$width.'"/>';
        $xml.='</w:tblGrid>';
        foreach ($rows as $row) {
            $xml.='<w:tr><w:trPr><w:cantSplit/>'.($row->parentNode->nodeName==='thead'?'<w:tblHeader/>':'').'</w:trPr>'; $index=0;
            foreach ($row->childNodes as $cell) {
                if (!in_array($cell->nodeName,['td','th'])) continue;
                $this->cellIndex=$index;
                $span=max(1,(int) $cell->getAttribute('colspan'));
                $width=array_sum(array_slice($widths,$index,$span)); $index+=$span;
                $xml.='<w:tc><w:tcPr><w:tcW w:w="'.$width.'" w:type="dxa"/>'.($span>1?'<w:gridSpan w:val="'.$span.'"/>':'').'</w:tcPr>'.$this->paragraph($cell,18,true).'</w:tc>';
            }
            $xml.='</w:tr>';
        }
        $gap=$this->completion?160:240;
        $this->signature=false;
        return $xml.'</w:tbl><w:p><w:pPr><w:spacing w:after="0" w:line="'.$gap.'" w:lineRule="exact"/></w:pPr></w:p>';
    }
    public function render(array $sections): string {
        $this->images=[]; $body='';
        foreach ($sections as $index=>$html) {
            $this->completion=$index===0; $this->ending=$index===count($sections)-1;
            if ($index) $body.='<w:p><w:pPr><w:spacing w:after="0" w:line="1" w:lineRule="exact"/></w:pPr><w:r><w:br w:type="page"/></w:r></w:p>';
            $dom=new \DOMDocument;
            @$dom->loadHTML('<?xml encoding="UTF-8"><html><body>'.$html.'</body></html>');
            foreach ($dom->getElementsByTagName('body')->item(0)->childNodes as $node) {
                if ($node->nodeType!==XML_ELEMENT_NODE) continue;
                if ($node->nodeName==='table' && str_contains($node->getAttribute('class'),'signatures')) {
                    $gap=$this->completion?320:($this->ending?240:680);
                    $body.='<w:p><w:pPr><w:spacing w:after="0" w:line="'.$gap.'" w:lineRule="exact"/></w:pPr></w:p>';
                }
                $body.=$node->nodeName==='table'?$this->table($node):$this->paragraph($node);
            }
        }
        $xml='<?xml version="1.0" encoding="UTF-8" standalone="yes"?>';
        $relationships='';
        foreach ($this->images as $image) $relationships.='<Relationship Id="'.$image['id'].'" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/image" Target="'.$image['path'].'"/>';
        $path=tempnam(sys_get_temp_dir(),'solar-word-');
        try {
            $zip=new \ZipArchive;
            if ($zip->open($path,\ZipArchive::OVERWRITE)!==true) throw new \RuntimeException('Unable to create Word document.');
            $zip->addFromString('[Content_Types].xml',$xml.'<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Default Extension="png" ContentType="image/png"/><Default Extension="jpeg" ContentType="image/jpeg"/><Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/></Types>');
            $zip->addFromString('_rels/.rels',$xml.'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/></Relationships>');
            $zip->addFromString('word/_rels/document.xml.rels',$xml.'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'.$relationships.'</Relationships>');
            foreach ($this->images as $image) $zip->addFromString('word/'.$image['path'],$image['bytes']);
            $zip->addFromString('word/document.xml',$xml.'<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships" xmlns:wp="http://schemas.openxmlformats.org/drawingml/2006/wordprocessingDrawing" xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main" xmlns:pic="http://schemas.openxmlformats.org/drawingml/2006/picture"><w:body>'.$body.'<w:sectPr><w:pgSz w:w="11906" w:h="16838"/><w:pgMar w:top="880" w:right="960" w:bottom="880" w:left="960"/></w:sectPr></w:body></w:document>');
            $zip->close(); return file_get_contents($path);
        } finally { @unlink($path); }
    }
}
