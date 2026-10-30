<?php

namespace Tests\Feature;

use App\Models\User;
use App\Http\Middleware\VerifyCsrfToken;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class LoginRedirectTest extends TestCase
{
    public function test_login_ignores_stale_logo_destination_and_opens_dashboard(): void
    {
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        Schema::create('users', function (Blueprint $table) {
            $table->id(); $table->string('name'); $table->string('email'); $table->string('password'); $table->boolean('is_active'); $table->rememberToken();
        });
        DB::table('users')->insert(['id'=>1, 'name'=>'Test User', 'email'=>'login@example.test', 'password'=>Hash::make('test-password'), 'is_active'=>true]);
        $this->withSession(['url.intended'=>url('/profile/company-logo-image')])
            ->post('/login', ['email'=>'login@example.test', 'password'=>'test-password'])
            ->assertRedirect(route('dashboard'))->assertSessionMissing('url.intended')
            ->assertSessionHas('dashboard.show_daily_overview', true);
        $this->assertAuthenticatedAs(User::find(1));
    }

    public function test_expired_login_gets_fresh_form_without_replaying_password(): void
    {
        $middleware = new class($this->app, $this->app['encrypter']) extends VerifyCsrfToken {
            protected function runningUnitTests() { return false; }
            protected function tokensMatch($request) { return false; }
        };
        $request = Request::create('/login', 'POST', ['email'=>'login@example.test', 'password'=>'must-not-be-flashed']);
        $request->setLaravelSession($this->app['session.store']);
        $request->setRouteResolver(fn () => $this->app['router']->getRoutes()->match($request));
        $response = $middleware->handle($request, fn () => $this->fail('Expired credentials must not be processed.'));
        $this->assertSame(route('login'), $response->getTargetUrl());
        $this->assertSame('login@example.test', $request->session()->getOldInput('email'));
        $this->assertNull($request->session()->getOldInput('password'));
        $this->assertTrue($request->session()->get('errors')->has('email'));
    }

    public function test_other_posts_still_reject_invalid_csrf_tokens(): void
    {
        $middleware = new class($this->app, $this->app['encrypter']) extends VerifyCsrfToken {
            protected function runningUnitTests() { return false; }
            protected function tokensMatch($request) { return false; }
        };
        $request = Request::create('/logout', 'POST');
        $request->setLaravelSession($this->app['session.store']);
        $request->setRouteResolver(fn () => $this->app['router']->getRoutes()->match($request));
        $this->expectException(\Illuminate\Session\TokenMismatchException::class);
        $middleware->handle($request, fn () => response('Unexpected'));
    }
}
