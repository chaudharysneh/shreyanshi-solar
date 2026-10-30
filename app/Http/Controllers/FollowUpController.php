<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\FollowUp;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;

class FollowUpController extends Controller
{
    public function index()
    {
        return view('crm.followups.index');
    }

    public function create()
    {
        $leads = Lead::orderBy('name')->get();

        if (auth()->user()->isAdmin()) {
            $users = User::orderBy('name')->get();
        } else {
            $users = User::where('id', auth()->id())->orderBy('name')->get();
        }

        return view('crm.followups.create', compact('leads', 'users'));
    }


    public function show($id)
    {
        $followUp = FollowUp::with(['lead', 'customer', 'assignedUser', 'creator', 'statusHistories.updater'])->findOrFail($id);
        $this->authorize('view', $followUp);

        return view('crm.followups.show', compact('followUp'));
    }

    // public function edit($id)
    // {
    //     $followUp = FollowUp::with(['statusHistories.updater'])->findOrFail($id);
    //     $leads = Lead::orderBy('name')->get();
    //     $customers = Customer::orderBy('name')->get();
    //     $users = User::role(['Admin', 'staff'])->orderBy('name')->get();

    //     return view('crm.followups.edit', compact('followUp', 'leads', 'customers', 'users'));
    // }

    public function edit($id)
    {
        $followUp = FollowUp::with(['statusHistories.updater'])->findOrFail($id);
        $this->authorize('update', $followUp);
        $leads = Lead::orderBy('name')->get();
        $customers = Customer::visibleTo(auth()->user())->orderBy('name')->get();

        if (auth()->user()->isAdmin()) {
            $users = User::orderBy('name')->get();
        } else {
            $users = User::where('id', auth()->id())->orderBy('name')->get();
        }

        return view('crm.followups.edit', compact('followUp', 'leads', 'customers', 'users'));
    }

    public function export(Request $request)
    {
        $fileName = 'followups_' . date('Y-m-d_H-i-s') . '.csv';

        $followups = $this->scopeOwnedRecords(
            FollowUp::with(['lead', 'assignedUser', 'creator'])
        )->latest('created_at')
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = trim((string) $request->search);

                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('purpose', 'like', "%{$search}%")
                        ->orWhere('comment', 'like', "%{$search}%")
                        ->orWhere('status', 'like', "%{$search}%")
                        ->orWhere('priority', 'like', "%{$search}%")
                        ->orWhereHas('lead', function ($leadQuery) use ($search) {
                            $leadQuery->where('name', 'like', "%{$search}%");
                        })
                        ->orWhereHas('assignedUser', function ($userQuery) use ($search) {
                            $userQuery->where('name', 'like', "%{$search}%");
                        });
                });
            })
            ->get();

        $headers = [
            'Content-type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=$fileName",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $columns = ['Sr.No', 'Lead Name', 'Staff Name', 'Purpose', 'Created At', 'Follow Up Date', 'Status'];

        $callback = function () use ($followups, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            foreach ($followups as $index => $followup) {
                fputcsv($file, [
                    $index + 1,
                    $followup->lead?->name ?? '-',
                    $followup->assignedUser?->name ?? '-',
                    $followup->purpose ?? '-',
                    $followup->created_at?->timezone('Asia/Kolkata')->format('d-m-Y h:i A') ?? '--',
                    $followup->follow_up_at?->timezone('Asia/Kolkata')->format('d-m-Y h:i A') ?? '--',
                    ucfirst($followup->status ?? 'pending'),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function toggle($id)
    {
        $followUp = FollowUp::findOrFail($id);
        $this->authorize('update', $followUp);
        $followUp->status = $followUp->status === 'completed' ? 'pending' : 'completed';
        $followUp->updated_by = auth()->id();
        $followUp->save();

        return response()->json(['success' => true, 'status' => $followUp->status]);
    }
}

