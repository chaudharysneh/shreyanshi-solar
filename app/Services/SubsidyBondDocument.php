<?php
namespace App\Services;

class SubsidyBondDocument
{
    public function pages(array $data): array
    {
        $name=e($data['consumer_name']); $number=e($data['consumer_number']); $address=nl2br(e($data['address']));
        $date=\Carbon\Carbon::parse($data['agreement_date'])->format('d/m/Y');
        $vendor='SHREYANSHI ENTERPRISES';
        $vendorAddress='SHOP NO 13 R.N. GOU ARCADE OPP. KOTHE SCHOOL, 70 FEET ROAD SOLAPUR 413006';
        return [
            '<p class="center"><b>Annexure 2</b></p><p class="center"><b>Model Draft Agreement between Consumer &amp; Vendor for installation of grid connected rooftop solar (RTS) project under PM – Surya Ghar: Muft Bijli Yojana</b></p><p>This agreement is executed on <b>'.$date.'</b> for design, supply, installation, commissioning and 5-year comprehensive maintenance of RTS project/system along with warranty under PM Surya Ghar: Muft Bijli Yojana</p>',
            '<p class="center"><b>Between</b></p><p><b>'.$name.'</b> having residential electricity connection with consumer number <b>'.$number.'</b> from MSEDCL at <b>'.$address.'</b>.</p><p><b>'.$vendor.'</b> is registered / empanelled with the MSEDCL and is registered/functional office at <b>'.$vendorAddress.'</b>.</p><p><b>Whereas</b></p><p>First Party wishes to install a Grid Connected Rooftop Solar Plant on the rooftop of the residential building of the Consumer under PM Surya Ghar: Muft Bijli Yojana.</p><p><b>And whereas</b></p><p>Second Party has verified availability of appropriate roof and found it feasible to install a Grid Connected Roof Top Solar plant and that the second party is willing to design, supply, install, test, commission and carry out Operation &amp; Maintenance of the Rooftop Solar plant for 5 year period.</p><p>On this day, the First Party and Second Party agree to the following:</p><h3>The First Party hereby undertakes to perform the following activities:</h3><ol><li>Submission of online application at National Portal for installation of RTS project/system, Submission of application for net-metering and system inspection and upload of the relevant documents on the National Portal of the scheme.</li><li>Provide secure storage of the material of the RTS plant delivered at the premises till handed over the system.</li><li>Provide access to the Roof Top during installation of the plant, operation &amp; maintenance, testing of the plant and equipment and for meter reading from solar meter, inverter etc.</li><li>Provide electricity during plant installation and water for cleaning of the panels.</li><li>Report any malfunctioning of the plant to the Vendor during the warranty period.</li></ol>',
            '<ol start="6"><li>Pay the amount as per the payment schedule as mutually agreed with the vendor, including any additional amount to the second party for any additional work /Customization required depending upon the building condition</li></ol><h3>The Second Party hereby undertakes to perform the following activities:</h3><ol><li>The Vendor must follow all the standards and safety guidelines prescribed under state regulations and technical standards prescribed by MNRE for RTS projects, failing which the vendor is liable for blacklisting from participation in the govt. project/ scheme and other penal actions in accordance with the law. The responsibility of supply, installation and commissioning of the rooftop solar project/system in complete compliance with MNRE scheme guidelines lies with the Vendor.</li><li><b>Site Survey:</b> Site visit, survey and development of detailed project report for installation of RTS system. This also includes, feasibility study of roof, strength of roof and shadow free area. If any additional work or customization is involved for the plant installation as per site condition and requirement of the consumer building, the Vendor shall prepare an estimate and can raise separate invoice including GST in addition to the amount towards standard plant cost. The consumer shall pay the amount for such additional work directly to the Vendor.</li><li><b>Design &amp; Engineering:</b> Design of plant along with drawings and selection of components as per standard provided by the DISCOM/SERC/MNRE for best performance and safety of the plant.</li><li><b>Module and Inverter:</b> The solar modules, including the solar cells, should be manufactured in India. Both the solar modules and inverters shall conform to the relevant standards and specifications prescribed by MNRE. Any other requirement, viz. star labelling (solar modules), quality control orders and standards &amp; labelling (inverters) etc., shall also be complied.</li><li><b>Procurement &amp; Supply:</b> Procurement of complete system as per BIS/IS/IEC standard (whatever applicable) &amp; safety guidelines for installation of rooftop solar plants. The supplied materials should comply with all MNRE standards for release of subsidy.</li><li><b>Installation &amp; Civil work:</b> Complete civil work, structure work and electrical work (including drawings) following all the safety and relevant BIS standards.</li></ol>',
            '<ol start="7"><li><b>Documentation (Technical Catalogues/Warranty Certificates/BIS certificates/other test reports etc):</b> All such documents shall be provided to the consumer for online uploading and submission of technical specifications, IEC/BIS report, Sr. Nos, Warranty card of Solar Panel &amp; Inverter, Layout &amp; Electrical SLD, Structure Design and Drawing, Cable and other detailed documents.</li><li><b>Project completion report (PCR):</b> Assisting the consumer in filling and uploading of signed documents (Consumer &amp; Vendor) on the national portal.</li><li><b>Warranty:</b> System warranty certificates should be provided to the consumer. The complete system should be warranted for 5 years from the date of commissioning by DISCOM. Individual component warranty documents provided by the manufacturer shall be provided to the consumer and all possible assistance should be extended to the consumer for claiming the warranty from the manufacturer.</li><li><b>NET meter &amp; Grid Connectivity:</b> Net meter supply/procurement, testing and approvals shall be in the scope of vendor. Grid connection of the plant shall be in the scope of the vendor.</li><li><b>Testing and Commissioning:</b> The vendor shall be present at the time of testing and commissioning by the DISCOM.</li><li><b>Operation &amp; Maintenance:</b> Five (5) years Comprehensive Operation and Maintenance including overhauling, wear and tear and regular checking of healthiness of system at proper interval shall be in the scope of vendor. The vendor shall also educate the consumer on best practices for cleaning of the modules and system maintenance.</li><li><b>Insurance:</b> Any insurance cost pertaining to material transfer/storage before commissioning of the system shall be in the scope of the vendor.</li><li><b>Applicable Standard:</b> The system must meet the technical standards and specifications notified by MNRE. The vendor is solely responsible to supply component and service which meets the technical standards and specification prescribed by MNRE and State DISCOMs.</li><li><b>Project/system cost &amp; payment terms:</b> The cost of the plant and payment schedule should be mutually discussed and decided between the vendor and consumer. The consumer may opt for milestone-based payment to the vendor and the same shall be included in the agreement.</li><li><b>Dispute:</b> In-case of any dispute between consumer and vendor (in</li></ol>',
            '<p style="margin-left:23pt">supply/installation/maintenance of system or payment terms), both parties must settle the same mutually or as per law. MNRE/DISCOM shall not be liable for, and would not be a party to any dispute arising between vendor and consumer.</p><ol start="17"><li><b>Subsidy / Project Related Documents:</b> Vendor must provide all the documents to consumer and help in uploading the same to National Portal for smooth release of subsidy.</li><li><b>Performance of Plant:</b> The Performance Ratio (PR) of Plant must be 75% at the time of commissioning of the project by DISCOM or its authorized agency. Vendor must provide (returnable basis) radiation sensor with valid calibration certificate of any NABL / International laboratory at the time of commissioning / testing of the plant. Vendor must maintain the PR of the plant till warranty of project i.e. 5 years from the date of commissioning.</li><li><b>Mutually Agreed Terms of Payment ... Loan Payment.</b></li></ol><table><tr><td class="center"><b>First Party</b></td><td class="center"><b>Second Party</b></td></tr><tr><td>Name: <b>'.$name.'</b></td><td>Name: <b>Shreyanshi Enterprises</b></td></tr><tr><td>Address: <b>'.$address.'</b></td><td>Address: <b>'.$vendorAddress.'</b></td></tr><tr><td><br><b>Sign: -</b></td><td><br><b>Sign: -</b><br><b>Date: '.$date.'</b></td></tr></table><p class="disclaimer">Disclaimer: This agreement is between vendor and consumer and any dispute related to the same shall not involve any third party including MNRE and Distribution Utilities.</p>',
        ];
    }

