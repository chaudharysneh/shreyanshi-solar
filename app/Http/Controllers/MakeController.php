<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Support\Facades\Storage;

class MakeController extends Controller
{
    public function index()
    {
        return view('crm.makes.index');
    }

    public function image($id)
    {
        $category = Category::findOrFail($id);

        $imagePath = $this->resolveImagePath($category->image);

        if (!$imagePath) {
            abort(404, 'Image not found');
        }

        return response()->file($imagePath);
    }

    private function resolveImagePath(?string $imagePath): ?string
    {
        if (!$imagePath) {
            return null;
        }

        $normalizedPath = str_replace('\\', '/', trim($imagePath, '/'));
        $candidates = array_values(array_unique([
            $normalizedPath,
            preg_replace('#^make/#i', 'makes/', $normalizedPath),
            preg_replace('#^makes/#i', 'make/', $normalizedPath),
            'makes/' . basename($normalizedPath),
            'make/' . basename($normalizedPath),
        ]));

        foreach ($candidates as $candidate) {
            if (!$candidate) {
                continue;
            }

            $storageDiskPath = Storage::disk('public')->path($candidate);
            if (is_file($storageDiskPath)) {
                return $storageDiskPath;
            }

            $publicStoragePath = public_path('storage/' . $candidate);
            if (is_file($publicStoragePath)) {
                return $publicStoragePath;
            }
        }

        return null;
    }
}
