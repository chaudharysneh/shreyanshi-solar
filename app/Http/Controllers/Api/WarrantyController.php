<?php

namespace App\Http\Controllers\Api;

use App\Models\BomProduct;
use App\Models\Warranty;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class WarrantyController extends ApiBaseController
{
    public function index(Request $request)
    {
        $search = trim((string) $request->get('search', ''));
        $perPage = max(1, min((int) $request->get('per_page', 10), 100));

        $warranties = Warranty::query()
            ->when($search !== '', function ($query) use ($search) {
                $query->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            })
            ->latest()
            ->paginate($perPage)
            ->appends($request->query());

        return response()->json([
            'success' => true,
            'message' => 'Warranties retrieved successfully.',
            'data' => $warranties,
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

        $warranty = Warranty::create($validator->validated());

        return response()->json([
            'success' => true,
            'message' => 'Warranty created successfully.',
            'data' => $warranty,
        ], 201);
    }

    public function show(Warranty $warranty)
    {
        return response()->json([
            'success' => true,
            'message' => 'Warranty retrieved successfully.',
            'data' => $warranty,
        ]);
    }

    public function update(Request $request, Warranty $warranty)
    {
        $validator = Validator::make($request->all(), $this->rules($warranty), $this->messages());

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $warranty->update($validator->validated());

        return response()->json([
            'success' => true,
            'message' => 'Warranty updated successfully.',
            'data' => $warranty->fresh(),
        ]);
    }

    public function destroy(Warranty $warranty)
    {
        if (BomProduct::where('warranty_id', $warranty->id)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'This warranty is already in use.',
            ], 422);
        }

        $warranty->delete();

        return response()->json([
            'success' => true,
            'message' => 'Warranty deleted successfully.',
        ]);
    }

    private function rules(?Warranty $warranty = null): array
    {
        return [
            'title' => [
                'required',
                'string',
                'max:255',
                Rule::unique('warranty', 'title')->ignore($warranty?->id)->whereNull('deleted_at'),
            ],
            'description' => ['nullable', 'string', 'max:255'],
        ];
    }

    private function messages(): array
    {
        return [
            'title.required' => 'Please enter warranty name.',
            'title.unique' => 'This warranty name already exists.',
        ];
    }
}
