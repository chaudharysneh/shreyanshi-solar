<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Services\CustomerDocumentName;
use Illuminate\Http\Request;

class DocumentNameController extends Controller
{
    public function show(Request $request, CustomerDocumentName $names)
    {
        $data = $request->validate(['customer_id' => ['required','integer'], 'type' => ['required','in:EST,INV']]);
        $permission = $data['type'] === 'EST' ? 'create_estimates' : 'create_invoices';
        if (!$request->user()->hasMatrixPermission($permission)) return response()->json(['message' => 'Access denied.'], 403);
        $customer = Customer::visibleTo($request->user())->findOrFail($data['customer_id']);
        return response()->json(['name' => $names->next($customer->id, $data['type'])]);
    }
}
