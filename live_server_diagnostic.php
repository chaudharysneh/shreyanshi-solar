<?php

echo "🌐 LIVE SERVER DIAGNOSTIC & AUTO-FIX\n";
echo "====================================\n\n";

// This script will run on live server to diagnose and fix issues

echo "📋 SYSTEM INFORMATION:\n";
echo "======================\n";
echo "PHP Version: " . PHP_VERSION . "\n";
echo "Server Software: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown') . "\n";
echo "Document Root: " . ($_SERVER['DOCUMENT_ROOT'] ?? 'Unknown') . "\n";
echo "Current Directory: " . getcwd() . "\n";
echo "Script Path: " . __FILE__ . "\n";

echo "\n🔍 LARAVEL DETECTION:\n";
echo "=====================\n";

if (file_exists('artisan')) {
    echo "✅ Laravel detected (artisan file found)\n";
    
    // Try to bootstrap Laravel
    try {
        require_once 'vendor/autoload.php';
        $app = require_once 'bootstrap/app.php';
        $app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
        echo "✅ Laravel bootstrapped successfully\n";
        
        // Now we can use Laravel features
        $laravelReady = true;
    } catch (Exception $e) {
        echo "❌ Laravel bootstrap failed: " . $e->getMessage() . "\n";
        $laravelReady = false;
    }
} else {
    echo "❌ Laravel not detected (no artisan file)\n";
    $laravelReady = false;
}

echo "\n📁 DIRECTORY STRUCTURE CHECK:\n";
echo "=============================\n";

$requiredDirs = [
    'storage' => 'Storage directory',
    'storage/app' => 'Storage app directory',
    'storage/app/public' => 'Storage public directory',
    'storage/app/public/makes' => 'Makes directory',
    'public' => 'Public directory',
    'app' => 'App directory',
    'app/Http' => 'HTTP directory',
    'app/Http/Controllers' => 'Controllers directory',
    'app/Http/Controllers/Api' => 'API Controllers directory',
    'routes' => 'Routes directory',
];

foreach ($requiredDirs as $dir => $desc) {
    $exists = is_dir($dir);
    $status = $exists ? "✅" : "❌";
    echo "$status $desc ($dir)\n";
    
    if ($exists) {
        $permissions = substr(sprintf('%o', fileperms($dir)), -4);
        echo "   Permissions: $permissions\n";
    }
}

echo "\n📄 REQUIRED FILES CHECK:\n";
echo "========================\n";

$requiredFiles = [
    'routes/web.php' => 'Web routes',
    'routes/api.php' => 'API routes', 
    'app/Http/Controllers/MakeController.php' => 'Web MakeController',
    'app/Http/Controllers/Api/MakeController.php' => 'API MakeController',
    '.env' => 'Environment file',
];

foreach ($requiredFiles as $file => $desc) {
    $exists = file_exists($file);
    $status = $exists ? "✅" : "❌";
    echo "$status $desc ($file)\n";
    
    if ($exists) {
        $size = filesize($file);
        $permissions = substr(sprintf('%o', fileperms($file)), -4);
        echo "   Size: " . number_format($size) . " bytes, Permissions: $permissions\n";
    }
}

echo "\n🔗 STORAGE SYMLINK CHECK:\n";
echo "=========================\n";

$publicStorage = 'public/storage';
$targetPath = '../storage/app/public';

if (file_exists($publicStorage)) {
    if (is_link($publicStorage)) {
        $linkTarget = readlink($publicStorage);
        echo "✅ Symlink exists: $publicStorage -> $linkTarget\n";
        
        $targetExists = file_exists($publicStorage . '/makes');
        echo "Target accessible: " . ($targetExists ? "✅" : "❌") . "\n";
    } else {
        echo "⚠️ public/storage exists but is not a symlink\n";
        echo "Type: " . (is_dir($publicStorage) ? "Directory" : "File") . "\n";
    }
} else {
    echo "❌ public/storage does not exist\n";
}

echo "\n🔧 AUTO-FIX ATTEMPTS:\n";
echo "=====================\n";

