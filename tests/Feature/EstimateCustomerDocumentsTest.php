<?php

namespace Tests\Feature;

use App\Http\Controllers\Api\EstimateController;
use App\Models\Estimate;
use App\Models\User;
use App\Support\CustomerDocumentFields;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EstimateCustomerDocumentsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        Schema::create('estimates', function (Blueprint $table) {
            $table->increments('estimate_id');
            $table->unsignedInteger('user_id');
            $table->unsignedInteger('created_by')->nullable();
            $table->json('customer_docs')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        DB::table('estimates')->insert(['estimate_id' => 1, 'user_id' => 10]);
        $user = \Mockery::mock(User::class)->makePartial();
        $user->id = 10;
        $user->shouldReceive('isAdmin')->andReturn(false);
        $this->actingAs($user);
        Storage::fake('public');
    }

    private function upload(array $groups)
    {
        $request = new Request(['documents' => array_map(fn ($group) => array_diff_key($group, ['files' => true]), $groups)]);
        $request->files->set('documents', array_map(fn ($group) => ['files' => $group['files']], $groups));
        return (new EstimateController())->uploadCustomerDocuments($request, '1');
    }

    private function group(string $category, string $label = 'Document'): array
    {
        return ['category' => $category, 'label' => $label, 'files' => [UploadedFile::fake()->create('document.pdf', 20, 'application/pdf')]];
    }

    public function test_one_category_can_be_uploaded_without_the_other_categories(): void
    {
        $response = $this->upload([$this->group('aadhar_card')]);
        $this->assertSame(200, $response->status());
        $this->assertCount(1, Estimate::findOrFail(1)->customer_docs);
        Storage::disk('public')->assertExists($response->getData(true)['data']['docs'][0]['path']);
    }

    public function test_multiple_files_and_custom_labels_are_saved_and_survive_later_uploads(): void
    {
        $groups = [];
        foreach (CustomerDocumentFields::REQUIRED as $category => $label) {
            $groups[] = $this->group($category, $label);
        }
        $groups[0]['files'][] = UploadedFile::fake()->create('back.pdf', 20, 'application/pdf');
        $groups[] = $this->group('extra', 'Property ownership proof');
        $response = $this->upload($groups);
        $this->assertSame(200, $response->status());
        $docs = $response->getData(true)['data']['docs'];
        $this->assertCount(7, $docs);
        $this->assertSame('Property ownership proof', $docs[6]['label']);
        foreach ($docs as $doc) Storage::disk('public')->assertExists($doc['path']);

        $response = $this->upload([$this->group('extra', 'Installation consent')]);
        $this->assertSame(200, $response->status());
        $this->assertCount(8, Estimate::findOrFail(1)->customer_docs);
        $this->assertSame('Property ownership proof', Estimate::findOrFail(1)->customer_docs[6]['label']);
    }

    public function test_invalid_files_and_empty_custom_labels_are_rejected(): void
    {
        $group = $this->group('extra', '');
        $group['files'] = [UploadedFile::fake()->create('script.exe', 20, 'application/octet-stream')];
        $response = $this->upload([$group]);
        $this->assertSame(422, $response->status());
        $errors = $response->getData(true)['errors'];
        $this->assertArrayHasKey('documents.0.label', $errors);
        $this->assertArrayHasKey('documents.0.files.0', $errors);
    }

    public function test_legacy_documents_are_preserved_when_uploading_only_an_extra_document(): void
    {
        DB::table('estimates')->where('estimate_id', 1)->update(['customer_docs' => json_encode(['legacy.pdf'])]);
        $response = $this->upload([$this->group('extra', 'Other')]);
        $this->assertSame(200, $response->status());
        $this->assertSame('legacy.pdf', $response->getData(true)['data']['docs'][0]['path']);
    }
}
