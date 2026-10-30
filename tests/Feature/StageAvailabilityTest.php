<?php

namespace Tests\Feature;

use App\Http\Controllers\Api\Masters\StageController;
use App\Models\Stage;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class StageAvailabilityTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        Schema::create('stages', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('status')->default('in_progress');
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function test_inactive_stage_is_hidden_but_can_be_managed_and_reactivated(): void
    {
        $stage = Stage::create(['name' => 'Registration', 'is_active' => true]);
        $controller = new StageController;
        $controller->update(Request::create('/', 'PUT', ['name' => 'Registration', 'status' => 'inactive']), $stage->id);

        $this->assertNull(Stage::find($stage->id));
        $this->assertSame('inactive', Stage::withoutGlobalScope('active')->find($stage->id)->status);
        $this->assertSame(0, $controller->index(Request::create('/'))->getData(true)['data']['total']);
        $this->assertSame(1, $controller->index(Request::create('/', 'GET', ['include_inactive' => 1]))->getData(true)['data']['total']);

        $controller->update(Request::create('/', 'PUT', ['name' => 'Registration', 'status' => 'active']), $stage->id);
        $this->assertSame('active', Stage::findOrFail($stage->id)->status);
        $this->assertSame('in_progress', DB::table('stages')->value('status'));
    }

    public function test_progress_status_is_no_longer_accepted(): void
    {
        $response = (new StageController)->store(Request::create('/', 'POST', ['name' => 'Registration', 'status' => 'completed']));
        $this->assertSame(422, $response->getStatusCode());
        $this->assertSame(0, Stage::count());
    }
}
