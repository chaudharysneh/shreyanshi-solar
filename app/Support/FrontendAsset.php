<?php

namespace App\Support;

class FrontendAsset
{
    private array $versions = [];

    public function url(string $path): string
    {
        $path = ltrim(str_replace('\\', '/', $path), '/');
        $prefix = trim((string) config('frontend_assets.public_prefix', ''), '/');
        $url = asset(($prefix !== '' ? $prefix.'/' : '').$path);

        if (!array_key_exists($path, $this->versions)) {
            $file = public_path($path);
            $this->versions[$path] = is_file($file) && is_readable($file)
                ? substr(hash_file('sha256', $file), 0, 16)
                : null;
        }

        return $url.($this->versions[$path] !== null ? '?v='.$this->versions[$path] : '');
    }
}
