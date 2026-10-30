<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\File;

class CacheClearProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Auto-clear view cache on every request
        $this->clearViewCache();
        $this->clearRouteCache();
    }

    /**
     * Clear view cache
     */
    private function clearViewCache(): void
    {
        $viewPath = storage_path('framework/views');
        
        if (File::isDirectory($viewPath)) {
            $files = File::files($viewPath);
            foreach ($files as $file) {
                if ($file->getFilename() !== '.gitkeep') {
                    try {
                        File::delete($file);
                    } catch (\Exception $e) {
                        // Silently fail
                    }
                }
            }
        }
    }

    /**
     * Clear route cache
     */
    private function clearRouteCache(): void
    {
        $cachePath = base_path('bootstrap/cache');
        
        $files = [
            'routes-v7.php',
            'config.php',
        ];
        
        foreach ($files as $file) {
            $filePath = $cachePath . '/' . $file;
            if (File::exists($filePath)) {
                try {
                    File::delete($filePath);
                } catch (\Exception $e) {
                    // Silently fail
                }
            }
        }
    }
}
