<?php

namespace App\Http\Controllers\Api;

use App\Models\ProductInventory;
use App\Models\Purchase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PurchaseController extends ApiBaseController
{
    public function index(Request $request)
    {
        $search = trim((string) $request->get('search', ''));
        $perPage = max(1, min((int) $request->get('per_page', 10), 100));

        $purchases = Purchase::with(['vendor', 'product', 'creator'])
            ->when($search !== '', fn ($query) => $query->where('invoice_no', 'like', "%{$search}%")
                ->orWhere('invoice_name', 'like', "%{$search}%")
                ->orWhereHas('vendor', fn ($q) => $q->where('name', 'like', "%{$search}%")))
            ->latest()
            ->paginate($perPage)
            ->appends($request->query());

        $purchases->getCollection()->transform(fn (Purchase $purchase) => $this->serialize($purchase));

        return response()->json([
            'success' => true,
            'message' => 'Purchases retrieved successfully.',
            'data' => $purchases,
        ]);
    }

    public function store(Request $request)
    {
        // Check if we have multiple products or single product
        $hasMultipleProducts = $request->has('products') && is_array($request->products);
        
        if ($hasMultipleProducts) {
            return $this->storeMultipleProducts($request);
        } else {
            return $this->storeSingleProduct($request);
        }
    }

    private function storeSingleProduct(Request $request)
    {
        $validator = Validator::make($request->all(), $this->rules(), $this->messages());

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();
        $data['user_id'] = auth()->id();
        $data['created_by'] = auth()->id();

        // Generate IN number
        $lastPurchase = Purchase::latest('invoice_id')->first();
        $nextNumber = ($lastPurchase ? intval($lastPurchase->invoice_no) : 0) + 1;
        $data['invoice_no'] = str_pad($nextNumber, 6, '0', STR_PAD_LEFT);

        // Calculate total if not provided
        if (!isset($data['total']) || $data['total'] == 0) {
            $subtotal = ($data['price'] ?? 0) * ($data['quantity'] ?? 1);
            $gst = ($subtotal * ($data['gst'] ?? 0)) / 100;
            $discount = $data['discount'] ?? 0;
            $data['total'] = $subtotal + $gst - $discount;
        }

        $purchase = Purchase::create($data);

        // Create inventory record for Material IN (increase stock)
        $quantity = $data['quantity'] ?? 0;
        $latestInventory = ProductInventory::where('product_id', $data['product_id'])->latest('id')->first();
        $currentStock = ($latestInventory?->current_stock ?? 0) + $quantity;

        ProductInventory::create([
            'product_id' => $data['product_id'],
            'initial_stock' => $quantity,
            'current_stock' => $currentStock,
            'type' => 'increase',
            'date' => now()->toDateString(),
            'created_by' => auth()->id(),
        ]);

        $purchase->load(['vendor', 'product', 'creator']);

        return response()->json([
            'success' => true,
            'message' => 'Purchase created successfully.',
            'data' => $this->serialize($purchase),
        ], 201);
    }

    private function storeMultipleProducts(Request $request)
    {
        // Validate basic fields
        $basicValidator = Validator::make($request->all(), [
            'customer_id' => ['required', 'exists:vendors,id'],
            'invoice_date' => ['required', 'date'],
            'comment' => ['nullable', 'string'],
            'products' => ['required', 'array', 'min:1'],
            'products.*.product_id' => ['required', 'exists:products,id'],
            'products.*.quantity' => ['required', 'integer', 'min:1'],
        ], [
            'customer_id.required' => 'Please select a vendor.',
            'customer_id.exists' => 'Please select a valid vendor.',
            'invoice_date.required' => 'Please select invoice date.',
            'products.required' => 'Please add at least one product.',
            'products.*.product_id.required' => 'Please select a product.',
            'products.*.product_id.exists' => 'Please select a valid product.',
            'products.*.quantity.required' => 'Please enter quantity.',
            'products.*.quantity.min' => 'Quantity must be at least 1.',
        ]);

        if ($basicValidator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $basicValidator->errors(),
            ], 422);
        }

        $data = $basicValidator->validated();
        $createdPurchases = [];

        // Generate base IN number
        $lastPurchase = Purchase::latest('invoice_id')->first();
        $baseNumber = ($lastPurchase ? intval($lastPurchase->invoice_no) : 0) + 1;

        foreach ($data['products'] as $index => $productData) {
            $purchaseData = [
                'customer_id' => $data['customer_id'],
                'product_id' => $productData['product_id'],
                'invoice_date' => $data['invoice_date'],
                'quantity' => $productData['quantity'],
                'price' => 0, // Default values
                'gst' => 0,
                'discount' => 0,
                'total' => 0,
                'status' => 'pending',
                'comment' => $data['comment'] ?? null,
                'user_id' => auth()->id(),
                'created_by' => auth()->id(),
                'invoice_no' => str_pad($baseNumber + $index, 6, '0', STR_PAD_LEFT),
            ];

            $purchase = Purchase::create($purchaseData);

            // Create inventory record for Material IN (increase stock)
            $quantity = $productData['quantity'];
            $latestInventory = ProductInventory::where('product_id', $productData['product_id'])->latest('id')->first();
            $currentStock = ($latestInventory?->current_stock ?? 0) + $quantity;

            ProductInventory::create([
                'product_id' => $productData['product_id'],
                'initial_stock' => $quantity,
                'current_stock' => $currentStock,
                'type' => 'increase',
                'date' => now()->toDateString(),
                'created_by' => auth()->id(),
            ]);

            $purchase->load(['vendor', 'product', 'creator']);
            $createdPurchases[] = $this->serialize($purchase);
        }

        return response()->json([
            'success' => true,
            'message' => count($createdPurchases) . ' Material IN entries created successfully.',
            'data' => $createdPurchases,
        ], 201);
    }

    public function show(Purchase $purchase)
    {
        $purchase->load(['vendor', 'product', 'creator']);

        return response()->json([
            'success' => true,
            'message' => 'Purchase retrieved successfully.',
            'data' => $this->serialize($purchase),
        ]);
    }

    public function update(Request $request, Purchase $purchase)
    {
        $validator = Validator::make($request->all(), $this->rules($purchase), $this->messages());

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();
        $data['updated_by'] = auth()->id();

        $oldProductId = $purchase->product_id;
        $oldQuantity = $purchase->quantity;

        // Calculate total if not provided
        if (!isset($data['total']) || $data['total'] == 0) {
            $subtotal = ($data['price'] ?? $purchase->price) * ($data['quantity'] ?? $purchase->quantity);
            $gst = ($subtotal * ($data['gst'] ?? $purchase->gst)) / 100;
            $discount = $data['discount'] ?? $purchase->discount;
            $data['total'] = $subtotal + $gst - $discount;
        }

        $purchase->update($data);

        // Adjust inventory if product or quantity changed
        $newProductId = $purchase->product_id;
        $newQuantity = $purchase->quantity;

        if ($oldProductId != $newProductId || $oldQuantity != $newQuantity) {
            // Reverse old inventory
            $latestInventoryOld = ProductInventory::where('product_id', $oldProductId)->latest('id')->first();
            $currentStockOld = ($latestInventoryOld?->current_stock ?? 0) - $oldQuantity;
            
            ProductInventory::create([
                'product_id' => $oldProductId,
                'initial_stock' => $oldQuantity,
                'current_stock' => $currentStockOld,
                'type' => 'decrease',
                'date' => now()->toDateString(),
                'created_by' => auth()->id(),
            ]);

            // If product changed, add to new product, otherwise adjust same product
            $latestInventoryNew = ProductInventory::where('product_id', $newProductId)->latest('id')->first();
            $currentStockNew = ($latestInventoryNew?->current_stock ?? 0) + $newQuantity;

            ProductInventory::create([
                'product_id' => $newProductId,
                'initial_stock' => $newQuantity,
                'current_stock' => $currentStockNew,
                'type' => 'increase',
                'date' => now()->toDateString(),
                'created_by' => auth()->id(),
            ]);
        }

        $purchase->load(['vendor', 'product', 'creator']);

        return response()->json([
            'success' => true,
            'message' => 'Purchase updated successfully.',
            'data' => $this->serialize($purchase),
        ]);
    }

    public function destroy(Purchase $purchase)
    {
        $productId = $purchase->product_id;
        $quantity = $purchase->quantity;

        $purchase->delete();

        // Revert inventory
        $latestInventory = ProductInventory::where('product_id', $productId)->latest('id')->first();
        $currentStock = ($latestInventory?->current_stock ?? 0) - $quantity;

        ProductInventory::create([
            'product_id' => $productId,
            'initial_stock' => $quantity,
            'current_stock' => $currentStock,
            'type' => 'decrease',
            'date' => now()->toDateString(),
            'created_by' => auth()->id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Purchase deleted successfully.',
        ]);
    }

    private function rules(?Purchase $purchase = null): array
    {
        return [
            'customer_id' => ['required', 'exists:vendors,id'],
            'product_id' => ['required', 'exists:products,id'],
            'invoice_date' => ['required', 'date'],
            'due_date' => ['nullable', 'date'],
            'quantity' => ['required', 'integer', 'min:1'],
            'price' => ['required', 'numeric', 'min:0'],
            'gst' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'discount' => ['nullable', 'numeric', 'min:0'],
            'total' => ['nullable', 'numeric', 'min:0'],
            'status' => ['nullable', 'in:pending,approved,rejected,completed'],
            'comment' => ['nullable', 'string'],
            'attach_file' => ['nullable', 'file', 'max:5120'],
        ];
    }

    private function messages(): array
    {
        return [
            'customer_id.required' => 'Please select a vendor.',
            'customer_id.exists' => 'Please select a valid vendor.',
            'product_id.required' => 'Please select a product.',
            'product_id.exists' => 'Please select a valid product.',
            'invoice_date.required' => 'Please select invoice date.',
            'quantity.required' => 'Please enter quantity.',
            'quantity.min' => 'Quantity must be at least 1.',
            'price.required' => 'Please enter price.',
        ];
    }

    private function serialize(Purchase $purchase): array
    {
        return [
            'invoice_id' => $purchase->invoice_id,
            'customer_id' => $purchase->customer_id,
            'vendor_id' => $purchase->customer_id,
            'vendor' => $purchase->vendor ? [
                'id' => $purchase->vendor->id,
                'name' => $purchase->vendor->name,
            ] : null,
            'product_id' => $purchase->product_id,
            'product' => $purchase->product ? [
                'id' => $purchase->product->id,
                'name' => $purchase->product->name,
            ] : null,
            'invoice_no' => $purchase->invoice_no,
            'invoice_date' => optional($purchase->invoice_date)?->format('Y-m-d'),
            'due_date' => optional($purchase->due_date)?->format('Y-m-d'),
            'quantity' => $purchase->quantity,
            'price' => $purchase->price,
            'gst' => $purchase->gst,
            'discount' => $purchase->discount,
            'total' => $purchase->total,
            'amount' => $purchase->amount,
            'status' => $purchase->status,
            'comment' => $purchase->comment,
            'created_by' => $purchase->created_by,
            'creator' => $purchase->creator ? [
                'id' => $purchase->creator->id,
                'name' => $purchase->creator->name,
            ] : null,
            'created_at' => optional($purchase->created_at)?->toIso8601String(),
            'updated_at' => optional($purchase->updated_at)?->toIso8601String(),
        ];
    }
}
