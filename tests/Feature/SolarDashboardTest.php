<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\SolarDashboardService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class SolarDashboardTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        $this->travelTo(\Carbon\Carbon::parse('2026-10-24 12:00:00'));
        Schema::create('stages', function (Blueprint $table) { $table->id(); $table->string('name'); $table->integer('sort_order'); });
        Schema::create('leads', function (Blueprint $table) { $table->id(); $table->timestamps(); $table->softDeletes(); });
        Schema::create('currencies', function (Blueprint $table) { $table->id(); $table->string('code'); });
        Schema::create('invoices', function (Blueprint $table) {
            $table->id(); $table->integer('currency_id')->nullable(); $table->integer('customer_id')->nullable();
            $table->decimal('amount', 12, 2); $table->string('status'); $table->boolean('is_quotation')->default(false);
            $table->date('due_date')->nullable(); $table->timestamps(); $table->softDeletes();
        });
        Schema::create('customers', function (Blueprint $table) { $table->id(); $table->string('name'); $table->softDeletes(); });
        Schema::create('payments', function (Blueprint $table) { $table->id(); $table->integer('invoice_id'); $table->decimal('amount', 12, 2); });
        $user = new class extends User {
            public bool $metricsAllowed = true;
            public array $metricPermissions = ['view_invoices', 'view_leads'];
            public function isAdmin(): bool { return true; }
            public function can($abilities, $arguments = []) { return $this->metricsAllowed; }
            public function hasMatrixPermission(string $permission): bool { return $this->metricsAllowed && in_array($permission, $this->metricPermissions); }
        };
        $user->id = 1;
        $this->actingAs($user);
    }

    private function overview(array $query = []): array
    {
        $request = Request::create('/dashboard', 'GET', $query);
        $request->setUserResolver(fn () => auth()->user());
        return app(SolarDashboardService::class)->build($request);
    }

    public function test_balances_deduct_payments_exclude_paid_and_keep_currencies_separate(): void
    {
        DB::table('currencies')->insert([['id' => 1, 'code' => 'INR'], ['id' => 2, 'code' => 'USD']]);
        foreach ([[1, 1, 1000, 'unpaid'], [2, 2, 200, 'unpaid'], [3, 1, 900, 'paid'], [4, 1, 400, 'cancelled']] as [$id, $currency, $amount, $status]) {
            DB::table('invoices')->insert(['id' => $id, 'currency_id' => $currency, 'amount' => $amount, 'status' => $status, 'due_date' => '2026-10-20']);
        }
        DB::table('payments')->insert(['invoice_id' => 1, 'amount' => 250]);
        $data = $this->overview();
        $this->assertEquals(750, $data['balances']['INR']);
        $this->assertEquals(200, $data['balances']['USD']);
        $this->assertEquals(750, $data['overdueBalances']['INR']);
        $this->assertCount(2, $data['outstanding']);
    }

    public function test_custom_period_uses_equal_previous_period_for_comparison(): void
    {
        foreach (['2026-10-21', '2026-10-22', '2026-10-24', '2026-10-20'] as $date) DB::table('leads')->insert(['created_at' => $date.' 12:00:00']);
        $data = $this->overview(['solar_period' => 'custom', 'solar_from' => '2026-10-22', 'solar_to' => '2026-10-24']);
        $this->assertCount(2, $data['leads']);
        $this->assertEquals(2, $data['previousLeads']);
        $this->assertEquals(0, $data['change']);
    }

    public function test_restricted_modules_do_not_expose_metrics(): void
    {
        DB::table('leads')->insert(['created_at' => '2026-10-24 10:00:00']);
        auth()->user()->metricsAllowed = false;
        $data = $this->overview();
        $this->assertCount(0, $data['leads']);
        $this->assertCount(0, $data['balances']);
        $this->assertCount(0, $data['actions']);
    }

    public function test_filters_are_saved_in_session_and_redirect_to_a_clean_url(): void
    {
        $request = Request::create('/dashboard/filters', 'POST', ['solar_period' => 'today']);
        $request->setLaravelSession(app('session')->driver());
        $response = app(\App\Http\Controllers\DashboardController::class)->saveFilters($request);
        $this->assertSame(url('/dashboard'), $response->getTargetUrl());
        $this->assertSame(['solar_period' => 'today'], $request->session()->get('dashboard.solar_filters'));
    }

    public function test_all_actions_are_available_for_scrolling_beyond_five_rows(): void
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->id(); $table->string('title'); $table->string('status'); $table->date('due_date'); $table->timestamps();
        });
        for ($day = 1; $day <= 7; $day++) DB::table('tasks')->insert(['title' => 'Task '.$day, 'status' => 'pending', 'due_date' => '2026-10-0'.$day]);
        auth()->user()->metricPermissions = ['view_tasks'];
        $data = $this->overview();
        $this->assertCount(7, $data['actions']);
        $this->assertSame(7, $data['actionCount']);
        $this->assertSame('Task 1', $data['actions']->first()['title']);
    }

    public function test_daily_activity_groups_by_the_correct_dates_and_fills_empty_days(): void
    {
        foreach (['follow_ups' => 'follow_up_at', 'meetings' => 'scheduled_at'] as $name => $column) {
            Schema::create($name, function (Blueprint $table) use ($column) {
                $table->id(); $table->timestamp($column); $table->timestamps(); $table->softDeletes();
            });
        }
        DB::table('leads')->insert(['created_at' => '2026-10-24 08:00:00']);
        DB::table('follow_ups')->insert(['created_at' => '2026-10-20 08:00:00', 'follow_up_at' => '2026-10-24 10:00:00']);
        DB::table('meetings')->insert(['created_at' => '2026-10-20 08:00:00', 'scheduled_at' => '2026-10-23 14:00:00']);
        auth()->user()->metricPermissions = ['view_leads', 'view_followups', 'view_meetings'];
        $request = Request::create('/dashboard');
        $request->setUserResolver(fn () => auth()->user());
        $data = app(SolarDashboardService::class)->dailyActivity($request);
        $this->assertCount(30, $data['labels']);
        $this->assertCount(3, $data['datasets']);
        $this->assertSame(1, $data['datasets'][0]['values'][29]);
        $this->assertSame(1, $data['datasets'][1]['values'][29]);
        $this->assertSame(1, $data['datasets'][2]['values'][28]);
        $this->assertSame(0, $data['datasets'][2]['values'][29]);
        auth()->user()->metricsAllowed = false;
        $this->assertSame([], app(SolarDashboardService::class)->dailyActivity($request)['datasets']);
    }

    public function test_invalid_custom_range_is_rejected(): void
    {
        $this->expectException(ValidationException::class);
        $this->overview(['solar_period' => 'custom', 'solar_from' => '2026-10-24', 'solar_to' => '2026-10-20']);
    }
}
