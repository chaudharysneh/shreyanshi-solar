<?php

namespace Tests\Feature;

use App\Http\Controllers\Api\LeadController;
use App\Models\ChannelPartner;
use App\Models\Lead;
use App\Models\LeadSource;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use ReflectionMethod;
use Tests\TestCase;

class LeadSourceDetailsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\App\Http\Middleware\Authenticate::class);
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        Schema::create('leads', function (Blueprint $table) {
            $table->id(); $table->string('source')->nullable(); $table->unsignedBigInteger('lead_source_id')->nullable();
        });
        (require database_path('migrations/2026_03_05_115941_create_lead_sources_table.php'))->up();
        DB::table('leads')->insert(['source' => 'Trade fair']);
        (require database_path('migrations/2026_10_26_000001_add_lead_source_details_and_channel_partners.php'))->up();
        Schema::create('users', function (Blueprint $table) { $table->id(); $table->string('name'); });
        (require database_path('migrations/2026_10_26_000002_expand_channel_partner_master.php'))->up();
        $this->login(true);
    }

    private function login(bool $admin, bool $canEdit = true): void
    {
        $user = new class extends User {
            public bool $admin = false;
            public bool $allowLead = true;
            public function isAdmin(): bool { return $this->admin; }
            public function hasMatrixPermission(string $permission): bool { return $this->allowLead; }
        };
        $user->id = 1; $user->admin = $admin; $user->allowLead = $canEdit;
        $this->actingAs($user);
    }

    public function test_source_crud_and_legacy_source_backfill(): void
    {
        $this->assertSame(LeadSource::where('name', 'Trade fair')->value('id'), DB::table('leads')->value('lead_source_id'));
        $source = $this->postJson('/api/masters/lead-sources', ['name' => 'Exhibition'])->assertCreated()->json('data');
        $this->postJson('/api/masters/lead-sources', ['name' => 'Exhibition'])->assertUnprocessable();
        $this->putJson('/api/masters/lead-sources/'.$source['id'], ['name' => 'Online event'])->assertOk()->assertJsonPath('data.name', 'Online event');
        $this->deleteJson('/api/masters/lead-sources/'.$source['id'])->assertOk();
        $this->assertFalse(LeadSource::whereKey($source['id'])->exists());
    }

    public function test_used_sources_and_special_sources_are_protected_and_renames_keep_the_kind(): void
    {
        $used = LeadSource::where('name', 'Trade fair')->firstOrFail();
        $this->deleteJson('/api/masters/lead-sources/'.$used->id)->assertUnprocessable();
        $this->putJson('/api/masters/lead-sources/'.$used->id, ['name' => 'Fair'])->assertOk();
        $this->assertSame('Fair', DB::table('leads')->value('source'));
        $referral = LeadSource::where('kind', 'referral')->firstOrFail();
        $this->putJson('/api/masters/lead-sources/'.$referral->id, ['name' => 'Referred by customer'])->assertOk()->assertJsonPath('data.kind', 'referral');
        $this->deleteJson('/api/masters/lead-sources/'.$referral->id)->assertUnprocessable();
    }

    public function test_staff_cannot_manage_sources_but_can_create_and_search_partners(): void
    {
        $this->login(false);
        $this->postJson('/api/masters/lead-sources', ['name' => 'Blocked'])->assertForbidden();
        $id = LeadSource::firstOrFail()->id;
        $this->putJson('/api/masters/lead-sources/'.$id, ['name' => 'Blocked'])->assertForbidden();
        $this->deleteJson('/api/masters/lead-sources/'.$id)->assertForbidden();
        $partner = $this->postJson('/api/channel-partners', ['name' => 'Solar Associates', 'phone' => '9876543210', 'address' => 'Ahmedabad'])->assertCreated()->json('data');
        $this->getJson('/api/channel-partners?q=Solar')->assertOk()->assertJsonPath('results.0.id', $partner['id']);
        $this->getJson('/api/channel-partners?q=Absent')->assertOk()->assertJsonCount(0, 'results');
        $this->postJson('/api/channel-partners', ['name' => 'Missing phone'])->assertUnprocessable();
        $this->login(false, false);
        $this->postJson('/api/channel-partners', ['name' => 'Blocked', 'phone' => '9876543210'])->assertForbidden();
        $this->getJson('/api/channel-partners')->assertForbidden();
    }

    public function test_edit_fields_restore_the_saved_source_partner_and_referral_name(): void
    {
        $partner = ChannelPartner::create(['name' => 'Solar Partner', 'phone' => '9876543210']);
        $source = LeadSource::where('kind', 'channel_partner')->firstOrFail();
        $lead = new Lead(['lead_source_id' => $source->id, 'channel_partner_id' => $partner->id, 'referral_name' => 'Saved referral']);
        $html = view('crm.leads.partials.source-master', ['lead' => $lead, 'sources' => LeadSource::all(), 'errors' => new \Illuminate\Support\ViewErrorBag])->render();
        $this->assertStringContainsString('data-kind="channel_partner" selected', $html);
        $this->assertStringContainsString('selected>Solar Partner</option>', $html);
        $this->assertStringContainsString('value="Saved referral"', $html);
    }

    public function test_source_specific_details_are_validated_and_old_details_are_cleared_when_source_changes(): void
    {
        $rulesMethod = new ReflectionMethod(LeadController::class, 'sourceDetailRules');
        $normalize = new ReflectionMethod(LeadController::class, 'normalizeSourceDetails');
        $controller = new LeadController;
        $referral = LeadSource::where('kind', 'referral')->firstOrFail();
        $channel = LeadSource::where('kind', 'channel_partner')->firstOrFail();
        $partner = ChannelPartner::create(['name' => 'Partner', 'phone' => '9876543210']);
        foreach ([$referral->id => 'referral_name', $channel->id => 'channel_partner_id'] as $id => $field) {
            $rules = $rulesMethod->invoke($controller, new Request(['lead_source_id' => $id]));
            $this->assertArrayHasKey($field, Validator::make([], $rules)->errors()->toArray());
        }
        $rules = $rulesMethod->invoke($controller, new Request(['lead_source_id' => $channel->id]));
        $this->assertFalse(Validator::make(['channel_partner_id' => 999], $rules)->passes());
        $this->assertTrue(Validator::make(['channel_partner_id' => $partner->id], $rules)->passes());
        $data = $normalize->invoke($controller, ['lead_source_id' => $channel->id, 'channel_partner_id' => $partner->id, 'referral_name' => 'Stale']);
        $this->assertNull($data['referral_name']);
        $this->assertSame($partner->id, $data['channel_partner_id']);
        $this->assertSame($channel->name, $data['source']);
        $lead = new Lead(['lead_source_id' => $channel->id]);
        $cleared = $normalize->invoke($controller, ['lead_source_id' => null, 'source' => ''], $lead);
        $this->assertNull($cleared['channel_partner_id']);
    }
}
