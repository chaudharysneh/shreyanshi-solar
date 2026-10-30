<?php

namespace App\Http\Controllers\Api;

use App\Models\BomProduct;
use App\Models\Technology;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class TechnologyController extends ApiBaseController
{
    public function index(Request $request)
    {
        $search = trim((string) $request->get('search', ''));
        $perPage = max(1, min((int) $request->get('per_page', 10), 100));

        $technologies = Technology::query()
            ->when($search !== '', function ($query) use ($search) {
                $query->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            })
            ->latest()
            ->paginate($perPage)
            ->appends($request->query());

        return response()->json([
            'success' => true,
            'message' => 'Technologies retrieved successfully.',
            'data' => $technologies,
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

        $technology = Technology::create($validator->validated());

        return response()->json([
            'success' => true,
            'message' => 'Technology created successfully.',
            'data' => $technology,
        ], 201);
    }

    public function show(Technology $technology)
    {
        return response()->json([
            'success' => true,
            'message' => 'Technology retrieved successfully.',
            'data' => $technology,
        ]);
    }

    public function update(Request $request, Technology $technology)
    {
        $validator = Validator::make($request->all(), $this->rules($technology), $this->messages());

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $technology->update($validator->validated());

        return response()->json([
            'success' => true,
            'message' => 'Technology updated successfully.',
            'data' => $technology->fresh(),
        ]);
    }

    public function destroy(Technology $technology)
    {
        if (BomProduct::where('technology_id', $technology->id)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'This technology is already in use.',
            ], 422);
        }

        $technology->delete();

        return response()->json([
            'success' => true,
            'message' => 'Technology deleted successfully.',
        ]);
    }

    private function rules(?Technology $technology = null): array
    {
        return [
            'title' => [
                'required',
                'string',
                'max:255',
                Rule::unique('technology', 'title')->ignore($technology?->id)->whereNull('deleted_at'),
            ],
            'description' => ['nullable', 'string', 'max:255'],
        ];
    }

    private function messages(): array
    {
        return [
            'title.required' => 'Please enter technology name.',
            'title.unique' => 'This technology name already exists.',
        ];
    }
}
