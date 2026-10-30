<?php

namespace App\Http\Controllers\Api;

use App\Models\Vendor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class VendorController extends ApiBaseController
{
    public function index(Request $request)
    {
        $search = trim((string) $request->get('search', ''));

        $vendors = Vendor::with(['creator'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($innerQuery) use ($search) {
                    $innerQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $vendors->getCollection()->transform(fn (Vendor $vendor) => $this->serialize($vendor));

        return response()->json([
            'success' => true,
            'message' => 'Vendors retrieved successfully.',
            'data' => $vendors,
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
            $data['image'] = $request->file('image')->store('vendors', 'public');
        }

        $data['status'] = $data['status'] ?? 'active';

        $vendor = Vendor::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Vendor created successfully.',
            'data' => $this->serialize($vendor->fresh(['creator'])),
            'redirect' => route('vendors.index'),
        ], 201);
    }

    public function show(Vendor $vendor)
    {
        $vendor->load(['creator', 'updater', 'deleter']);

        return response()->json([
            'success' => true,
            'message' => 'Vendor retrieved successfully.',
            'data' => $this->serialize($vendor),
        ]);
    }

    public function update(Request $request, Vendor $vendor)
    {
        $validator = Validator::make($request->all(), $this->rules($vendor), $this->messages());

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();

        if ($request->hasFile('image')) {
            if ($vendor->image) {
                Storage::disk('public')->delete($vendor->image);
            }

            $data['image'] = $request->file('image')->store('vendors', 'public');
        }

        $data['status'] = $data['status'] ?? ($vendor->status ?: 'active');

        $vendor->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Vendor updated successfully.',
            'data' => $this->serialize($vendor->fresh(['creator', 'updater'])),
            'redirect' => route('vendors.index'),
        ]);
    }

    public function destroy(Vendor $vendor)
    {
        if ($vendor->image) {
            Storage::disk('public')->delete($vendor->image);
        }

        $vendor->delete();

        return response()->json([
            'success' => true,
            'message' => 'Vendor deleted successfully.',
        ]);
    }

    private function rules(?Vendor $vendor = null): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'nullable',
                'email',
                'max:255',
                Rule::unique('vendors', 'email')->ignore($vendor?->id)->whereNull('deleted_at'),
            ],
            'phone' => [
                'required',
                'regex:/^[0-9]{10}$/',
                Rule::unique('vendors', 'phone')->ignore($vendor?->id)->whereNull('deleted_at'),
            ],
            'address' => ['nullable', 'string', 'max:2000'],
            'image' => ['nullable', 'file', 'mimes:jpg,jpeg,png,gif,bmp,webp,avif,svg', 'max:2048'],
            'status' => ['nullable', 'string', 'max:50'],
        ];
    }

    private function messages(): array
    {
        return [
            'name.required' => 'Please enter vendor name.',
            'phone.required' => 'Please enter phone.',
            'phone.regex' => 'Phone number must be exactly 10 digits.',
            'phone.unique' => 'This phone number already exists.',
            'email.email' => 'Please enter a valid email address.',
            'email.unique' => 'This email address already exists.',
            'image.mimes' => 'Please select a valid image. Allowed types: AVIF, WEBP, JPG, JPEG, PNG, GIF, BMP, SVG.',
            'image.max' => 'Please select an image smaller than 2MB.',
        ];
    }

    private function serialize(Vendor $vendor): array
    {
        return [
            'id' => $vendor->id,
            'name' => $vendor->name,
            'email' => $vendor->email,
            'phone' => $vendor->phone,
            'address' => $vendor->address,
            'image' => $vendor->image,
            'image_url' => $vendor->image && Storage::disk('public')->exists($vendor->image) 
                ? asset('storage/' . $vendor->image) 
                : null,
            'status' => $vendor->status,
            'created_at' => optional($vendor->created_at)?->toIso8601String(),
            'updated_at' => optional($vendor->updated_at)?->toIso8601String(),
            'deleted_at' => optional($vendor->deleted_at)?->toIso8601String(),
            'creator_name' => $vendor->creator?->name,
            'updater_name' => $vendor->updater?->name,
            'deleter_name' => $vendor->deleter?->name,
        ];
    }
}