// Attempt 1: Create symlink using Laravel command
if ($laravelReady) {
    echo "Attempt 1: Laravel storage:link command\n";
    try {
        \Illuminate\Support\Facades\Artisan::call('storage:link');
        echo "✅ Laravel storage:link executed\n";
    } catch (Exception $e) {
        echo "❌ Laravel storage:link failed: " . $e->getMessage() . "\n";
    }
}

// Attempt 2: Manual symlink creation
echo "\nAttempt 2: Manual symlink creation\n";
if (file_exists($publicStorage) && !is_link($publicStorage)) {
    // Remove existing file/directory
    if (is_dir($publicStorage)) {
        echo "Removing existing directory: $publicStorage\n";
        rmdir($publicStorage);
    } else {
        echo "Removing existing file: $publicStorage\n";
        unlink($publicStorage);
    }
}

if (!file_exists($publicStorage)) {
    if (function_exists('symlink')) {
        $target = realpath('storage/app/public');
        if (symlink($target, $publicStorage)) {
            echo "✅ Manual symlink created successfully\n";
        } else {
            echo "❌ Manual symlink creation failed\n";
        }
    } else {
        echo "❌ symlink() function not available\n";
    }
}

// Attempt 3: File copy method
if (!file_exists($publicStorage) || !is_link($publicStorage)) {
    echo "\nAttempt 3: File copy method\n";
    
    if (!file_exists($publicStorage)) {
        mkdir($publicStorage, 0755, true);
        echo "Created public/storage directory\n";
    }
    
    if (!file_exists($publicStorage . '/makes')) {
        mkdir($publicStorage . '/makes', 0755, true);
        echo "Created public/storage/makes directory\n";
    }
    
    // Copy files
    $sourceDir = 'storage/app/public/makes';
    $targetDir = 'public/storage/makes';
    
    if (is_dir($sourceDir)) {
        $files = glob($sourceDir . '/*');
        $copiedCount = 0;
        
        foreach ($files as $file) {
            if (is_file($file)) {
                $filename = basename($file);
                $targetFile = $targetDir . '/' . $filename;
                
                if (copy($file, $targetFile)) {
                    $copiedCount++;
                }
            }
        }
        
        echo "✅ Copied $copiedCount files to public/storage/makes\n";
    }
}

echo "\n🧪 FINAL VERIFICATION:\n";
echo "======================\n";

// Test file access
$testFile = 'public/storage/makes/gQsOhAAKUu5EA9XOnLj89b3srdlXPV2TafS3vZUF.webp';
if (file_exists($testFile)) {
    echo "✅ Test image file accessible\n";
    echo "File size: " . number_format(filesize($testFile)) . " bytes\n";
    
    // Generate test URLs
    $baseUrl = 'https://' . $_SERVER['HTTP_HOST'];
    echo "Test URLs:\n";
    echo "- Direct: $baseUrl/storage/makes/gQsOhAAKUu5EA9XOnLj89b3srdlXPV2TafS3vZUF.webp\n";
    echo "- Route: $baseUrl/make/48/image\n";
    echo "- API: $baseUrl/api/make/48/image\n";
} else {
    echo "❌ Test image file not accessible\n";
}

// Database check if Laravel is ready
if ($laravelReady) {
    echo "\n📊 DATABASE CHECK:\n";
    echo "==================\n";
    
    try {
        $categoryCount = \App\Models\Category::count();
        echo "✅ Database connected, categories: $categoryCount\n";
        
        $cat48 = \App\Models\Category::find(48);
        if ($cat48) {
            echo "✅ Category 48 exists: {$cat48->name}\n";
            echo "Image path: {$cat48->image}\n";
        } else {
            echo "❌ Category 48 not found\n";
        }
    } catch (Exception $e) {
        echo "❌ Database error: " . $e->getMessage() . "\n";
    }
}

echo "\n✨ DIAGNOSTIC COMPLETE\n";
echo "======================\n";
echo "If test URLs work, the issue is resolved!\n";
echo "If not, check web server configuration and file permissions.\n";