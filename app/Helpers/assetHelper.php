<?php

if (!function_exists('crm_asset')) {
    function crm_asset(string $path): string
    {
        return app(\App\Support\FrontendAsset::class)->url($path);
    }
}
