<?php

namespace Tests\Feature;

use App\Support\FrontendAsset;
use Illuminate\Routing\UrlGenerator;
use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class FrontendAssetTest extends TestCase
{
    private string $originalPublicPath;
    private string $assetDirectory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->originalPublicPath = public_path();
        $this->assetDirectory = sys_get_temp_dir().'/crm-assets-'.bin2hex(random_bytes(6));
        mkdir($this->assetDirectory.'/js', 0777, true);
        file_put_contents($this->assetDirectory.'/js/example.js', 'console.log("first");');
        $this->app->usePublicPath($this->assetDirectory);
        config(['frontend_assets.public_prefix' => '']);
        app('url')->forceRootUrl('https://crm.example.test');
        app('url')->forceScheme('https');
    }

    protected function tearDown(): void
    {
        $this->app->usePublicPath($this->originalPublicPath);
        unlink($this->assetDirectory.'/js/example.js');
        rmdir($this->assetDirectory.'/js');
        rmdir($this->assetDirectory);
        parent::tearDown();
    }

    public function test_versions_follow_content_even_when_timestamps_are_preserved(): void
    {
        $file = $this->assetDirectory.'/js/example.js';
        $timestamp = filemtime($file);
        $first = (new FrontendAsset)->url('js/example.js');
        file_put_contents($file, 'console.log("second");');
        touch($file, $timestamp);
        $second = (new FrontendAsset)->url('js/example.js');
        $this->assertNotSame($first, $second);
        $this->assertSame($second, (new FrontendAsset)->url('js/example.js'));
        $this->assertStringStartsWith('https://crm.example.test/js/example.js?v=', $first);
    }

    public function test_prefix_comes_from_configuration_and_helper_renders_in_blade(): void
    {
        config(['frontend_assets.public_prefix' => '/public/']);
        $url = crm_asset('/js/example.js');
        $this->assertStringStartsWith('https://crm.example.test/public/js/example.js?v=', $url);
        $this->assertSame('<script src="'.$url.'"></script>', Blade::render('<script src="{{ crm_asset("js/example.js") }}"></script>'));
    }

    public function test_missing_files_do_not_crash_page_rendering(): void
    {
        $this->assertSame('https://crm.example.test/js/missing.js', crm_asset('js/missing.js'));
    }

    public function test_configured_asset_origin_is_preserved(): void
    {
        $generator = new UrlGenerator(app('router')->getRoutes(), request(), 'https://cdn.example.test');
        $this->app->instance('url', $generator);
        $this->assertStringStartsWith('https://cdn.example.test/js/example.js?v=', (new FrontendAsset)->url('js/example.js'));
    }
}
