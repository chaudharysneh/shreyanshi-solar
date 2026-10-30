<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class IndexStatusTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\App\Http\Middleware\Authenticate::class);
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        $this->mock(\App\Services\UserLogService::class, function ($mock) { $mock->shouldReceive('updated')->andReturnNull(); });
        Schema::create('notifications', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('user_id'); $table->text('notification_text'); $table->boolean('is_read'); $table->timestamps();
        });
        Schema::create('permissions', function (Blueprint $table) { $table->id(); $table->string('name'); $table->string('guard_name'); });
        Schema::create('leads', function (Blueprint $table) {
            $table->id(); $table->string('name'); $table->string('status');
            foreach (['created_by', 'updated_by', 'user_id', 'assigned_user_id'] as $field) $table->unsignedBigInteger($field)->nullable();
            $table->softDeletes(); $table->timestamps();
        });
        Schema::create('lead_statuses', function (Blueprint $table) { $table->id(); $table->string('code'); $table->string('name'); $table->timestamps(); });
        Schema::create('lead_status_histories', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('lead_id'); $table->string('status');
            $table->text('comment')->nullable(); $table->unsignedBigInteger('updated_by'); $table->timestamps();
        });
        DB::table('lead_statuses')->insert([['code' => 'new', 'name' => 'New'], ['code' => 'demo', 'name' => 'Demo Booked']]);
        DB::table('leads')->insert(['id' => 1, 'name' => 'Example', 'status' => 'new', 'created_by' => 1, 'user_id' => 1]);
        $this->login(true);
    }

    private function login(bool $admin, bool $permission = true): void
    {
        $user = new class extends User {
            public bool $admin = true;
            public bool $allowed = true;
            public function isAdmin(): bool { return $this->admin; }
            public function hasMatrixPermission(string $permission): bool { return $this->allowed; }
        };
        $user->id = $admin ? 1 : 2; $user->admin = $admin; $user->allowed = $permission;
        $this->actingAs($user);
    }

    public function test_custom_options_and_comments_are_saved_only_for_actual_changes(): void
    {
        $this->getJson('/api/index-status/leads/1')->assertOk()->assertJsonPath('requires_comment', true)->assertJsonFragment(['value' => 'demo', 'label' => 'Demo Booked']);
        $this->patchJson('/api/index-status/leads/1', ['status' => 'demo', 'name' => 'Unwanted', 'comment' => 'Demo agreed'])->assertOk();
        $this->assertDatabaseHas('leads', ['id' => 1, 'status' => 'demo', 'name' => 'Example']);
        $this->assertDatabaseHas('lead_status_histories', ['lead_id' => 1, 'status' => 'demo', 'comment' => 'Demo agreed', 'updated_by' => 1]);
        $this->patchJson('/api/index-status/leads/1', ['status' => 'demo', 'comment' => 'Same status'])->assertOk();
        $this->assertDatabaseCount('lead_status_histories', 1);
    }

    public function test_invalid_status_missing_comment_and_task_bypass_are_rejected(): void
    {
        $this->patchJson('/api/index-status/leads/1', ['status' => 'bogus', 'comment' => 'Test'])->assertUnprocessable();
        $this->patchJson('/api/index-status/leads/1', ['status' => 'demo'])->assertUnprocessable();
        $this->patchJson('/api/index-status/leads/1', ['status' => 'demo', 'comment' => '   '])->assertUnprocessable();
        $this->patchJson('/api/index-status/tasks/1', ['status' => 'completed'])->assertNotFound();
        $this->assertDatabaseCount('lead_status_histories', 0);
        $this->assertDatabaseHas('leads', ['id' => 1, 'status' => 'new']);
    }

    public function test_edit_permission_and_record_ownership_are_enforced(): void
    {
        $this->login(false, false);
        $this->getJson('/api/index-status/leads/1')->assertForbidden();
        $this->patchJson('/api/index-status/leads/1', ['status' => 'demo', 'comment' => 'Test'])->assertForbidden();
        $this->login(false, true);
        $this->patchJson('/api/index-status/leads/1', ['status' => 'demo', 'comment' => 'Test'])->assertNotFound();
        $this->assertDatabaseHas('leads', ['id' => 1, 'status' => 'new']);
    }

    public function test_master_status_uses_active_flag_without_changing_other_fields(): void
    {
        Schema::create('finance_masters', function (Blueprint $table) { $table->id(); $table->string('name'); $table->boolean('is_active'); $table->timestamps(); });
        DB::table('finance_masters')->insert(['id' => 1, 'name' => 'Bank Finance', 'is_active' => true]);
        $this->getJson('/api/index-status/finance-masters/1')->assertOk()->assertJsonPath('value', '1');
        $this->patchJson('/api/index-status/finance-masters/1', ['status' => '0'])->assertOk();
        $this->assertDatabaseHas('finance_masters', ['id' => 1, 'name' => 'Bank Finance', 'is_active' => false]);
    }

    public function test_deal_updates_use_existing_business_handler(): void
    {
        Schema::create('statuses', function (Blueprint $table) { $table->id(); $table->string('name'); $table->string('type'); $table->boolean('is_active'); });
        Schema::create('deals', function (Blueprint $table) { $table->id(); $table->unsignedBigInteger('status_id'); $table->softDeletes(); });
        DB::table('statuses')->insert([['id' => 1, 'name' => 'Pending', 'type' => 'deal', 'is_active' => true], ['id' => 2, 'name' => 'Won', 'type' => 'deal', 'is_active' => true]]);
        DB::table('deals')->insert(['id' => 1, 'status_id' => 1]);
        $this->mock(\App\Http\Controllers\Api\DealController::class, function ($mock) {
            $mock->shouldReceive('updateStatus')->once()->withArgs(fn ($request, $deal) => (int) $request->status_id === 2 && $deal->id === 1)->andReturn(response()->json(['success' => true]));
        });
        $this->patchJson('/api/index-status/deals/1', ['status' => '2'])->assertOk();
    }

    public function test_followup_meeting_and_ticket_changes_keep_comment_history(): void
    {
        foreach (['follow_ups', 'meetings', 'support_tickets'] as $name) {
            Schema::create($name, function (Blueprint $table) {
                $table->id(); $table->string('status'); $table->integer('created_by')->nullable(); $table->integer('updated_by')->nullable();
                $table->softDeletes(); $table->timestamps();
            });
        }
        foreach (['follow_up_status_histories' => 'follow_up_id', 'meeting_status_histories' => 'meeting_id'] as $name => $foreignKey) {
            Schema::create($name, function (Blueprint $table) use ($foreignKey) {
                $table->id(); $table->integer($foreignKey); $table->string('status'); $table->text('comment')->nullable(); $table->integer('updated_by'); $table->timestamps();
            });
        }
        Schema::create('module_status_histories', function (Blueprint $table) {
            $table->id(); $table->string('historable_type'); $table->integer('historable_id'); $table->string('status');
            $table->text('comment')->nullable(); $table->integer('updated_by'); $table->timestamps();
        });
        foreach ([['follow-ups', 'follow_ups', 'pending', 'completed', 'follow_up_status_histories'], ['meetings', 'meetings', 'scheduled', 'completed', 'meeting_status_histories'], ['tickets', 'support_tickets', 'Open', 'Resolved', 'module_status_histories']] as [$module, $table, $from, $to, $history]) {
            DB::table($table)->insert(['id' => 1, 'status' => $from, 'created_by' => 1]);
            $this->getJson("/api/index-status/$module/1")->assertOk()->assertJsonPath('requires_comment', true);
            $this->patchJson("/api/index-status/$module/1", ['status' => $to])->assertUnprocessable();
            $this->patchJson("/api/index-status/$module/1", ['status' => $to, 'comment' => 'Work completed'])->assertOk();
            $this->assertDatabaseHas($table, ['id' => 1, 'status' => $to]);
            $this->assertDatabaseHas($history, ['status' => $to, 'comment' => 'Work completed', 'updated_by' => 1]);
        }
        Schema::table('follow_ups', function (Blueprint $table) { $table->dateTime('follow_up_at')->nullable(); });
        $payload = ['status' => 'resheduled', 'comment' => 'Customer requested another time'];
        $this->patchJson('/api/index-status/follow-ups/1', $payload)->assertUnprocessable();
        $this->patchJson('/api/index-status/follow-ups/1', $payload + ['follow_up_at' => 'invalid'])->assertUnprocessable();
        $this->patchJson('/api/index-status/follow-ups/1', $payload + ['follow_up_at' => '2026-10-26T15:30'])->assertOk();
        $this->assertDatabaseHas('follow_ups', ['id' => 1, 'status' => 'resheduled', 'follow_up_at' => '2026-10-26 15:30:00']);
        $this->assertDatabaseHas('follow_up_status_histories', ['status' => 'resheduled', 'comment' => $payload['comment']]);
        $this->patchJson('/api/index-status/follow-ups/1', $payload + ['follow_up_at' => '2026-10-27T10:45'])->assertOk();
        $this->assertDatabaseHas('follow_ups', ['id' => 1, 'follow_up_at' => '2026-10-27 10:45:00']);
        $this->assertDatabaseCount('follow_up_status_histories', 3);
    }
}
