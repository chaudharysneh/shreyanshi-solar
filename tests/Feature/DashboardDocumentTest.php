<?php
namespace Tests\Feature;
use App\Http\Controllers\DashboardDocumentController;
use App\Models\Customer;
use App\Models\DashboardDocument;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DashboardDocumentTest extends TestCase
{
    protected function setUp(): void {
        parent::setUp();
        config(['database.default'=>'sqlite','database.connections.sqlite.database'=>':memory:']); DB::purge('sqlite');
        Schema::create('users',function (Blueprint $t) { $t->id(); $t->string('name')->default('Tester'); });
        Schema::create('customers',function (Blueprint $t) { $t->id(); $t->string('name'); $t->string('phone'); $t->text('address')->nullable(); $t->boolean('is_active')->default(true); $t->unsignedBigInteger('pipeline_stage_id')->nullable(); $t->timestamps(); $t->softDeletes(); });
        Schema::create('stages',function (Blueprint $t) { $t->id(); $t->integer('sort_order')->default(0); $t->boolean('is_active')->default(true); });
        (require database_path('migrations/2026_10_30_120000_create_dashboard_documents_table.php'))->up();
        (require database_path('migrations/2026_10_30_130000_create_dashboard_document_versions_table.php'))->up();
        DB::table('users')->insert([['id'=>1],['id'=>2]]);
        DB::table('customers')->insert(['id'=>1,'name'=>'Test Consumer','phone'=>'9876543210','address'=>'Test address']);
        Storage::fake('local');
    }
    private function controller() {
        $controller=\Mockery::mock(DashboardDocumentController::class)->makePartial();
        $controller->shouldReceive('authorize')->andReturn(true);
        return $controller;
    }
    private function request(array $data=[],int $userId=1): Request {
        $user=\Mockery::mock(User::class)->makePartial(); $user->id=$userId;
        $user->shouldReceive('hasMatrixPermission')->with('create_customers')->andReturn(true);
        $request=new Request($data); $request->setUserResolver(fn ()=>$user); return $request;
    }
    private function data(): array {
        return ['customer_id'=>1,'type'=>'subsidy','filename'=>'Test Bond','fields'=>['consumer_name'=>'Test Consumer','agreement_date'=>'2026-10-30','consumer_number'=>'00123','address'=>'Test address']];
    }
    public function test_both_types_are_saved_listed_and_updated_without_duplicates(): void {
        $controller=$this->controller();
        $id=$controller->save($this->request($this->data()))->getData(true)['id'];
        $solar=array_replace($this->data(),['type'=>'solar','filename'=>'Solar details']);
        $controller->save($this->request($solar));
        $this->assertSame(2,DashboardDocument::count());
        $controller->save($this->request(array_replace($this->data(),['document_id'=>$id,'filename'=>'Updated Bond'])));
        $this->assertSame(2,DashboardDocument::count());
        $result=$controller->index($this->request(['type'=>'subsidy','q'=>'Updated']))->getData(true);
        $this->assertSame(1,$result['total']);
        $this->assertArrayNotHasKey('fields',$result['data'][0]);
        $saved=$controller->show($this->request(),$id)->getData(true);
        $this->assertSame('00123',$saved['fields']['consumer_number']);
        $this->assertSame('Test Consumer',$saved['customer']['name']);
        $this->assertSame(0,$controller->index($this->request([],2))->getData(true)['total']);
    }
    public function test_another_user_cannot_open_saved_document(): void {
        $controller=$this->controller();
        $id=$controller->save($this->request($this->data()))->getData(true)['id'];
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        $controller->show($this->request([],2),$id);
    }
    public function test_quick_customer_creation_and_duplicate_phone_validation(): void {
        $controller=$this->controller();
        $response=Customer::withoutEvents(fn ()=>$controller->createCustomer($this->request(['name'=>'New Customer','phone'=>'9123456789'])));
        $this->assertSame(201,$response->status());
        $this->assertSame('New Customer',$response->getData(true)['name']);
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $controller->createCustomer($this->request(['name'=>'Duplicate','phone'=>'9123456789']));
    }
    public function test_aadhaar_is_private_and_preserved_when_reopening_without_new_upload(): void {
        $controller=$this->controller();
        $request=$this->request(array_replace($this->data(),['type'=>'solar']));
        $request->files->set('consumer_aadhaar_image',UploadedFile::fake()->image('aadhaar.png'));
        $id=$controller->save($request)->getData(true)['id'];
        $path=DashboardDocument::findOrFail($id)->aadhaar_path;
        Storage::disk('local')->assertExists($path);
        $controller->save($this->request(array_replace($this->data(),['type'=>'solar','document_id'=>$id])));
        $this->assertSame($path,DashboardDocument::findOrFail($id)->aadhaar_path);
        $this->assertTrue($controller->show($this->request(),$id)->getData(true)['has_aadhaar']);
        $this->assertArrayNotHasKey('aadhaar_path',$controller->show($this->request(),$id)->getData(true));
    }
    public function test_changed_saves_keep_history_and_unchanged_saves_do_not_duplicate_it(): void {
        $controller=$this->controller();
        $id=$controller->save($this->request($this->data()))->getData(true)['id'];
        $same=array_replace($this->data(),['document_id'=>$id]);
        $controller->save($this->request($same));
        $this->assertSame(1,DashboardDocument::findOrFail($id)->versions()->count());
        $changed=$same; $changed['fields']['consumer_number']='00999'; $changed['filename']='Revised Bond';
        $controller->save($this->request($changed));
        $versions=DashboardDocument::findOrFail($id)->versions()->get();
        $this->assertCount(2,$versions);
        $this->assertSame('00999',$versions[0]->fields['consumer_number']);
        $this->assertSame('00123',$versions[1]->fields['consumer_number']);
        $this->assertSame('Test Bond',$versions[1]->filename);
        $history=\Mockery::mock(\App\Http\Controllers\CustomerFormDocumentController::class)->makePartial();
        $history->shouldReceive('authorize')->andReturn(true);
        $customer=Customer::findOrFail(1);
        $view=$history->show($this->request(['version'=>1]),$customer,$id);
        $this->assertSame('00123',$view->getData()['selected']->fields['consumer_number']);
        $response=$history->download($this->request(['format'=>'word']),$customer,$id,1);
        $this->assertSame(200,$response->status());
        $path=tempnam(sys_get_temp_dir(),'history-');
        try {
            file_put_contents($path,$response->getContent());
            $zip=new \ZipArchive; $zip->open($path);
            $xml=$zip->getFromName('word/document.xml'); $zip->close();
            $this->assertStringContainsString('00123',$xml);
            $this->assertStringNotContainsString('00999',$xml);
        } finally { unlink($path); }
    }
    public function test_replaced_aadhaar_remains_available_in_old_version(): void {
        $controller=$this->controller();
        $data=array_replace($this->data(),['type'=>'solar']);
        $request=$this->request($data);
        $request->files->set('consumer_aadhaar_image',UploadedFile::fake()->image('old.png'));
        $id=$controller->save($request)->getData(true)['id'];
        $oldPath=DashboardDocument::findOrFail($id)->aadhaar_path;
        $request=$this->request($data+['document_id'=>$id]);
        $request->files->set('consumer_aadhaar_image',UploadedFile::fake()->image('new.png'));
        $controller->save($request);
        $record=DashboardDocument::findOrFail($id);
        $this->assertNotSame($oldPath,$record->aadhaar_path);
        Storage::disk('local')->assertExists($oldPath);
        Storage::disk('local')->assertExists($record->aadhaar_path);
        $this->assertSame($oldPath,$record->versions()->where('version',1)->firstOrFail()->aadhaar_path);
    }
    public function test_history_cannot_be_opened_under_a_different_customer(): void {
        $id=$this->controller()->save($this->request($this->data()))->getData(true)['id'];
        $history=\Mockery::mock(\App\Http\Controllers\CustomerFormDocumentController::class)->makePartial();
        $history->shouldReceive('authorize')->andReturn(true);
        $wrong=new Customer; $wrong->id=999;
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        $history->show($this->request(),$wrong,$id);
    }
    public function test_solar_export_uses_saved_fields_and_history_snapshot(): void {
        $controller=$this->controller();
        $data=array_replace($this->data(),['type'=>'solar']);
        $id=$controller->save($this->request($data))->getData(true)['id'];
        $export=\Mockery::mock(\App\Http\Controllers\SolarDocumentController::class)->makePartial();
        $export->shouldReceive('authorize')->andReturn(true);
        $response=$export->export($this->request(['document_id'=>$id,'format'=>'word','filename'=>'Solar Export']),new \App\Services\SolarDocument);
        $this->assertSame(200,$response->status());
        $this->assertStringContainsString('wordprocessingml',$response->headers->get('Content-Type'));
        $data['document_id']=$id; $data['fields']['consumer_name']='Updated Solar Consumer';
        $controller->save($this->request($data));
        $history=\Mockery::mock(\App\Http\Controllers\CustomerFormDocumentController::class)->makePartial();
        $history->shouldReceive('authorize')->andReturn(true);
        $old=$history->download($this->request(['format'=>'word']),Customer::findOrFail(1),$id,1);
        $temp=tempnam(sys_get_temp_dir(),'solar-history-');
        try {
            file_put_contents($temp,$old->getContent()); $zip=new \ZipArchive; $zip->open($temp);
            $xml=$zip->getFromName('word/document.xml'); $zip->close();
            $this->assertStringContainsString('Test Consumer',$xml);
            $this->assertStringNotContainsString('Updated Solar Consumer',$xml);
        } finally { unlink($temp); }
    }
    public function test_solar_export_rejects_someone_elses_document(): void {
        $id=$this->controller()->save($this->request(array_replace($this->data(),['type'=>'solar'])))->getData(true)['id'];
        $export=\Mockery::mock(\App\Http\Controllers\SolarDocumentController::class)->makePartial();
        $export->shouldReceive('authorize')->andReturn(true);
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        $export->export($this->request(['document_id'=>$id,'format'=>'word','filename'=>'Export'],2),new \App\Services\SolarDocument);
    }
    public function test_multipart_http_upload_saves_image_in_both_tables_and_verifies_it(): void {
        $this->withoutExceptionHandling();
        (require database_path('migrations/2026_03_10_091425_create_permission_tables.php'))->up();
        Schema::create('subscription_user_plan',function (Blueprint $table) { $table->id(); $table->unsignedBigInteger('user_id'); });
        \Illuminate\Support\Facades\Gate::before(fn () => true);
        $user=\Mockery::mock(User::class)->makePartial(); $user->id=1;
        $user->shouldReceive('isAdmin')->andReturn(true);
        $user->shouldReceive('hasMatrixPermission')->andReturn(true);
        $data=array_replace($this->data(),['type'=>'solar']);
        $data['consumer_aadhaar_image']=UploadedFile::fake()->image('aadhaar.jpg',739,415);
        $response=$this->actingAs($user)->post('/dashboard/documents',$data,['Accept'=>'application/json']);
        $response->assertOk()->assertJsonPath('has_aadhaar',true);
        $id=$response->json('id');
        $document=DashboardDocument::findOrFail($id);
        $this->assertNotEmpty($document->aadhaar_path);
        Storage::disk('local')->assertExists($document->aadhaar_path);
        $this->assertSame($document->aadhaar_path,$document->versions()->firstOrFail()->aadhaar_path);
        $this->getJson('/dashboard/documents/'.$id)->assertOk()->assertJsonPath('has_aadhaar',true);
        $this->get('/dashboard/documents/'.$id.'/aadhaar')->assertOk();
        $preview=$this->get('/dashboard/documents/'.$id.'/aadhaar?preview=1')->assertOk()->assertHeader('Content-Type','image/jpeg');
        $this->assertStringStartsWith('inline',$preview->headers->get('Content-Disposition'));
        unset($data['consumer_aadhaar_image']); $data['document_id']=$id;
        $this->post('/dashboard/documents',$data,['Accept'=>'application/json'])->assertOk()->assertJsonPath('has_aadhaar',true);
        $this->assertSame(1,DashboardDocument::count());
        $this->assertSame(1,$document->versions()->count());
    }
    public function test_json_image_upload_saves_verifiable_path_and_exports_clean_pdf(): void {
        (require database_path('migrations/2026_03_10_091425_create_permission_tables.php'))->up();
        Schema::create('subscription_user_plan',function (Blueprint $table) { $table->id(); $table->unsignedBigInteger('user_id'); });
        \Illuminate\Support\Facades\Gate::before(fn () => true);
        $user=\Mockery::mock(User::class)->makePartial(); $user->id=1;
        $user->shouldReceive('isAdmin')->andReturn(true);
        $user->shouldReceive('hasMatrixPermission')->andReturn(true);
        $this->actingAs($user);
        $image=UploadedFile::fake()->image('aadhaar.png',739,415);
        $bytes=file_get_contents($image->getPathname());
        $data=array_replace($this->data(),['type'=>'solar']);
        $data['consumer_aadhaar_image_data']='data:image/png;base64,'.base64_encode($bytes);
        $response=$this->postJson('/dashboard/documents',$data)->assertOk()->assertJsonPath('has_aadhaar',true);
        $id=$response->json('id'); $document=DashboardDocument::findOrFail($id);
        $this->assertSame($bytes,Storage::disk('local')->get($document->aadhaar_path));
        $this->assertSame($document->aadhaar_path,$document->versions()->firstOrFail()->aadhaar_path);
        $this->getJson('/dashboard/documents/'.$id)->assertOk()->assertJsonPath('has_aadhaar',true);
        unset($data['consumer_aadhaar_image_data']); $data['document_id']=$id;
        $this->postJson('/dashboard/documents',$data)->assertOk()->assertJsonPath('has_aadhaar',true);
        $this->assertSame(1,DashboardDocument::count());
        $this->assertSame(1,$document->versions()->count());
        $pdf=$this->postJson('/dashboard/documents/solar/export',['document_id'=>$id,'filename'=>'Test solar','format'=>'pdf'])->assertOk();
        $this->assertStringStartsWith('%PDF-',$pdf->getContent());
        $this->assertStringContainsString('/Subtype /Image',$pdf->getContent());
    }
    public function test_json_upload_rejects_disguised_non_image_without_saving_a_row(): void {
        $data=array_replace($this->data(),['type'=>'solar','consumer_aadhaar_image_data'=>'data:image/png;base64,'.base64_encode('This is not an image')]);
        try { $this->controller()->save($this->request($data)); $this->fail('Invalid image accepted'); }
        catch (\Illuminate\Validation\ValidationException $error) {
            $this->assertArrayHasKey('consumer_aadhaar_image',$error->errors());
        }
        $this->assertSame(0,DashboardDocument::count());
    }
}
