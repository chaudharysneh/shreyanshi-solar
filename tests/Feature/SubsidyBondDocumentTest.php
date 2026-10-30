<?php
namespace Tests\Feature;

use App\Http\Controllers\SubsidyBondController;
use App\Services\SubsidyBondDocument;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Tests\TestCase;

class SubsidyBondDocumentTest extends TestCase
{
    private function data(): array
    {
        return ['agreement_date'=>'2026-10-30','consumer_name'=>'Consumer & Test <script>','consumer_number'=>'0012345','address'=>"Street One\nSolapur",'filename'=>'Test Bond','format'=>'pdf'];
    }
    public function test_pdf_flows_without_forced_pages_and_consumer_details_are_escaped(): void
    {
        $pages=(new SubsidyBondDocument)->pages($this->data());
        $this->assertCount(5,$pages);
        $this->assertStringContainsString('Consumer &amp; Test &lt;script&gt;', $pages[1]);
        $this->assertStringContainsString('0012345',$pages[1]);
        $this->assertStringContainsString('30/10/2026',$pages[0]);
        $this->assertStringContainsString('30/10/2026',$pages[4]);
        $pdf=Pdf::loadView('documents.subsidy-bond',compact('pages'))->setPaper('a4');
        $this->assertStringStartsWith('%PDF-', $pdf->output());
        $this->assertLessThan(5,$pdf->getDomPDF()->getCanvas()->get_page_count());
        $this->assertGreaterThan(1,$pdf->getDomPDF()->getCanvas()->get_page_count());
    }
    public function test_word_is_valid_ooxml_with_page_breaks_footer_and_dynamic_values(): void
    {
        $service=new SubsidyBondDocument;
        $response=(new SubsidyBondController)->export(new Request(array_replace($this->data(),['format'=>'word'])),$service);
        $this->assertSame(200,$response->getStatusCode());
        $path=tempnam(sys_get_temp_dir(),'bond-test-');
        try {
            file_put_contents($path,$response->getContent());
            $zip=new \ZipArchive;
            $this->assertTrue($zip->open($path));
            foreach (['[Content_Types].xml','_rels/.rels','word/document.xml','word/footer1.xml','word/_rels/document.xml.rels'] as $part) {
                $xml=new \DOMDocument;
                $this->assertTrue($xml->loadXML($zip->getFromName($part)));
            }
            $document=$zip->getFromName('word/document.xml');
            $this->assertSame(0,substr_count($document,'w:type="page"'));
            $this->assertStringNotContainsString('w:before="9000"',$document);
            $this->assertStringContainsString('w:sz w:val="20"',$document);
            $this->assertStringContainsString('0012345',$document);
            $this->assertStringContainsString('30/10/2026',$document);
            $this->assertStringContainsString('<w:b/>',$document);
            $zip->close();
        } finally { unlink($path); }
    }
    public function test_export_requires_login_and_valid_fields(): void
    {
        $this->postJson('/dashboard/documents/subsidy-bond/export',$this->data())->assertUnauthorized();
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        (new SubsidyBondController)->export(new Request(['format'=>'pdf']),new SubsidyBondDocument);
    }
}
