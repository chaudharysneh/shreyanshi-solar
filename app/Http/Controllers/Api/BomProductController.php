<?php

namespace App\Http\Controllers\Api;

use App\Models\BomProduct;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class BomProductController extends ApiBaseController
{
    public function index(Request $request)
    {
        $search = trim((string) $request->get('search', ''));

        $products = BomProduct::query()
            ->with(['categories', 'technology', 'warranty', 'creator'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('product_name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhereHas('categories', fn ($related) => $related->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('technology', fn ($related) => $related->where('title', 'like', "%{$search}%"))
                        ->orWhereHas('warranty', fn ($related) => $related->where('title', 'like', "%{$search}%"));
                });
            })
            ->tap(function ($query) use ($request) {
                \App\Support\ListFilters::apply($query, $request, ['technology_id', 'warranty_id'], ['created_at']);
                $request->validate(['category_id' => ['nullable', 'integer']]);
                if ($request->filled('category_id')) {
                    $query->whereHas('categories', fn ($q) => $q->whereKey($request->integer('category_id')));
                }
            })
            ->latest()
            ->paginate($request->integer('per_page', 10))
            ->withQueryString();

        return response()->json([
            'success' => true,
            'message' => 'BOM products retrieved successfully.',
            'data' => $products,
        ]);
    }

    public function store(Request $request)
    {
        // Normalize category_id[] to category_id for validation
        $input = $request->all();
        if ($request->has('category_id') && is_array($request->input('category_id'))) {
            $input['category_id'] = $request->input('category_id');
        }

        \Log::info('BOM Store Request', [
            'input_keys' => array_keys($input),
            'category_id' => $input['category_id'] ?? null,
            'product_name' => $input['product_name'] ?? null,
        ]);

        $validator = Validator::make(
            $input,
            $this->rules(null, $request->boolean('quick_bom')),
            $this->messages(true)
        );

        if ($validator->fails()) {
            \Log::warning('BOM Store Validation Failed', ['errors' => $validator->errors()->toArray()]);
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();
        $data['user_id'] = $data['user_id'] ?? Auth::id();
        $categoryIds = $data['category_id'] ?? [];
        
        \Log::info('BOM Store Processing', [
            'category_ids' => $categoryIds,
            'user_id' => $data['user_id'],
            'product_name' => $data['product_name'],
        ]);
        
        unset($data['category_id']);

        // Check for duplicate submission (same product name, user, and image within last 5 seconds)
        $recentDuplicate = BomProduct::where('product_name', $data['product_name'])
            ->where('user_id', $data['user_id'])
            ->where('created_at', '>=', now()->subSeconds(5))
            ->first();

        if ($recentDuplicate) {
            \Log::warning('BOM Store Duplicate Detected', ['product_id' => $recentDuplicate->id]);
            return response()->json([
                'success' => false,
                'message' => 'This BOM product was just created. Please wait a moment before creating another.',
                'data' => $recentDuplicate->fresh(['categories', 'technology', 'warranty', 'creator']),
                'redirect' => route('bom-products.index'),
            ], 409); // 409 Conflict
        }

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('bom-products', 'public');
        }

        $product = BomProduct::create($data);
        
        \Log::info('BOM Product Created', ['product_id' => $product->id, 'category_count' => count($categoryIds)]);
        
        if (!empty($categoryIds)) {
            try {
                $product->categories()->sync($categoryIds);
                \Log::info('BOM Categories Synced', ['product_id' => $product->id, 'categories' => $categoryIds]);
            } catch (\Exception $e) {
                \Log::error('BOM Categories Sync Failed', [
                    'product_id' => $product->id,
                    'categories' => $categoryIds,
                    'error' => $e->getMessage(),
                ]);
                
                // Delete the product if category sync fails
                $product->delete();
                
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to link categories to product. Please try again.',
                    'errors' => ['category_id' => ['Failed to save category associations']],
                ], 422);
            }
        }

        $freshProduct = $product->fresh(['categories', 'technology', 'warranty', 'creator']);
        
        \Log::info('BOM Store Success', [
            'product_id' => $freshProduct->id,
            'categories_count' => $freshProduct->categories->count(),
            'categories' => $freshProduct->categories->pluck('name')->toArray(),
        ]);

        // ── Email: Admin Notification (staff activity) ─────────────────
        send_admin_notification('BOM Product', 'Created', $freshProduct->product_name, []);

        return response()->json([
            'success' => true,
            'message' => 'BOM product created successfully.',
            'data' => $freshProduct,
            'redirect' => route('bom-products.index'),
        ], 201);
    }

    public function show(BomProduct $bomProduct)
    {
        return response()->json([
            'success' => true,
            'message' => 'BOM product retrieved successfully.',
            'data' => $bomProduct->load(['categories', 'technology', 'warranty', 'creator']),
        ]);
    }

    public function update(Request $request, BomProduct $bomProduct)
    {
        // Normalize category_id[] to category_id for validation
        $input = $request->all();
        if ($request->has('category_id') && is_array($request->input('category_id'))) {
            $input['category_id'] = $request->input('category_id');
        }

        \Log::info('BOM Update Request', [
            'product_id' => $bomProduct->id,
            'category_id' => $input['category_id'] ?? null,
        ]);

        $validator = Validator::make($input, $this->rules($bomProduct), $this->messages(false));

        if ($validator->fails()) {
            \Log::warning('BOM Update Validation Failed', ['errors' => $validator->errors()->toArray()]);
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();
        $categoryIds = $data['category_id'] ?? [];
        
        \Log::info('BOM Update Processing', [
            'product_id' => $bomProduct->id,
            'category_ids' => $categoryIds,
        ]);
        
        unset($data['category_id']);

        if ($request->hasFile('image')) {
            if ($bomProduct->image) {
                Storage::disk('public')->delete($bomProduct->image);
            }

            $data['image'] = $request->file('image')->store('bom-products', 'public');
        }

        $bomProduct->update($data);
        
        if (!empty($categoryIds)) {
            try {
                $bomProduct->categories()->sync($categoryIds);
                \Log::info('BOM Categories Synced on Update', ['product_id' => $bomProduct->id, 'categories' => $categoryIds]);
            } catch (\Exception $e) {
                \Log::error('BOM Categories Sync Failed on Update', [
                    'product_id' => $bomProduct->id,
                    'categories' => $categoryIds,
                    'error' => $e->getMessage(),
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to link categories to product. Please try again.',
                    'errors' => ['category_id' => ['Failed to save category associations']],
                ], 422);
            }
        }

        $freshProduct = $bomProduct->fresh(['categories', 'technology', 'warranty', 'creator']);
        
        \Log::info('BOM Update Success', [
            'product_id' => $freshProduct->id,
            'categories_count' => $freshProduct->categories->count(),
        ]);

        // ── Email: Admin Notification (staff activity) ─────────────────
        send_admin_notification('BOM Product', 'Updated', $freshProduct->product_name, []);

        return response()->json([
            'success' => true,
            'message' => 'BOM product updated successfully.',
            'data' => $freshProduct,
            'redirect' => route('bom-products.index'),
        ]);
    }

    public function destroy(BomProduct $bomProduct)
    {
        $productName = $bomProduct->product_name;
        $bomProduct->delete();

        send_admin_notification('BOM Product', 'Deleted', $productName ?? 'N/A', []);

        return response()->json([
            'success' => true,
            'message' => 'BOM product deleted successfully.',
        ]);
    }

    private function rules(?BomProduct $product = null, bool $isQuickBom = false): array
    {
        // ✅ CHANGED: Only validate 4 required fields
        // 1. product_name (Name)
        // 2. category_id (Make)
        // 3. price (Price)
        // 4. image (Image)
        // All other fields are optional with no validation
        return [
            'user_id' => ['nullable', 'exists:users,id'],
            'product_name' => ['required', 'string', 'max:255'],
            'hsn_sac' => ['nullable', 'string', 'max:50'],
            'category_id' => ['nullable', 'array'],
            'category_id.*' => ['exists:category,id'],
            'price' => $isQuickBom
                ? ['nullable', 'numeric', 'min:0']
                : ['required', 'numeric', 'min:1'],
            'image' => ['nullable', 'file', 'mimes:jpg,jpeg,png,gif,bmp,webp,avif,svg', 'max:2048'],
            // All other fields are optional - no validation
            'tax_type' => ['nullable'],
            'tax_rate' => ['nullable'],
            'technology_id' => ['nullable'],
            'warranty_id' => ['nullable'],
            'description' => ['nullable'],
            'height' => ['nullable'],
            'fitting_material' => ['nullable'],
            'fitting_type' => ['nullable'],
            'thickness' => ['nullable'],
            'size_of_pipe' => ['nullable'],
            'capacity' => ['nullable'],
            'meter' => ['nullable'],
            'nos' => ['nullable'],
        ];
    }

    private function messages(bool $isCreate): array
    {
        // ✅ CHANGED: Only show messages for 4 required fields
        return [
            'product_name.required' => 'Please fill out the BOM name!',
            'category_id.array' => 'Please select a valid Make!',
            'category_id.*.exists' => 'Please select a valid Make!',
            'price.required' => 'Please enter the price!',
            'price.numeric' => 'Price must be a valid number!',
            'price.min' => 'Price must be greater than or equal to 1!',
            'image.required' => 'Please select an image!',
            'image.mimes' => 'Please select a valid image. Allowed types: AVIF, WEBP, JPG, JPEG, PNG, GIF, BMP, SVG.',
            'image.max' => 'Please select an image smaller than 2MB!',
        ];
    }
}
