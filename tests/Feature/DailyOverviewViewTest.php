<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

class DailyOverviewViewTest extends TestCase
{
    public function test_overview_renders_with_and_without_finance_access(): void
    {
        $user = new class extends User {
            public bool $financeAllowed = true;
            public function hasMatrixPermission(string $permission): bool { return $this->financeAllowed; }
        };
        $user->id = 1;
        $this->actingAs($user);

        foreach ([true, false] as $allowed) {
            $user->financeAllowed = $allowed;
            $html = view('dashboard.partials.daily-overview', [
                'solar' => ['actions' => collect(), 'outstanding' => collect(), 'balances' => collect()],
                'bankReceivables' => collect(),
                'showDailyOverview' => true,
                'formatDashboardAmount' => fn ($amount) => number_format($amount, 2),
            ])->render();

            $this->assertStringContainsString('No meetings scheduled today.', $html);
            $this->assertStringContainsString('No follow-ups scheduled today.', $html);
            $this->assertStringContainsString('No overdue follow-ups or tasks due.', $html);
            $this->assertSame($allowed, str_contains($html, 'Bank receivables'));
        }
    }
}
