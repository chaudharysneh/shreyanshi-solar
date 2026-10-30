<?php

namespace App\Http\Controllers\Api;

use App\Models\HandoverPerson;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class HandoverPersonController extends ApiBaseController
{
    public function index(Request $request)
    {
        $search = trim((string) $request->get('search', ''));
        $perPage = max(1, min((int) $request->get('per_page', 10), 100));

        $handoverPersons = HandoverPerson::query()
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($innerQuery) use ($search) {
                    $innerQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('address', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate($perPage)
            ->appends($request->query());

        return response()->json([
            'success' => true,
            'message' => 'Handover persons retrieved successfully.',
            'data' => $handoverPersons,
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
        $data['status'] = $data['status'] ?? 'active';
        $data['user_id'] = $data['user_id'] ?? Auth::id();

        $handoverPerson = HandoverPerson::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Handover person created successfully.',
            'data' => $handoverPerson,
        ], 201);
    }

    public function show(HandoverPerson $handoverPerson)
    {
        return response()->json([
            'success' => true,
            'message' => 'Handover person retrieved successfully.',
            'data' => $handoverPerson,
        ]);
    }

    public function update(Request $request, HandoverPerson $handoverPerson)
    {
        $validator = Validator::make($request->all(), $this->rules($handoverPerson), $this->messages());

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();
        $data['status'] = $data['status'] ?? ($handoverPerson->status ?: 'active');
        $data['user_id'] = $handoverPerson->user_id ?: Auth::id();

        $handoverPerson->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Handover person updated successfully.',
            'data' => $handoverPerson->fresh(),
        ]);
    }

    public function destroy(HandoverPerson $handoverPerson)
    {
        $handoverPerson->delete();

        return response()->json([
            'success' => true,
            'message' => 'Handover person deleted successfully.',
        ]);
    }

    private function rules(?HandoverPerson $handoverPerson = null): array
    {
        return [
            'name' => ['required', 'string', 'min:3', 'max:255'],
            'phone' => [
                'required',
                'regex:/^[0-9]{10}$/',
                Rule::unique('handover_persons', 'phone')->ignore($handoverPerson?->id)->whereNull('deleted_at'),
            ],
            'address' => ['nullable', 'string', 'max:2000'],
            'status' => ['nullable', 'string', 'max:50'],
        ];
    }

    private function messages(): array
    {
        return [
            'name.required' => 'Please enter handover person name.',
            'name.min' => 'Name must be at least 3 characters.',
            'phone.required' => 'Please enter phone number.',
            'phone.regex' => 'Phone number must be exactly 10 digits.',
            'phone.unique' => 'This phone number already exists.',
        ];
    }
}
