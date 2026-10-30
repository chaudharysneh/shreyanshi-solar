<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ChannelPartner;
use Illuminate\Http\Request;

class ChannelPartnerController extends Controller
{
    private function canAccess(Request $request): bool
    {
        return $request->user()->hasMatrixPermission('create_leads') || $request->user()->hasMatrixPermission('edit_leads')
            || $request->user()->hasMatrixPermission('create_customers') || $request->user()->hasMatrixPermission('edit_customers');
    }

    public function index(Request $request)
    {
        if (!$this->canAccess($request)) return response()->json(['message' => 'You cannot manage channel partners.'], 403);
        $query = ChannelPartner::where('status', 'active');
        if ($request->filled('q')) $query->where('name', 'like', '%' . $request->string('q') . '%');
        $partners = $query->orderBy('name')->paginate(20);
        return response()->json(['results' => $partners->map(fn ($partner) => ['id' => $partner->id, 'text' => $partner->name]), 'pagination' => ['more' => $partners->hasMorePages()]]);
    }

    public function store(Request $request)
    {
        if (!$this->canAccess($request)) return response()->json(['message' => 'You cannot manage channel partners.'], 403);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:2000'],
        ]);
        return response()->json(['data' => ChannelPartner::create($data)], 201);
    }
}
