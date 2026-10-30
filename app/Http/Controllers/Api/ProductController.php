<?php

namespace App\Http\Controllers\Api;

use App\Models\Product;
use App\Models\ProductInventory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ProductController extends ApiBaseController
{
    public function index(Request $request)
    {
        $search = trim((string) $request->get('search', ''));
        $perPage = max(1, min((int) $request->get('per_page', 10), 100));

        $products = Product::with(['category', 'creator'])
            ->when($search !== '', fn ($query) => $query->where(fn ($query) => $query->where('name', 'like', "%{$search}%")
                ->orWhere('serial_no', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%")
                ->orWhereHas('category', fn ($q) => $q->where('name', 'like', "%{$search}%"))))
            ->tap(fn ($query) => \App\Support\ListFilters::apply($query, $request, ['category_id','quantity'], ['created_at']))
            ->latest()
            ->paginate($perPage)
            ->appends($request->query());

        $products->getCollection()->transform(fn (Product $product) => $this->serialize($product));

        return response()->json([
            'success' => true,
            'message' => 'Products retrieved successfully.',
            'data' => $products,
        ]);
    }

    public function store(Request $request)
    {
        $this->normalizeCategoryId($request);

        $validator = Validator::make($request->all(), $this->rules(), $this->messages());

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();

        $product = Product::create($data);

        if ($request->has('custom_fields')) {
            $product->saveCustomFields($request->get('custom_fields'));
        }

        // Create initial inventory record with type "create"
        ProductInventory::create([
            'product_id' => $product->id,
            'initial_stock' => $product->quantity,
            'current_stock' => $product->quantity,
            'type' => 'create',
            'date' => now()->toDateString(),
            'created_by' => auth()->id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Product created successfully.',
            'data' => $this->serialize($product->fresh(['category', 'creator'])),
            'redirect' => route('products.index'),
        ], 201);
    }

    public function show(Product $product)
    {
        $product->load(['category', 'creator']);

        return response()->json([
            'success' => true,
            'message' => 'Product retrieved successfully.',
            'data' => $this->serialize($product),
        ]);
    }

    public function update(Request $request, Product $product)
    {
        $this->normalizeCategoryId($request);

        $validator = Validator::make($request->all(), $this->rules($product), $this->messages());

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();

        // Check if quantity is being changed
        $newQuantity = $data['quantity'] ?? $product->quantity;

        $product->update($data);

        // Create an update inventory record (keep all old history)
        ProductInventory::create([
            'product_id' => $product->id,
            'initial_stock' => 0,
            'current_stock' => $newQuantity,
            'type' => 'update',
            'date' => now()->toDateString(),
            'created_by' => auth()->id(),
        ]);

        if ($request->has('custom_fields')) {
            $product->saveCustomFields($request->get('custom_fields'));
        }

        return response()->json([
            'success' => true,
            'message' => 'Product updated successfully.',
            'data' => $this->serialize($product->fresh(['category', 'creator'])),
            'redirect' => route('products.index'),
        ]);
    }

    public function destroy(Product $product)
    {
        $product->delete();

        return response()->json([
            'success' => true,
            'message' => 'Product deleted successfully.',
        ]);
    }

    public function getBySerialNo($serialNo)
    {
        $product = Product::where('serial_no', $serialNo)
            ->whereNull('deleted_at')
            ->with(['category', 'creator'])
            ->first();

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found.',
                'data' => null,
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Product retrieved successfully.',
            'data' => $this->serialize($product),
        ]);
    }

    public function import(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'import_file' => ['required', 'file', 'mimes:csv'],
        ], [
            'import_file.required' => 'Please select an import file.',
            'import_file.file' => 'Please upload a valid import file.',
            'import_file.mimes' => 'Please upload a CSV file.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Import functionality coming soon.',
        ]);
    }

    private function rules(?Product $product = null): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'category_id' => ['required', 'exists:categories,id'],
            'quantity' => ['required', 'integer', 'min:0'],
            'availability' => ['nullable', 'in:in_stock,out_of_stock'],
            'serial_no' => ['nullable', 'string', 'max:255', Rule::unique('products', 'serial_no')->ignore($product?->id)->whereNull('deleted_at')],
            'status' => ['nullable', 'in:active,inactive'],
            'description' => ['nullable', 'string'],
        ];
    }

    private function normalizeCategoryId(Request $request): void
    {
        $categoryValue = trim((string) $request->input('category_id', ''));

        if ($categoryValue === '') {
            return;
        }

        if (ctype_digit($categoryValue)) {
            if (DB::table('categories')->where('id', $categoryValue)->exists()) {
                return;
            }

            $legacyCategory = DB::table('product_categories')
                ->where('id', $categoryValue)
                ->first();

            if (!$legacyCategory) {
                return;
            }

            $categoryValue = $legacyCategory->name;
        }

        $category = DB::table('categories')
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($categoryValue)])
            ->whereNull('deleted_at')
            ->first();

        if (!$category) {
            $categoryId = DB::table('categories')->insertGetId([
                'name' => $categoryValue,
                'created_by' => Auth::id(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            $categoryId = $category->id;
        }

        $request->merge([
            'category_id' => $categoryId,
        ]);
    }

    private function messages(): array
    {
        return [
            'name.required' => 'Please fill out the product name!',
            'category_id.required' => 'Please select a valid category!',
            'category_id.exists' => 'Please select a valid category!',
            'quantity.required' => 'Please fill out the product quantity!',
            'quantity.integer' => 'Please enter a valid quantity.',
        ];
    }

    private function serialize(Product $product): array
    {
        // Get the latest inventory record for current stock
        $inventory = $product->inventories()->latest()->first();
        $currentStock = $inventory?->current_stock ?? $product->quantity;

        return [
            'id' => $product->id,
            'name' => $product->name,
            'serial_no' => $product->serial_no,
            'category_id' => $product->category_id,
            'category' => $product->category ? [
                'id' => $product->category->id,
                'name' => $product->category->name,
            ] : null,
            'quantity' => $product->quantity,
            'current_stock' => $currentStock,
            'status' => $product->status,
            'availability' => $product->availability,
            'description' => $product->description,
            'created_at' => optional($product->created_at)?->toIso8601String(),
            'updated_at' => optional($product->updated_at)?->toIso8601String(),
        ];
    }
}
