<?php

namespace App\Http\Controllers;

use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    private function getSubscriptionStaffMeta(?User $user): array
    {
        $meta = [
            'staffLimit' => 0,
            'currentStaffCount' => 0,
            'planName' => null,
        ];

        if (!$user) {
            return $meta;
        }

        $planOwner = $user->isAdmin()
            ? $user
            : (Schema::hasColumn('users', 'parent_id') && !empty($user->parent_id)
                ? User::find($user->parent_id) ?? $user
                : $user);

        if (!Schema::hasTable('subscription_user_plan')) {
            return $meta;
        }

        $assignment = DB::table('subscription_user_plan')
            ->where('user_id', $planOwner->id)
            ->orderByDesc('id')
            ->first();

        $plan = $assignment ? SubscriptionPlan::find($assignment->subscription_id) : null;

        if (Schema::hasColumn('users', 'parent_id')) {
            $meta['currentStaffCount'] = User::query()
                ->nonAdmin()
                ->where('parent_id', $planOwner->id)
                ->count();
        }

        $meta['staffLimit'] = (int) ($plan->staff_limit ?? 0);
        $meta['planName'] = $plan->name ?? null;

        return $meta;
    }

    public function index()
    {
        $subscriptionMeta = $this->getSubscriptionStaffMeta(auth()->user());

        return view('crm.users.index', $subscriptionMeta);
    }

    public function create()
    {
        $permissionMatrix = config('crm_permissions.modules', []);
        $permissionActions = config('crm_permissions.actions', []);

        return view('crm.users.create', compact('permissionMatrix', 'permissionActions'));
    }

    public function show(User $user)
    {
        $user->load('roles.permissions');
        return view('crm.users.show', compact('user'));
    }

    public function image(User $user)
    {
        if (!auth()->check() || (auth()->id() !== $user->id && !auth()->user()->isMainAdmin())) {
            abort(403);
        }

        if (!$user->avatar_path || !Storage::disk('public')->exists($user->avatar_path)) {
            abort(404);
        }

        $fullPath = Storage::disk('public')->path($user->avatar_path);

        return Response::file($fullPath);
    }

    public function edit(User $user)
    {
        $permissionMatrix = config('crm_permissions.modules', []);
        $permissionActions = config('crm_permissions.actions', []);
        $userPermissions = $user->permissions->pluck('name')->all();

        return view('crm.users.edit', compact('user', 'permissionMatrix', 'permissionActions', 'userPermissions'));
    }

    public function export(Request $request)
    {
        $fileName = 'users_' . date('Y-m-d_H-i-s') . '.csv';
        $query = User::with('roles')
            ->latest()
            ->when($request->search, function ($q) use ($request) {
                $search = $request->search;
                $q->where(function ($sub) use ($search) {
                    $sub->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('address', 'like', "%{$search}%");
                });
            })
            ->when($request->role, function ($q) use ($request) {
                $q->whereHas('roles', fn($role) => $role->where('name', $request->role));
            })
            ->when($request->from_date && $request->to_date, function ($q) use ($request) {
                $q->whereBetween('created_at', [
                    $request->from_date . ' 00:00:00',
                    $request->to_date . ' 23:59:59',
                ]);
            });

        $users = $query->get();

        $headers = [
            "Content-type" => "text/csv",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma" => "no-cache",
            "Cache-Control" => "must-revalidate, post-check=0, pre-check=0",
            "Expires" => "0"
        ];

        $columns = ['Sr no.', 'Name', 'Email', 'Contact', 'Address', 'Role', 'Status'];

        $callback = function () use ($users, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            $i = 1;
            foreach ($users as $user) {
                $contact = $user->phone ? '="' . preg_replace('/\D+/', '', $user->phone) . '"' : '';
                $role = $user->roles->pluck('name')->map(fn($name) => ucfirst($name))->implode('|') ?: 'Staff';

                fputcsv($file, [
                    $i++,
                    $user->name,
                    $user->email,
                    $contact,
                    $user->address,
                    $role,
                    ($user->is_active ?? true) ? 'Active' : 'Inactive',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function import(Request $request)
    {
        $request->validate([
            'import_file' => ['required', 'file', 'mimes:csv'],
        ]);

        $path = $request->file('import_file')->getRealPath();
        $handle = fopen($path, 'r');

        if ($handle === false) {
            return redirect()->route('users.index')->with('error', 'Unable to read the import file.');
        }

        $header = fgetcsv($handle);
        $headerMap = collect($header ?: [])->map(function ($value) {
            return strtolower(trim((string) $value));
        })->values()->all();
        $imported = 0;

        while (($row = fgetcsv($handle)) !== false) {
            $rowData = [];
            foreach ($headerMap as $index => $column) {
                $rowData[$column] = trim((string) ($row[$index] ?? ''));
            }

            $name = $rowData['name'] ?? '';
            $email = $rowData['email'] ?? '';
            $phone = $rowData['contact'] ?? ($rowData['phone'] ?? '');
            $address = $rowData['address'] ?? '';
            $rolesRaw = $rowData['role'] ?? ($rowData['roles'] ?? '');
            $password = (string) ($rowData['password'] ?? '');

            if ($name === '' || $email === '') {
                continue;
            }

            $phone = preg_replace('/[^0-9]/', '', $phone);
            if ($phone !== '' && strlen($phone) > 10) {
                $phone = substr($phone, -10);
            }

            $user = User::firstOrNew(['email' => $email]);
            $user->name = $name;
            $user->phone = $phone ?: $user->phone;
            $user->address = $address ?: $user->address;

            if (!$user->exists) {
                $user->password = Hash::make($password !== '' ? $password : 'Password@123');
            } elseif ($password !== '') {
                $user->password = Hash::make($password);
            }

            $user->save();

            if ($rolesRaw !== '') {
                $roles = collect(preg_split('/[|,]/', $rolesRaw))
                    ->map(fn($role) => trim($role))
                    ->filter()
                    ->map(fn($role) => strtolower($role))
                    ->unique()
                    ->values()
                    ->all();

                $roles = Role::query()
                    ->whereIn('name', $roles)
                    ->pluck('name')
                    ->values()
                    ->all();

                if (!empty($roles)) {
                    $user->syncRoles($roles);
                }
            } elseif (!$user->roles()->exists() && $staffRole = Role::where('name', 'staff')->first()) {
                $user->syncRoles([$staffRole->name]);
            }

            $imported++;
        }

        fclose($handle);

        return redirect()->route('users.index')->with('success', "{$imported} user(s) imported successfully.");
    }

    public function apiSearch(Request $request)
    {
        $search = $request->get('q');

        $users = User::query()
            ->when($search, function ($query, $search) {
                return $query->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            })
            ->limit(20)
            ->get(['id', 'name', 'email']);

        return response()->json($users);
    }
}
