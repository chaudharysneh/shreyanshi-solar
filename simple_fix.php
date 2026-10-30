<?php

echo "🚀 SIMPLE LIVE SERVER FIX\n";
echo "=========================\n\n";

// Simple fix script that works without Laravel bootstrap

echo "📋 Current Directory: " . getcwd() . "\n";
echo "📋 PHP Version: " . PHP_VERSION . "\n\n";

echo "🔧 FIXING STORAGE SYMLINK:\n";
echo "==========================\n";

$publicStorage = 'public/storage';
$sourceDir = 'storage/app/public';

// Step 1: Remove existing public/storage if it's not a symlink
if (file_exists($publicStorage)) {
    if (is_link($publicStorage)) {
        echo "✅ Symlink already exists\n";
    } else {
        echo "⚠️ Removing existing non-symlink public/storage\n";
        if (is_dir($publicStorage)) {
            // Remove directory recursively
            function removeDir($dir) {
                if (is_dir($dir)) {
                    $files = array_diff(scandir($dir), array('.', '..'));
                    foreach ($files as $file) {
                        $path = $dir . '/' . $file;
                        is_dir($path) ? removeDir($path) : unlink($path);
                    }
                    return rmdir($dir);
                }
                return false;
            }
            removeDir($publicStorage);
        } else {
            unlink($publicStorage);
        }
        echo "✅ Removed existing public/storage\n";
    }
}

// Step 2: Create symlink
if (!file_exists($publicStorage)) {
    $targetPath = realpath($sourceDir);
    
    if ($targetPath && function_exists('symlink')) {
        if (symlink($targetPath, $publicStorage)) {
            echo "✅ Symlink created successfully\n";
        } else {
            echo "❌ Symlink creation failed\n";
        }
    } else {
        echo "⚠️ Symlink not possible, using copy method\n";
        
        // Create directory structure
        if (!file_exists($publicStorage)) {
            mkdir($publicStorage, 0755, true);
        }
        
        if (!file_exists($publicStorage . '/makes')) {
            mkdir($publicStorage . '/makes', 0755, true);
        }
        
        // Copy all files from storage/app/public to public/storage
        function copyDirectory($source, $dest) {
            if (!is_dir($source)) return false;
            
            if (!is_dir($dest)) {
                mkdir($dest, 0755, true);
            }
            
            $files = scandir($source);
            $copiedCount = 0;
            
            foreach ($files as $file) {
                if ($file != '.' && $file != '..') {
                    $sourcePath = $source . '/' . $file;
                    $destPath = $dest . '/' . $file;
                    
                    if (is_dir($sourcePath)) {
                        copyDirectory($sourcePath, $destPath);
                    } else {
                        if (copy($sourcePath, $destPath)) {
                            $copiedCount++;
                        }
                    }
                }
            }
            
            return $copiedCount;
        }
        
        $copiedFiles = copyDirectory($sourceDir, $publicStorage);
        echo "✅ Copied $copiedFiles files to public/storage\n";
    }
}

echo "\n🧪 VERIFICATION:\n";
echo "================\n";

// Check if fix worked
$testFile = 'public/storage/makes/gQsOhAAKUu5EA9XOnLj89b3srdlXPV2TafS3vZUF.webp';
if (file_exists($testFile)) {
    echo "✅ Test image file accessible\n";
    echo "📊 File size: " . number_format(filesize($testFile)) . " bytes\n";
    
    // Generate URLs
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'your-domain.com';
    $baseUrl = $protocol . '://' . $host;
    
    echo "\n🔗 TEST THESE URLS:\n";
    echo "==================\n";
    echo "Direct storage: $baseUrl/storage/makes/gQsOhAAKUu5EA9XOnLj89b3srdlXPV2TafS3vZUF.webp\n";
    echo "Make route: $baseUrl/make/48/image\n";
    echo "API route: $baseUrl/api/make/48/image\n";
    
    echo "\n✅ SUCCESS! Images should now work!\n";
} else {
    echo "❌ Test image file not found\n";
    
    // List what's in storage
    echo "\n📁 Contents of storage/app/public:\n";
    if (is_dir($sourceDir)) {
        $contents = scandir($sourceDir);
        foreach ($contents as $item) {
            if ($item != '.' && $item != '..') {
                echo "   - $item\n";
            }
        }
    }
    
    echo "\n📁 Contents of public/storage:\n";
    if (is_dir($publicStorage)) {
        $contents = scandir($publicStorage);
        foreach ($contents as $item) {
            if ($item != '.' && $item != '..') {
                echo "   - $item\n";
            }
        }
    }
}

echo "\n🎯 NEXT STEPS:\n";
echo "==============\n";
echo "1. Test the direct storage URL first\n";
echo "2. If that works, test the route URLs\n";
echo "3. If routes don't work, check .htaccess and web server config\n";
echo "4. Clear any caches if available\n";

echo "\n✨ FIX COMPLETE!\n";