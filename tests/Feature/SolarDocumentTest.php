<?php
namespace Tests\Feature;

use App\Services\SolarDocument;
use App\Services\HtmlWordDocument;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SolarDocumentTest extends TestCase
{
    private function fields(): array {
        return [
            'consumer_name'=>'Test Solar Consumer','consumer_number'=>'00012345','address'=>'TEST-ADDRESS-42',
            'billing_unit'=>'BU-987','city'=>'TEST-CITY','sub-_division'=>'TEST-SUBDIVISION','sanction_no'=>'SANCTION-0007',
            'sanctioned_capacity'=>'7.5','capacity_solar'=>'6.5','make_of_solar'=>'TEST-PANEL-MAKE','wattage_per_module'=>'545',
            'no_of_p_v_module'=>'12','total_capacity'=>'6.54','warranty'=>'TEST-WARRANTY-25','make_of_inverter'=>'TEST-INVERTER-MAKE',
            'capacity_inverter'=>'6','year_of_manufacturing'=>'2025','aadhaar_number'=>'123412341234','mobile_number'=>'9876543210',
            'installation_date'=>'2026-10-30','state'=>'TEST-STATE','panel_serial_numbers'=>"PANEL-0001\nPANEL-0002",
            'inverter_serial_number'=>'INVERTER-0001','date_day'=>'30','date_month_short'=>'Oct','year'=>'2026',
            'sub-_division_address'=>'TEST-SUBDIVISION-ADDRESS',
        ];
    }
    public function test_every_dynamic_field_is_used_and_static_wording_is_preserved(): void {
        $sections=(new SolarDocument)->sections($this->fields());
        $this->assertCount(6,$sections);
        $html=implode('',$sections);
        foreach ($this->fields() as $key=>$value) {
            if ($key==='installation_date') $value='30/10/2026';
            $this->assertStringContainsString($value,$html,$key);
        }
        foreach (['SHREYANSHI ENTERPRISES','YOGESH JADHAV','Net Metering Arrangement','Private Sector','3 Nos, &lt;5 Ohm','Grid Interactive Renewable Regulations'] as $static) $this->assertStringContainsString($static,$html);
        $this->assertStringContainsString('Redressal Forum constituted',$sections[5]);
        $this->assertStringContainsString('Witness 2:',$sections[5]);
        $this->assertStringContainsString('Test Solar Consumer',$sections[5]);
        $this->assertStringNotContainsString('Domestic Content Requirement',$sections[5]);
        foreach (['Dolorem','Temp oribus','Autem eos laborum','SAMARTH SHARMA'] as $sample) $this->assertStringNotContainsString($sample,$html);
        $changed=$this->fields(); $changed['consumer_name']='Different Consumer'; $changed['capacity_solar']='9.9';
        $next=implode('',(new SolarDocument)->sections($changed));
        $this->assertStringNotContainsString('Test Solar Consumer',$next);
        $this->assertStringContainsString('Different Consumer',$next);
        $this->assertStringContainsString('9.9',$next);
    }
    public function test_missing_fields_do_not_get_sample_data_and_html_is_escaped(): void {
        $sections=(new SolarDocument)->sections(['consumer_name'=>'<script>alert(1)</script>']);
        $html=implode('',$sections);
        $this->assertStringNotContainsString('<script>',$html);
        $this->assertStringContainsString('&lt;script&gt;',$html);
        $this->assertStringNotContainsString('8 kW',$html);
        $this->assertStringNotContainsString('<img',$html);
    }
    public function test_pdf_and_editable_word_embed_the_saved_private_aadhaar_image(): void {
        Storage::fake('local');
        $path=UploadedFile::fake()->image('consumer.png',400,250)->store('solar-test','local');
        $sections=(new SolarDocument)->sections($this->fields(),$path);
        $this->assertStringContainsString('data:image/png;base64,',$sections[1]);
        $pdf=Pdf::loadView('documents.solar-document',compact('sections'))->setPaper('a4');
        $this->assertStringStartsWith('%PDF-',$pdf->output());
        $this->assertGreaterThanOrEqual(6,$pdf->getDomPDF()->getCanvas()->get_page_count());
        $this->assertLessThanOrEqual(12,$pdf->getDomPDF()->getCanvas()->get_page_count());
        $word=(new HtmlWordDocument)->render($sections);
        $temp=tempnam(sys_get_temp_dir(),'solar-test-');
        try {
            file_put_contents($temp,$word); $zip=new \ZipArchive;
            $this->assertTrue($zip->open($temp));
            foreach (['[Content_Types].xml','_rels/.rels','word/document.xml','word/_rels/document.xml.rels'] as $part) {
                $dom=new \DOMDocument; $this->assertTrue($dom->loadXML($zip->getFromName($part)));
            }
            $xml=$zip->getFromName('word/document.xml');
            $this->assertStringContainsString('Test Solar Consumer',$xml);
            $this->assertStringContainsString('PANEL-0001',$xml);
            $this->assertStringContainsString('TEST-SUBDIVISION-ADDRESS',$xml);
            $this->assertStringContainsString('<w:tbl>',$xml);
            $this->assertStringContainsString('w:sz w:val="17"',$xml);
            $this->assertStringContainsString('w:line="190" w:lineRule="exact"',$xml);
            $this->assertStringContainsString('w:lineRule="atLeast"',$xml);
            $this->assertStringContainsString('w:tblLayout w:type="fixed"',$xml);
            $this->assertStringContainsString('w:jc w:val="right"',$xml);
            $this->assertStringContainsString('r:embed="image1"',$xml);
            $this->assertSame(Storage::disk('local')->get($path),$zip->getFromName('word/media/image1.png'));
            $this->assertStringContainsString('Target="media/image1.png"',$zip->getFromName('word/_rels/document.xml.rels'));
            $zip->close();
        } finally { unlink($temp); }
    }
    public function test_completion_report_signatures_stay_on_the_first_page(): void {
        $sections=[(new SolarDocument)->sections($this->fields())[0]];
        $pdf=Pdf::loadView('documents.solar-document',compact('sections'))->setPaper('a4');
        $pdf->output();
        $this->assertSame(1,$pdf->getDomPDF()->getCanvas()->get_page_count());
    }
    public function test_completion_report_converts_capacity_to_wp_and_uses_correct_charge_controller(): void {
        $fields = $this->fields();
        $fields['total_capacity'] = '4.95';
        $sections = (new SolarDocument)->sections($fields);
        $this->assertStringContainsString('Total Capacity (KWP)', $sections[0]);
        $this->assertStringContainsString('4950 Wp', $sections[0]);
        $this->assertStringContainsString('0.1 kW', $sections[0]);
        $this->assertStringNotContainsString('0.3 kW', $sections[0]);
        $this->assertStringNotContainsString('4.95 kWp', $sections[0]);
        $this->assertStringContainsString('4.95 kW', implode('', array_slice($sections, 1)));
    }
}