    public function word(array $pages): string
    {
        $escape=fn ($text)=>htmlspecialchars($text, ENT_XML1 | ENT_QUOTES, 'UTF-8');
        $paragraph=function ($text, $properties='', $prefix='', $size=20) use ($escape) {
            $runs='';
            $run=function ($value, $bold=false) use ($escape,$size) {
                return '<w:r><w:rPr><w:rFonts w:ascii="Arial" w:hAnsi="Arial"/><w:sz w:val="'.$size.'"/>'.($bold?'<w:b/>':'').'</w:rPr><w:t xml:space="preserve">'.$escape($value).'</w:t></w:r>';
            };
            if ($prefix!=='') $runs.=$run($prefix);
            if ($text instanceof \DOMNode) {
                $visit=function ($node, $bold=false) use (&$visit, &$runs, $run) {
                    if ($node->nodeType===XML_TEXT_NODE) $runs.=$run($node->nodeValue,$bold);
                    elseif ($node->nodeName==='br') $runs.='<w:r><w:br/></w:r>';
                    else foreach ($node->childNodes as $child) $visit($child,$bold || in_array($node->nodeName,['b','strong','h3']));
                };
                $visit($text);
            } else $runs.=$run($text);
            $before=$text instanceof \DOMElement && $text->getAttribute('class')==='disclaimer'?'900':'0';
            $properties=str_replace('<w:spacing w:before="9000"/>','',$properties);
            return '<w:p><w:pPr>'.$properties.'<w:spacing w:before="'.$before.'" w:after="260" w:line="320" w:lineRule="exact"/></w:pPr>'.$runs.'</w:p>';
        };
        $body='';
        foreach ($pages as $index=>$html) {

            $dom=new \DOMDocument();
            @$dom->loadHTML('<?xml encoding="UTF-8"><html><body>'.$html.'</body></html>');
            $walk=function ($node) use (&$walk, &$body, $paragraph, $index) {
                if (in_array($node->nodeName,['p','h3','li'])) {
                    $prefix='';
                    if ($node->nodeName==='li') {
                        $number=(int) ($node->parentNode->getAttribute('start') ?: 1);
                        for ($previous=$node->previousSibling;$previous;$previous=$previous->previousSibling) if ($previous->nodeName==='li') $number++;
                        $prefix=$number.'. ';
                    }
                    $properties=$node->getAttribute('class')==='center'?'<w:jc w:val="center"/>':'<w:jc w:val="both"/>';
                    if ($node->nodeName==='h3') $properties.='<w:keepNext/>';
                    if ($node->nodeName==='li') $properties.='<w:ind w:left="460" w:hanging="240"/>';
                    $body.=$paragraph($node,$properties,$prefix);
                } elseif ($node->nodeName==='table') {
                    $body.='<w:tbl><w:tblPr><w:tblW w:w="0" w:type="auto"/></w:tblPr><w:tblGrid><w:gridCol w:w="4500"/><w:gridCol w:w="4500"/></w:tblGrid>';
                    foreach ($node->getElementsByTagName('tr') as $row) {
                        $body.='<w:tr>';
                        foreach ($row->getElementsByTagName('td') as $cell) $body.='<w:tc><w:tcPr><w:tcW w:w="4500" w:type="dxa"/></w:tcPr>'.$paragraph($cell,'','',18).'</w:tc>';
                        $body.='</w:tr>';
                    }
                    $body.='</w:tbl>';
                } else foreach ($node->childNodes as $child) $walk($child);
            };
            $walk($dom->getElementsByTagName('body')->item(0));
        }
        $xml='<?xml version="1.0" encoding="UTF-8" standalone="yes"?>';
        $path=tempnam(sys_get_temp_dir(),'bond-');
        try {
            $zip=new \ZipArchive();
            if ($zip->open($path,\ZipArchive::OVERWRITE)!==true) throw new \RuntimeException('Unable to create Word document.');
            $zip->addFromString('[Content_Types].xml',$xml.'<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/><Override PartName="/word/footer1.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.footer+xml"/></Types>');
            $zip->addFromString('_rels/.rels',$xml.'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/></Relationships>');
            $zip->addFromString('word/_rels/document.xml.rels',$xml.'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="footer" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/footer" Target="footer1.xml"/></Relationships>');
            $zip->addFromString('word/footer1.xml',$xml.'<w:ftr xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">'.$paragraph('Guidelines for PM-Surya Ghar: Muft Bijli Yojana', '<w:jc w:val="center"/>','',16).$paragraph('Central Financial Assistance to Residential Consumers','<w:jc w:val="center"/>','',16).'</w:ftr>');
            $zip->addFromString('word/document.xml',$xml.'<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><w:body>'.$body.'<w:sectPr><w:footerReference w:type="default" r:id="footer"/><w:pgSz w:w="11906" w:h="16838"/><w:pgMar w:top="1160" w:right="1100" w:bottom="1240" w:left="1100" w:footer="400"/></w:sectPr></w:body></w:document>');
            $zip->close();
            return file_get_contents($path);
        } finally { @unlink($path); }
    }
}
