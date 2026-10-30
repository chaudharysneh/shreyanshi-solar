<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Update image paths from 'make/' to 'makes/' in category table
        DB::table('category')
            ->where('image', 'like', 'make/%')
            ->update([
                'image' => DB::raw("REPLACE(image, 'make/', 'makes/')")
            ]);

        // Move physical files from storage/app/public/make/ to storage/app/public/makes/
        $oldPath = storage_path('app/public/make');
        $newPath = storage_path('app/public/makes');

        if (is_dir($oldPath) && !is_dir($newPath)) {
            // Create new directory
            mkdir($newPath, 0755, true);
            
            // Move all files
            if ($handle = opendir($oldPath)) {
                while (false !== ($file = readdir($handle))) {
                    if ($file != "." && $file != "..") {
                        $oldFile = $oldPath . '/' . $file;
                        $newFile = $newPath . '/' . $file;
                        
                        if (is_file($oldFile)) {
                            rename($oldFile, $newFile);
                        }
                    }
                }
                closedir($handle);
                
                // Remove old directory if empty
                if (count(scandir($oldPath)) == 2) {
                    rmdir($oldPath);
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert image paths from 'makes/' back to 'make/' in category table
        DB::table('category')
            ->where('image', 'like', 'makes/%')
            ->update([
                'image' => DB::raw("REPLACE(image, 'makes/', 'make/')")
            ]);

        // Move physical files back from storage/app/public/makes/ to storage/app/public/make/
        $newPath = storage_path('app/public/makes');
        $oldPath = storage_path('app/public/make');

        if (is_dir($newPath) && !is_dir($oldPath)) {
            // Create old directory
            mkdir($oldPath, 0755, true);
            
            // Move all files back
            if ($handle = opendir($newPath)) {
                while (false !== ($file = readdir($handle))) {
                    if ($file != "." && $file != "..") {
                        $newFile = $newPath . '/' . $file;
                        $oldFile = $oldPath . '/' . $file;
                        
                        if (is_file($newFile)) {
                            rename($newFile, $oldFile);
                        }
                    }
                }
                closedir($handle);
                
                // Remove new directory if empty
                if (count(scandir($newPath)) == 2) {
                    rmdir($newPath);
                }
            }
        }
    }
};