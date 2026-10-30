<?php

namespace App\Http\Controllers;

use App\Models\BomProduct;
use App\Models\Category;
use App\Models\Tax;
use App\Models\Technology;
use App\Models\Warranty;
use Illuminate\Support\Facades\Storage;

class BomProductController extends Controller
{
    public function index()
    {
        return view('crm.bom.index', [
            'categories' => Category::query()->orderBy('name')->get(),
            'technologies' => Technology::query()->orderBy('title')->get(),
            'warranties' => Warranty::query()->orderBy('title')->get(),
        ]);
    }

    public function create()
    {
        return view('crm.bom.create', $this->formData());
    }

    public function show(BomProduct $bomProduct)
    {
        $bomProduct->load(['category', 'technology', 'warranty', 'creator']);

        return view('crm.bom.show', compact('bomProduct'));
    }

    public function edit(BomProduct $bomProduct)
    {
        $bomProduct->load(['category', 'technology', 'warranty']);

        return view('crm.bom.edit', array_merge($this->formData(), compact('bomProduct')));
    }

    public function image(BomProduct $bomProduct)
    {
        $imagePath = $this->resolveImagePath($bomProduct->image);

        if (!$imagePath) {
            abort(404);
        }

        return response()->file($imagePath);
    }

    private function formData(): array
    {
        return [
            'categories' => Category::query()->orderBy('name')->get(),
            'technologies' => Technology::query()->orderBy('title')->get(),
            'warranties' => Warranty::query()->orderBy('title')->get(),
            'taxes' => Tax::active()->orderBy('name')->orderBy('rate')->get(),
        ];
    }

    private function resolveImagePath(?string $imagePath): ?string
    {
        if (!$imagePath) {
            return null;
        }

        $normalizedPath = str_replace('\\', '/', trim($imagePath, '/'));
        $filename = basename($normalizedPath);
        $candidates = array_values(array_unique([
            $normalizedPath,
            preg_replace('#^product/#i', 'products/', $normalizedPath),
            preg_replace('#^products/#i', 'product/', $normalizedPath),
            preg_replace('#^bom-product/#i', 'bom-products/', $normalizedPath),
            preg_replace('#^bom-products/#i', 'bom-product/', $normalizedPath),
            'bom-products/' . $filename,
            'bom-product/' . $filename,
            'products/' . $filename,
            'product/' . $filename,
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
