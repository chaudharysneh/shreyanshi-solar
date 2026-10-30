<?php

namespace App\Http\Controllers\Api;

use App\Models\ProductInventory;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ProductInventoryController extends ApiBaseController
{
    public function index(Request $request)
    {
        $search = trim((string) $request->get('search', ''));
        $perPage = max(1, min((int) $request->get('per_page', 10), 100));

        $products = Product::with(['creator'])
            ->when($search !== '', fn ($query) => $query->where('name', 'like', "%{$search}%"))
            ->latest()
            ->paginate($perPage)
            ->appends($request->query());

        $products->getCollection()->transform(function (Product $product) {
            $inventory = ProductInventory::where('product_id', $product->id)->latest('id')->first();
            
            // Use inventory data if exists, otherwise use product data
            return [
                'id' => $inventory?->id ?? 'prod_' . $product->id,
                'product_id' => $product->id,
                'product' => [
                    'id' => $product->id,
                    'name' => $product->name,
                ],
                'initial_stock' => $inventory?->initial_stock ?? 0,
                'current_stock' => $inventory?->current_stock ?? $product->quantity,
                'type' => $inventory?->type,
                'branch_id' => $inventory?->branch_id,
                'date' => $inventory?->date,
                'created_by' => $inventory?->created_by,
                'creator' => $inventory?->creator ? [
                    'id' => $inventory->creator->id,
                    'name' => $inventory->creator->name,
                ] : null,
                'created_at' => $inventory?->created_at?->toIso8601String() ?? $product->created_at?->toIso8601String(),
                'updated_at' => $inventory?->updated_at?->toIso8601String(),
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'Inventories retrieved successfully.',
            'data' => $products,
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
        $data['created_by'] = auth()->id();

        // Create new inventory record
        $inventory = ProductInventory::create($data);
        $inventory->load(['product', 'creator']);

        return response()->json([
            'success' => true,
            'message' => 'Inventory created successfully.',
            'data' => $this->serialize($inventory),
        ], 201);
    }

    public function show(ProductInventory $inventory)
    {
        $inventory->load(['product', 'creator']);

        return response()->json([
            'success' => true,
            'message' => 'Inventory retrieved successfully.',
            'data' => $this->serialize($inventory),
        ]);
    }

    public function update(Request $request, ProductInventory $inventory)
    {
        $validator = Validator::make($request->all(), $this->rules($inventory), $this->messages());

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();
        $data['created_by'] = auth()->id();

        // Always create a new record for history tracking
        $newInventory = ProductInventory::create($data);
        $newInventory->load(['product', 'creator']);

        return response()->json([
            'success' => true,
            'message' => 'Inventory updated successfully.',
            'data' => $this->serialize($newInventory),
        ]);
    }

    public function destroy(ProductInventory $inventory)
    {
        $inventory->delete();

        return response()->json([
            'success' => true,
            'message' => 'Inventory deleted successfully.',
        ]);
    }

    public function history(Product $product, Request $request)
    {
        $perPage = max(1, min((int) $request->get('per_page', 10), 100));

        $histories = ProductInventory::where('product_id', $product->id)
            ->with(['creator'])
            ->latest('id')
            ->paginate($perPage)
            ->appends($request->query());

        $histories->getCollection()->transform(fn (ProductInventory $inventory) => $this->serializeHistory($inventory));

        return response()->json([
            'success' => true,
            'message' => 'Inventory history retrieved successfully.',
            'data' => $histories,
        ]);
    }

    private function rules(?ProductInventory $inventory = null): array
    {
        return [
            'product_id' => ['required', 'exists:products,id'],
            'initial_stock' => ['required', 'integer', 'min:0'],
            'current_stock' => ['required', 'integer', 'min:0'],
            'type' => ['nullable', 'string', 'max:255'],
            'branch_id' => ['nullable', 'integer'],
            'date' => ['nullable', 'date'],
        ];
    }

    private function messages(): array
    {
        return [
            'product_id.required' => 'Please select a product.',
            'product_id.exists' => 'Please select a valid product.',
            'initial_stock.required' => 'Please enter initial stock.',
            'initial_stock.integer' => 'Initial stock must be a number.',
            'current_stock.required' => 'Please enter current stock.',
            'current_stock.integer' => 'Current stock must be a number.',
        ];
    }

    private function serialize(ProductInventory $inventory): array
    {
        return [
            'id' => $inventory->id,
            'product_id' => $inventory->product_id,
            'product' => $inventory->product ? [
                'id' => $inventory->product->id,
                'name' => $inventory->product->name,
            ] : null,
            'initial_stock' => $inventory->initial_stock,
            'current_stock' => $inventory->current_stock,
            'type' => $inventory->type,
            'branch_id' => $inventory->branch_id,
            'date' => optional($inventory->date)?->format('Y-m-d'),
            'created_by' => $inventory->created_by,
            'creator' => $inventory->creator ? [
                'id' => $inventory->creator->id,
                'name' => $inventory->creator->name,
            ] : null,
            'created_at' => optional($inventory->created_at)?->toIso8601String(),
            'updated_at' => optional($inventory->updated_at)?->toIso8601String(),
        ];
    }

    private function serializeHistory(ProductInventory $inventory): array
    {
        return [
            'id' => $inventory->id,
            'type' => $inventory->type,
            'current_stock' => $inventory->current_stock,
            'initial_stock' => $inventory->initial_stock,
            'creator' => $inventory->creator ? $inventory->creator->name : '-',
            'date' => optional($inventory->date)?->format('Y-m-d'),
            'created_at' => optional($inventory->created_at)?->toIso8601String(),
        ];
    }
}
