<?php
/**
 * Auto Cache Clearer
 * This file automatically clears Laravel caches when accessed
 * Access: https://your-domain.com/clear-cache.php
 */

// Define the base path
$basePath = __DIR__ . '/../';

// Load Laravel
require $basePath . 'bootstrap/autoload.php';
$app = require $basePath . 'bootstrap/app.php';

// Get the kernel
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

// Create a request
$request = Illuminate\Http\Request::capture();

// Clear all caches
try {
    // Clear config cache
    if (file_exists($basePath . 'bootstrap/cache/config.php')) {
        unlink($basePath . 'bootstrap/cache/config.php');
        echo "✓ Config cache cleared<br>";
    }
    
    // Clear route cache
    if (file_exists($basePath . 'bootstrap/cache/routes-v7.php')) {
        unlink($basePath . 'bootstrap/cache/routes-v7.php');
        echo "✓ Route cache cleared<br>";
    }
    
    // Clear view cache
    $viewPath = $basePath . 'storage/framework/views';
    if (is_dir($viewPath)) {
        $files = glob($viewPath . '/*');
        foreach ($files as $file) {
            if (is_file($file) && $file !== $viewPath . '/.gitkeep') {
                unlink($file);
            }
        }
        echo "✓ View cache cleared<br>";
    }
    
    // Clear application cache
    $cachePath = $basePath . 'bootstrap/cache';
    if (is_dir($cachePath)) {
        $files = glob($cachePath . '/*.php');
        foreach ($files as $file) {
            if (basename($file) !== '.gitkeep') {
                @unlink($file);
            }
        }
    }
    
    echo "<br><strong>✅ All caches cleared successfully!</strong><br>";
    echo "Refresh your application now.";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>
