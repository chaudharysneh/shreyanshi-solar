<?php

namespace App\Http\Controllers;

use App\Models\Vendor;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class VendorController extends Controller
{
    public function index()
    {
        return view('crm.vendors.index');
    }

    public function create()
    {
        return view('crm.vendors.create');
    }

    public function edit(Vendor $vendor)
    {
        return view('crm.vendors.edit', compact('vendor'));
    }

    public function show(Vendor $vendor)
    {
        $vendor->load(['creator', 'updater', 'deleter']);

        return view('crm.vendors.view', compact('vendor'));
    }

    public function image(Vendor $vendor)
    {
        if (!$vendor->image || !Storage::disk('public')->exists($vendor->image)) {
            abort(404);
        }

        return response()->file(Storage::disk('public')->path($vendor->image));
    }

    public function export(): StreamedResponse
    {
        $fileName = 'vendors_' . date('Y-m-d_H-i-s') . '.csv';
        $vendors = $this->scopeOwnedRecords(
            Vendor::with(['creator'])
        )->latest()->get();

        $headers = [
            'Content-type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=$fileName",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $columns = ['No', 'Vendor Name', 'Email', 'Phone', 'Address', 'Created By', 'Created At'];

        $callback = function () use ($vendors, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            foreach ($vendors as $index => $vendor) {
                fputcsv($file, [
                    $index + 1,
                    $vendor->name,
                    $vendor->email ?? '--',
                    $vendor->phone ?? '--',
                    $vendor->address ?? '--',
                    optional($vendor->creator)->name ?? '-',
                    optional($vendor->created_at)?->timezone('Asia/Kolkata')->format('d-m-Y h:i A') ?? '--',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
