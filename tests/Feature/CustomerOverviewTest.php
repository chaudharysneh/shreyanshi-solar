<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\User;
use App\Services\CustomerOverviewService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CustomerOverviewTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        Storage::fake('public');
        Schema::create('customers', function (Blueprint $table) {
            $table->id(); $table->json('customer_docs')->nullable(); $table->timestamps(); $table->softDeletes();
        });
        foreach (['meetings', 'deals'] as $name) {
            Schema::create($name, function (Blueprint $table) {
                $table->id(); $table->integer('customer_id'); $table->timestamp('scheduled_at')->nullable();
                $table->timestamps(); $table->softDeletes();
            });
        }
        foreach (['leads', 'tasks', 'follow_ups', 'stages', 'custom_fields', 'custom_field_values', 'documents'] as $name) {
            Schema::create($name, function (Blueprint $table) use ($name) {
                $table->id();
                $table->timestamps();
                if ($name === 'leads') { $table->integer('converted_customer_id')->nullable(); $table->softDeletes(); }
                if ($name === 'tasks') {
                    $table->string('related_type')->nullable(); $table->integer('related_id')->nullable();
                    $table->integer('project_id')->nullable(); $table->integer('estimate_id')->nullable();
                    $table->integer('assigned_user_id')->nullable();
                }
                if ($name === 'follow_ups') { $table->integer('lead_id')->nullable(); $table->timestamp('follow_up_at')->nullable(); $table->softDeletes(); }
                if (in_array($name, ['stages', 'custom_fields'])) { $table->boolean('is_active')->default(true); $table->integer('sort_order')->default(0); }
                if ($name === 'custom_fields') $table->string('module');
                if ($name === 'custom_field_values') { $table->string('model_type'); $table->integer('model_id'); }
                if ($name === 'documents') { $table->string('title'); $table->string('file_path'); $table->string('documentable_type'); $table->integer('documentable_id'); }
            });
        }
        $user = new class extends User {
            public bool $allowRecords = true;
            public function isAdmin(): bool { return true; }
            public function hasMatrixPermission(string $permission): bool { return $this->allowRecords; }
            public function can($abilities, $arguments = []): bool { return $this->allowRecords; }
            public function checkPermissionTo($permission, $guardName = null): bool { return false; }
        };
        $user->id = 1;
        $this->actingAs($user);
    }

    private function customer(): Customer
    {
        $customer = new Customer;
        $customer->forceFill(['id' => 42]);
        $customer->setRelation('meetings', new Collection);
        $customer->setRelation('deals', new Collection);
        return $customer;
    }

    public function test_documents_are_scoped_to_customer_and_missing_files_are_identified(): void
    {
        Storage::disk('public')->put('documents/own.pdf', 'fixture');
        foreach ([[42, 'own.pdf'], [42, 'missing.pdf'], [99, 'other.pdf']] as [$owner, $file]) {
            DB::table('documents')->insert(['title' => $file, 'file_path' => 'documents/'.$file, 'documentable_type' => Customer::class, 'documentable_id' => $owner]);
        }
        $overview = app(CustomerOverviewService::class)->build($this->customer());
        $this->assertCount(2, $overview['documents']);
        $this->assertTrue($overview['documents']->firstWhere('name', 'own.pdf')['available']);
        $this->assertFalse($overview['documents']->firstWhere('name', 'missing.pdf')['available']);
        $this->assertNull($overview['documents']->firstWhere('name', 'other.pdf'));
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $overview['documents']->first()['key']);
    }

    public function test_documents_from_restricted_estimates_are_not_exposed(): void
    {
        Schema::create('estimates', function (Blueprint $table) {
            $table->id('estimate_id'); $table->integer('customer_id');
            $table->string('attach_file')->nullable(); $table->text('customer_docs')->nullable();
            $table->timestamps(); $table->softDeletes();
        });
        DB::table('estimates')->insert(['customer_id' => 42, 'attach_file' => 'private.pdf']);
        auth()->user()->allowRecords = false;
        $overview = app(CustomerOverviewService::class)->build($this->customer());
        $this->assertCount(0, $overview['sections']['estimates']['records']);
        $this->assertCount(0, $overview['documents']);
    }

    public function test_customer_upload_accepts_multiple_files_and_can_delete_one_without_removing_others(): void
    {
        DB::table('customers')->insert(['id' => 42]);
        $customer = Customer::findOrFail(42);
        $groups = []; $files = [];
        foreach (\App\Support\CustomerDocumentFields::REQUIRED as $category => $label) {
            $groups[] = compact('category', 'label');
            $files[] = ['files' => [\Illuminate\Http\UploadedFile::fake()->create('document.pdf', 20, 'application/pdf')]];
        }
        $files[0]['files'][] = \Illuminate\Http\UploadedFile::fake()->create('back.pdf', 20, 'application/pdf');
        $groups[] = ['category' => 'extra', 'label' => 'Ownership proof'];
        $files[] = ['files' => [\Illuminate\Http\UploadedFile::fake()->create('ownership.pdf', 20, 'application/pdf')]];
        $request = new \Illuminate\Http\Request(['documents' => $groups]);
        $request->files->set('documents', $files);
        $controller = new \App\Http\Controllers\CustomerController;
        $response = $controller->uploadCustomerDocuments($request, $customer);
        $this->assertSame(200, $response->status());
        $this->assertCount(7, $response->getData(true)['data']['docs']);
        $this->assertSame('Ownership proof', Customer::findOrFail(42)->customer_docs[6]['label']);
        $overview = app(CustomerOverviewService::class)->build($customer->fresh());
        $document = $overview['documents']->first();
        $this->assertTrue($document['can_delete']);
        $this->assertSame(200, $controller->deleteRelatedDocument($customer->fresh(), $document['key'])->status());
        Storage::disk('public')->assertMissing($document['path']);
        $this->assertCount(6, Customer::findOrFail(42)->customer_docs);
    }

    public function test_deleting_estimate_document_updates_its_source_and_rejects_other_customer_keys(): void
    {
        Schema::create('estimates', function (Blueprint $table) {
            $table->id('estimate_id'); $table->integer('customer_id'); $table->json('customer_docs')->nullable();
            $table->timestamps(); $table->softDeletes();
        });
        DB::table('customers')->insert([['id' => 42], ['id' => 99]]);
        Storage::disk('public')->put('estimate/test.pdf', 'fixture');
        DB::table('estimates')->insert(['customer_id' => 42, 'customer_docs' => json_encode([
            ['path' => 'estimate/test.pdf', 'original_name' => 'test.pdf', 'category' => 'pan_card', 'label' => 'PAN card'],
        ])]);
        $customer = Customer::findOrFail(42);
        $document = app(CustomerOverviewService::class)->build($customer)['documents']->first();
        $controller = new \App\Http\Controllers\CustomerController;
        try {
            $controller->deleteRelatedDocument(Customer::findOrFail(99), $document['key']);
            $this->fail('A different customer must not be able to delete this document.');
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $exception) {
            $this->assertSame(404, $exception->getStatusCode());
        }
        $controller->deleteRelatedDocument($customer, $document['key']);
        $this->assertSame([], \App\Models\Estimate::first()->customer_docs);
        Storage::disk('public')->assertMissing('estimate/test.pdf');
    }

    public function test_customer_upload_accepts_one_category_without_the_other_categories(): void
    {
        DB::table('customers')->insert(['id' => 42]);
        $request = new \Illuminate\Http\Request(['documents' => [['category' => 'pan_card', 'label' => 'PAN card']]]);
        $request->files->set('documents', [['files' => [\Illuminate\Http\UploadedFile::fake()->create('pan.pdf', 20, 'application/pdf')]]]);
        $response = (new \App\Http\Controllers\CustomerController)->uploadCustomerDocuments($request, Customer::findOrFail(42));
        $this->assertSame(200, $response->status());
        $this->assertCount(1, Customer::findOrFail(42)->customer_docs);
        Storage::disk('public')->assertExists(Customer::findOrFail(42)->customer_docs[0]['path']);
    }

    public function test_document_delete_requires_edit_permission(): void
    {
        DB::table('customers')->insert(['id' => 42, 'customer_docs' => json_encode([['path' => 'test.pdf', 'original_name' => 'test.pdf']])]);
        $customer = Customer::findOrFail(42);
        auth()->user()->allowRecords = false;
        $document = app(CustomerOverviewService::class)->build($customer)['documents']->first();
        $this->assertFalse($document['can_delete']);
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        (new \App\Http\Controllers\CustomerController)->deleteRelatedDocument($customer, $document['key']);
    }

    public function test_customer_without_related_records_has_empty_sections(): void
    {
        $overview = app(CustomerOverviewService::class)->build($this->customer());
        $this->assertCount(0, $overview['documents']);
        $this->assertCount(0, $overview['stages']);
        foreach ($overview['sections'] as $section) $this->assertCount(0, $section['records']);
        foreach ($overview['workflow'] as $rows) $this->assertCount(0, $rows);
    }
}
