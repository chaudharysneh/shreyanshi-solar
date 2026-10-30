<?php

namespace Tests\Feature;

use App\Models\LeadStatus;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class LeadStatusMasterTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\App\Http\Middleware\Authenticate::class);
        // This suite never connects to or migrates the application's database.
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->string('status')->default('new');
        });
        Schema::create('lead_status_histories', function (Blueprint $table) {
            $table->id();
            $table->string('status');
        });
        $migration = require database_path('migrations/2026_10_24_000001_create_lead_statuses_table.php');
        $migration->up();
        $admin = new class extends User {
            public function isAdmin(): bool { return true; }
        };
        $admin->id = 1;
        $this->actingAs($admin);
    }

    public function test_inline_crud_preserves_codes_and_validates_names(): void
    {
        $created = $this->postJson('/api/masters/lead-statuses', ['name' => 'Demo Booked'])
            ->assertCreated()->json('data');
        $this->assertTrue(Validator::make(['status' => $created['code']], ['status' => 'exists:lead_statuses,code'])->passes());
        $this->postJson('/api/masters/lead-statuses', ['name' => 'Demo Booked'])->assertUnprocessable();
        $this->postJson('/api/masters/lead-statuses', ['name' => ''])->assertUnprocessable();
        $this->putJson('/api/masters/lead-statuses/'.$created['id'], ['name' => 'Demo Scheduled'])
            ->assertOk()->assertJsonPath('data.code', $created['code'])->assertJsonPath('data.name', 'Demo Scheduled');
        $this->deleteJson('/api/masters/lead-statuses/'.$created['id'])->assertOk();
        $this->assertFalse(LeadStatus::whereKey($created['id'])->exists());
    }

    public function test_used_statuses_and_default_are_protected(): void
    {
        $this->deleteJson('/api/masters/lead-statuses/'.LeadStatus::where('code', 'new')->value('id'))->assertUnprocessable();
        $used = LeadStatus::where('code', 'working')->firstOrFail();
        DB::table('leads')->insert(['status' => $used->code]);
        $this->deleteJson('/api/masters/lead-statuses/'.$used->id)->assertUnprocessable();
        $historical = LeadStatus::where('code', 'qualified')->firstOrFail();
        DB::table('lead_status_histories')->insert(['status' => $historical->code]);
        $this->deleteJson('/api/masters/lead-statuses/'.$historical->id)->assertUnprocessable();
    }

    public function test_non_admin_cannot_modify_master_records(): void
    {
        $staff = new class extends User {
            public function isAdmin(): bool { return false; }
        };
        $staff->id = 2;
        $this->actingAs($staff);
        $id = LeadStatus::firstOrFail()->id;
        $this->postJson('/api/masters/lead-statuses', ['name' => 'Blocked'])->assertForbidden();
        $this->putJson('/api/masters/lead-statuses/'.$id, ['name' => 'Blocked'])->assertForbidden();
        $this->deleteJson('/api/masters/lead-statuses/'.$id)->assertForbidden();
    }
}
