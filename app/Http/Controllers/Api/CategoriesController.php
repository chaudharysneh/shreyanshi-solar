<?php

namespace App\Http\Controllers\Api;

use App\Models\Categories;
use App\Models\Product;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Throwable;

class CategoriesController extends ApiBaseController
{
    public function index(Request $request)
    {
        $search = trim((string) $request->get('search', ''));
        $perPage = max(1, min((int) $request->get('per_page', 10), 100));

        $categories = Categories::query()
            ->when($search !== '', fn ($query) => $query->where('name', 'like', "%{$search}%"))
            ->latest()
            ->paginate($perPage)
            ->appends($request->query());

        // Map the collection to serialize each category
        $categories->setCollection($categories->getCollection()->map(fn (Categories $category) => $this->serialize($category)));

        return response()->json([
            'success' => true,
            'message' => 'Categories retrieved successfully.',
            'data' => $categories,
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), $this->rules(), $this->messages());

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('categories', 'public');
        }

        $category = Categories::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Category created successfully.',
            'data' => $this->serialize($category),
            'redirect' => route('categories.index'),
        ], 201);
    }

    public function show(Categories $category)
    {
        return response()->json([
            'success' => true,
            'message' => 'Category retrieved successfully.',
            'data' => $this->serialize($category),
        ]);
    }

    public function update(Request $request, Categories $category)
    {
        $validator = Validator::make($request->all(), $this->rules($category), $this->messages());

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();

        if ($request->hasFile('image')) {
            if ($category->image) {
                Storage::disk('public')->delete($category->image);
            }

            $data['image'] = $request->file('image')->store('categories', 'public');
        }

        $category->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Category updated successfully.',
            'data' => $this->serialize($category->fresh()),
            'redirect' => route('categories.index'),
        ]);
    }

    public function destroy(Categories $category)
    {
        if (Product::where('category_id', $category->id)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'This category is associated with products and cannot be deleted.',
            ], 422);
        }

        if ($category->image) {
            Storage::disk('public')->delete($category->image);
        }

        try {
            $category->delete();
        } catch (QueryException $exception) {
            $isConstraintError = (string) $exception->getCode() === '23000'
                || (int) ($exception->errorInfo[1] ?? 0) === 1451;

            if ($isConstraintError) {
                return response()->json([
                    'success' => false,
                    'message' => 'This category is associated with products and cannot be deleted.',
                ], 422);
            }

            return response()->json([
                'success' => false,
                'message' => 'Unable to delete category right now. Please try again.',
            ], 500);
        } catch (Throwable $exception) {
            return response()->json([
                'success' => false,
                'message' => 'Unable to delete category right now. Please try again.',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Category deleted successfully.',
        ]);
    }

    private function rules(?Categories $category = null): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('product_categories', 'name')->ignore($category?->id)->whereNull('deleted_at'),
            ],
            'image' => ['nullable', 'file', 'mimes:jpg,jpeg,png,gif,bmp,webp,avif,svg', 'max:2048'],
            'branch_id' => ['nullable', 'integer'],
        ];
    }

    private function messages(): array
    {
        return [
            'name.required' => 'Please enter category name.',
            'name.unique' => 'This category name already exists.',
            'image.mimes' => 'Please select a valid image. Allowed types: AVIF, WEBP, JPG, JPEG, PNG, GIF, BMP, SVG.',
            'image.max' => 'Please select an image smaller than 2MB.',
        ];
    }

    private function serialize(Categories $category): array
    {
        return [
            'id' => $category->id,
            'name' => $category->name,
            'image' => $category->image,
            'image_url' => $category->image && Storage::disk('public')->exists($category->image) 
                ? route('categories.image', $category->id) . '?v=' . ($category->updated_at ? $category->updated_at->timestamp : time())
                : null,
            'branch_id' => $category->branch_id,
            'created_at' => optional($category->created_at)?->toIso8601String(),
            'updated_at' => optional($category->updated_at)?->toIso8601String(),
        ];
    }
}
