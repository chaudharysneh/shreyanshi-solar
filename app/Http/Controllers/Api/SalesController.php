<?php

namespace App\Http\Controllers\Api;

use App\Models\Sales;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SalesController extends ApiBaseController
{
    public function index(Request $request)
    {
        $search = trim((string) $request->get('search', ''));
        $perPage = max(1, min((int) $request->get('per_page', 10), 100));

        $sales = Sales::with(['customer', 'product', 'handoverPerson', 'creator'])
            ->when($search !== '', fn ($query) => $query->where('invoice_no', 'like', "%{$search}%")
                ->orWhere('invoice_name', 'like', "%{$search}%")
                ->orWhereHas('customer', fn ($q) => $q->where('name', 'like', "%{$search}%")))
            ->latest()
            ->paginate($perPage)
            ->appends($request->query());

        $sales->getCollection()->transform(fn (Sales $sale) => $this->serialize($sale));

        return response()->json([
            'success' => true,
            'message' => 'Sales retrieved successfully.',
            'data' => $sales,
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

        // Set default currency if not provided
        if (empty($data['currency'])) {
            $data['currency'] = 'USD'; // Default currency
        }

        // Generate OUT number
        $lastSale = Sales::latest('invoice_id')->first();
        $nextNumber = ($lastSale ? intval($lastSale->invoice_no) : 0) + 1;
        $data['invoice_no'] = str_pad($nextNumber, 6, '0', STR_PAD_LEFT);

        // Check if sufficient stock available
        $quantity = $data['quantity'] ?? 0;
        $latestInventory = \App\Models\ProductInventory::where('product_id', $data['product_id'])->latest('id')->first();
        $currentStock = $latestInventory?->current_stock ?? 0;

        if ($currentStock < $quantity) {
            return response()->json([
                'success' => false,
                'errors' => [
                    'quantity' => ["Insufficient stock! Available: {$currentStock}, Requested: {$quantity}"]
                ],
            ], 422);
        }

        // Calculate total if not provided
        if (!isset($data['total']) || $data['total'] == 0) {
            $subtotal = ($data['price'] ?? 0) * ($data['quantity'] ?? 1);
            $gst = ($subtotal * ($data['gst'] ?? 0)) / 100;
            $discount = $data['discount'] ?? 0;
            $data['total'] = $subtotal + $gst - $discount;
        }

        $sale = Sales::create($data);

        // Create inventory record for Material OUT (decrease stock)
        $newStock = $currentStock - $quantity;

        \App\Models\ProductInventory::create([
            'product_id' => $data['product_id'],
            'initial_stock' => $quantity,
            'current_stock' => $newStock,
            'type' => 'decrease',
            'date' => now()->toDateString(),
            'created_by' => auth()->id(),
        ]);

        $sale->load(['customer', 'product', 'handoverPerson', 'creator']);

        return response()->json([
            'success' => true,
            'message' => 'Sale created successfully.',
            'data' => $this->serialize($sale),
        ], 201);
    }

    private function storeMultipleProducts(Request $request)
    {
        // Validate basic fields
        $basicValidator = Validator::make($request->all(), [
            'customer_id' => ['required', 'exists:customers,id'],
            'invoice_date' => ['required', 'date'],
            'handover_id' => ['nullable', 'integer'],
            'comment' => ['nullable', 'string'],
            'products' => ['required', 'array', 'min:1'],
            'products.*.product_id' => ['required', 'exists:products,id'],
            'products.*.quantity' => ['required', 'integer', 'min:1'],
        ], [
            'customer_id.required' => 'Please select a customer.',
            'customer_id.exists' => 'Please select a valid customer.',
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

        // Check stock availability for all products first, grouped by product.
        $stockErrors = [];
        $requestedByProduct = [];
        foreach ($data['products'] as $index => $productData) {
            $requestedByProduct[$productData['product_id']] = ($requestedByProduct[$productData['product_id']] ?? 0) + (int) $productData['quantity'];
        }

        foreach ($data['products'] as $index => $productData) {
            $latestInventory = \App\Models\ProductInventory::where('product_id', $productData['product_id'])->latest('id')->first();
            $currentStock = $latestInventory?->current_stock ?? 0;
            $requestedQuantity = $productData['quantity'];
            $totalRequestedQuantity = $requestedByProduct[$productData['product_id']] ?? $requestedQuantity;

            if ($currentStock < $requestedQuantity) {
                $stockErrors["products.{$index}.quantity"] = ["Insufficient stock! Available: {$currentStock}, Requested: {$requestedQuantity}"];
            } elseif ($currentStock < $totalRequestedQuantity) {
                $stockErrors["products.{$index}.quantity"] = ["Insufficient stock! Available: {$currentStock}, Total requested for this product: {$totalRequestedQuantity}"];
            }
        }

        if (!empty($stockErrors)) {
            return response()->json([
                'success' => false,
                'errors' => $stockErrors,
            ], 422);
        }

        $createdSales = [];

        // Generate base OUT number
        $lastSale = Sales::latest('invoice_id')->first();
        $baseNumber = ($lastSale ? intval($lastSale->invoice_no) : 0) + 1;

        foreach ($data['products'] as $index => $productData) {
            $saleData = [
                'customer_id' => $data['customer_id'],
                'product_id' => $productData['product_id'],
                'invoice_date' => $data['invoice_date'],
                'quantity' => $productData['quantity'],
                'handover_id' => $data['handover_id'] ?? null,
                'price' => 0, // Default values
                'gst' => 0,
                'discount' => 0,
                'total' => 0,
                'currency' => 'USD',
                'status' => 'pending',
                'comment' => $data['comment'] ?? null,
                'user_id' => auth()->id(),
                'created_by' => auth()->id(),
                'invoice_no' => str_pad($baseNumber + $index, 6, '0', STR_PAD_LEFT),
            ];

            $sale = Sales::create($saleData);

            // Create inventory record for Material OUT (decrease stock)
            $quantity = $productData['quantity'];
            $latestInventory = \App\Models\ProductInventory::where('product_id', $productData['product_id'])->latest('id')->first();
            $currentStock = $latestInventory?->current_stock ?? 0;
            $newStock = $currentStock - $quantity;

            \App\Models\ProductInventory::create([
                'product_id' => $productData['product_id'],
                'initial_stock' => $quantity,
                'current_stock' => $newStock,
                'type' => 'decrease',
                'date' => now()->toDateString(),
                'created_by' => auth()->id(),
            ]);

            $sale->load(['customer', 'product', 'handoverPerson', 'creator']);
            $createdSales[] = $this->serialize($sale);
        }

        return response()->json([
            'success' => true,
            'message' => count($createdSales) . ' Material OUT entries created successfully.',
            'data' => $createdSales,
        ], 201);
    }

    public function show(Sales $sale)
    {
        $sale->load(['customer', 'product', 'handoverPerson', 'creator']);

        return response()->json([
            'success' => true,
            'message' => 'Sale retrieved successfully.',
            'data' => $this->serialize($sale),
        ]);
    }

    public function update(Request $request, Sales $sale)
    {
        $validator = Validator::make($request->all(), $this->rules($sale), $this->messages());

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();
        $data['updated_by'] = auth()->id();

        $oldProductId = $sale->product_id;
        $oldQuantity = $sale->quantity;
        $newProductId = $data['product_id'] ?? $oldProductId;
        $newQuantity = $data['quantity'] ?? $oldQuantity;

        if ($oldProductId != $newProductId || $oldQuantity != $newQuantity) {
            $latestInventoryNew = \App\Models\ProductInventory::where('product_id', $newProductId)->latest('id')->first();
            $currentStockNew = $latestInventoryNew?->current_stock ?? 0;
            
            // If the product is the same, we need additional stock equal to (new - old)
            if ($oldProductId == $newProductId) {
                $additionalNeeded = $newQuantity - $oldQuantity;
                if ($additionalNeeded > 0 && $currentStockNew < $additionalNeeded) {
                    return response()->json([
                        'success' => false,
                        'errors' => [
                            'quantity' => ["Insufficient stock! Available: {$currentStockNew}, Additional Requested: {$additionalNeeded}"]
                        ],
                    ], 422);
                }
            } else {
                // If the product changed, we need the full new quantity
                if ($currentStockNew < $newQuantity) {
                    return response()->json([
                        'success' => false,
                        'errors' => [
                            'quantity' => ["Insufficient stock! Available: {$currentStockNew}, Requested: {$newQuantity}"]
                        ],
                    ], 422);
                }
            }
        }

        // Calculate total if not provided
        if (!isset($data['total']) || $data['total'] == 0) {
            $subtotal = ($data['price'] ?? $sale->price) * ($data['quantity'] ?? $sale->quantity);
            $gst = ($subtotal * ($data['gst'] ?? $sale->gst)) / 100;
            $discount = $data['discount'] ?? $sale->discount;
            $data['total'] = $subtotal + $gst - $discount;
        }

        $sale->update($data);

        // Adjust inventory if product or quantity changed
        if ($oldProductId != $newProductId || $oldQuantity != $newQuantity) {
            // Reverse old inventory
            $latestInventoryOld = \App\Models\ProductInventory::where('product_id', $oldProductId)->latest('id')->first();
            $currentStockOld = ($latestInventoryOld?->current_stock ?? 0) + $oldQuantity; // Restoring what was OUT'd
            
            \App\Models\ProductInventory::create([
                'product_id' => $oldProductId,
                'initial_stock' => $oldQuantity,
                'current_stock' => $currentStockOld,
                'type' => 'increase',
                'date' => now()->toDateString(),
                'created_by' => auth()->id(),
            ]);

            // Re-fetch new inventory (in case old and new product are the same)
            $latestInventoryNew = \App\Models\ProductInventory::where('product_id', $newProductId)->latest('id')->first();
            $currentStockNew = ($latestInventoryNew?->current_stock ?? 0) - $newQuantity;

            \App\Models\ProductInventory::create([
                'product_id' => $newProductId,
                'initial_stock' => $newQuantity,
                'current_stock' => $currentStockNew,
                'type' => 'decrease',
                'date' => now()->toDateString(),
                'created_by' => auth()->id(),
            ]);
        }

        $sale->load(['customer', 'product', 'handoverPerson', 'creator']);

        return response()->json([
            'success' => true,
            'message' => 'Sale updated successfully.',
            'data' => $this->serialize($sale),
        ]);
    }

    public function destroy(Sales $sale)
    {
        $productId = $sale->product_id;
        $quantity = $sale->quantity;

        $sale->delete();

        // Revert inventory
        $latestInventory = \App\Models\ProductInventory::where('product_id', $productId)->latest('id')->first();
        $currentStock = ($latestInventory?->current_stock ?? 0) + $quantity;

        \App\Models\ProductInventory::create([
            'product_id' => $productId,
            'initial_stock' => $quantity,
            'current_stock' => $currentStock,
            'type' => 'increase',
            'date' => now()->toDateString(),
            'created_by' => auth()->id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Sale deleted successfully.',
        ]);
    }

    private function rules(?Sales $sale = null): array
    {
        return [
            'customer_id' => ['required', 'exists:customers,id'],
            'product_id' => ['required', 'exists:products,id'],
            'invoice_date' => ['required', 'date'],
            'due_date' => ['nullable', 'date'],
            'quantity' => ['required', 'integer', 'min:1'],
            'price' => ['required', 'numeric', 'min:0'],
            'gst' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'discount' => ['nullable', 'numeric', 'min:0'],
            'total' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'max:50'],
            'status' => ['nullable', 'in:pending,approved,rejected,completed'],
            'comment' => ['nullable', 'string'],
            'handover_id' => ['nullable', 'integer'],
            'attach_file' => ['nullable', 'file', 'max:5120'],
        ];
    }

    private function messages(): array
    {
        return [
            'customer_id.required' => 'Please select a customer.',
            'customer_id.exists' => 'Please select a valid customer.',
            'product_id.required' => 'Please select a product.',
            'product_id.exists' => 'Please select a valid product.',
            'handover_id.exists' => 'Please select a valid handover person.',
            'invoice_date.required' => 'Please select invoice date.',
            'quantity.required' => 'Please enter quantity.',
            'quantity.min' => 'Quantity must be at least 1.',
            'price.required' => 'Please enter price.',
        ];
    }

    private function serialize(Sales $sale): array
    {
        return [
            'invoice_id' => $sale->invoice_id,
            'customer_id' => $sale->customer_id,
            'customer' => $sale->customer ? [
                'id' => $sale->customer->id,
                'name' => $sale->customer->name,
            ] : null,
            'product_id' => $sale->product_id,
            'product' => $sale->product ? [
                'id' => $sale->product->id,
                'name' => $sale->product->name,
            ] : null,
            'handover_id' => $sale->handover_id,
            'handoverPerson' => $sale->handoverPerson ? [
                'id' => $sale->handoverPerson->id,
                'name' => $sale->handoverPerson->name,
            ] : null,
            'invoice_no' => $sale->invoice_no,
            'invoice_date' => optional($sale->invoice_date)?->format('Y-m-d'),
            'due_date' => optional($sale->due_date)?->format('Y-m-d'),
            'quantity' => $sale->quantity,
            'price' => $sale->price,
            'gst' => $sale->gst,
            'discount' => $sale->discount,
            'total' => $sale->total,
            'amount' => $sale->amount,
            'status' => $sale->status,
            'comment' => $sale->comment,
            'created_by' => $sale->created_by,
            'creator' => $sale->creator ? [
                'id' => $sale->creator->id,
                'name' => $sale->creator->name,
            ] : null,
            'created_at' => optional($sale->created_at)?->toIso8601String(),
            'updated_at' => optional($sale->updated_at)?->toIso8601String(),
        ];
    }
}
