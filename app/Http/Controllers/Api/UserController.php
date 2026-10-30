<?php

namespace App\Http\Controllers\Api;

use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\QueryException;
use Throwable;
use Illuminate\Validation\Rule;
use Spatie\Permission\PermissionRegistrar;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class UserController extends ApiBaseController
{
    private function resolvePlanOwner(User $user): User
    {
        if ($user->isAdmin()) {
            return $user;
        }

        if (DB::getSchemaBuilder()->hasColumn('users', 'parent_id') && !empty($user->parent_id)) {
            return User::find($user->parent_id) ?: $user;
        }

        return $user;
    }

    private function getCurrentPlanForAdmin(User $admin): ?SubscriptionPlan
    {
        return SubscriptionPlan::query()
            ->select('subscription_plan.*')
            ->join('subscription_user_plan', 'subscription_user_plan.subscription_id', '=', 'subscription_plan.id')
            ->where('subscription_user_plan.user_id', $admin->id)
            ->orderByDesc('subscription_user_plan.id')
            ->first();
    }

    private function getCurrentStaffCount(User $admin): int
    {
        return User::query()
            ->nonAdmin()
            ->where('parent_id', $admin->id)
            ->count();
    }

    private function allowedMatrixPermissionNames(): array
    {
        $actions = array_keys(config('crm_permissions.actions', []));
        $names = [];

        foreach (config('crm_permissions.modules', []) as $module => $meta) {
            $moduleActions = $meta['actions'] ?? $actions;

            foreach ($moduleActions as $action) {
                $names[] = "{$action}_{$module}";
            }
        }

        return array_values(array_unique($names));
    }

    private function syncMatrixPermissions(User $user, array $permissions = []): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $user->syncPermissions($permissions);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function index(Request $request)
    {
        $search = trim((string) $request->get('search', ''));

        // ✅ CHANGED: Filter out admin and super-admin users from staff list
        $users = User::with('roles')
            ->whereDoesntHave('roles', function ($query) {
                $query->whereIn('name', ['admin', 'super-admin']);
            })
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('address', 'like', "%{$search}%");
                });
            })
            ->tap(function ($query) use ($request) {
                \App\Support\ListFilters::apply($query,$request,['job_title','is_active'],['created_at']);
                $request->validate(['role_id'=>['nullable','integer']]);
                if ($request->filled('role_id')) $query->whereHas('roles', fn ($q) => $q->whereKey($request->integer('role_id')));
            })
            ->latest()
            ->paginate($request->integer('per_page',10));

        return response()->json([
            'success' => true,
            'message' => 'Users retrieved successfully.',
            'data' => $users,
        ]);
    }

    public function search(Request $request)
    {
        $query = trim((string) $request->get('q', ''));
        $excludeId = $request->get('exclude_id');

        if (empty($query)) {
            return response()->json([]);
        }

        $users = User::where(function ($q) use ($query) {
            $q->where('email', 'like', "%{$query}%")
                ->orWhere('phone', 'like', "%{$query}%");
        })
            ->when($excludeId, function ($q) use ($excludeId) {
                $q->where('id', '!=', $excludeId);
            })
            ->select('id', 'name', 'email', 'phone')
            ->limit(20)
            ->get();

        return response()->json($users);
    }

    public function store(Request $request)
    {
        $allowedPermissions = $this->allowedMatrixPermissionNames();
        $actor = $request->user();
        $planOwner = $actor ? $this->resolvePlanOwner($actor) : null;
        $currentPlan = $planOwner ? $this->getCurrentPlanForAdmin($planOwner) : null;

        if ($planOwner && $currentPlan) {
            $staffLimit = (int) $currentPlan->staff_limit;
            $currentStaffCount = $this->getCurrentStaffCount($planOwner);

            if ($staffLimit > 0 && $currentStaffCount >= $staffLimit) {
                return response()->json([
                    'success' => false,
                    'message' => "Staff limit reached for {$currentPlan->name}",
                ], 422);
            }
        }

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'phone' => ['required', 'regex:/^[0-9]{10}$/'],
            'whatsapp' => 'nullable|string|max:50',
            'address' => 'nullable|string',
            'image' => 'nullable|file|mimes:avif,webp,jpg,jpeg,png,gif,bmp,svg|max:2048',
            'permissions' => 'nullable|array',
            'permissions.*' => ['string', Rule::in($allowedPermissions)],
        ], [
            'phone.regex' => 'Phone number must be exactly 10 digits.',
            'image.mimes' => 'Please select a valid image! Allowed: AVIF, WEBP, JPG, JPEG, PNG, GIF, BMP, SVG.',
            'image.max' => 'Image size must not exceed 2MB.',
        ]);

        $avatarPath = null;
        if ($request->hasFile('image')) {
            $avatarPath = $request->file('image')->store('users', 'public');
        }

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'phone' => $data['phone'],
            'whatsapp' => $data['whatsapp'] ?? null,
            'address' => $data['address'],
            'avatar_path' => $avatarPath,
            'is_active' => true,
        ]);

        $ownershipUpdates = [];
        if ($planOwner && DB::getSchemaBuilder()->hasColumn('users', 'parent_id')) {
            $ownershipUpdates['parent_id'] = $planOwner->id;
        }
        if ($actor && DB::getSchemaBuilder()->hasColumn('users', 'created_by')) {
            $ownershipUpdates['created_by'] = $actor->id;
        }
        if (!empty($ownershipUpdates)) {
            $user->forceFill($ownershipUpdates)->save();
        }

        if ($staffRole = Role::where('name', 'staff')->first()) {
            $user->syncRoles([$staffRole->name]);
        }

        $this->syncMatrixPermissions($user, $data['permissions'] ?? []);
        app(\App\Services\UserLogService::class)->created($user, 'Created a Staff ' . $user->name);

        // ── Email: Staff Account Created ──────────────────────────────────────
        send_staff_created_notification($user, $data['password']);

        try {
            $phone = $user->whatsapp ?: $user->phone;
            if ($phone) {
                $roleText = $user->roles()->pluck('name')->map(fn($role) => ucfirst($role))->implode(', ');
                if ($roleText === '') {
                    $roleText = 'Staff';
                }

                app(\App\Services\WhatsAppService::class)->sendForModule(
                    'staff_account_created',
                    $phone,
                    [
                        $user->name ?? 'Staff',
                        $user->name ?? 'Staff',
                        $user->email ?? '--',
                        $roleText,
                    ],
                    $user->id
                );
            }
        } catch (Throwable $e) {
            Log::error('Staff create WhatsApp block failed', [
                'user_id' => $user->id ?? null,
                'error' => $e->getMessage(),
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'User created successfully.',
            'data' => $user->load(['roles', 'permissions']),
            'redirect' => route('users.index'),
        ], 201);
    }

    public function show(User $user)
    {
        return response()->json([
            'success' => true,
            'message' => 'User retrieved successfully.',
            'data' => $user->load(['roles', 'permissions']),
        ]);
    }

    public function update(Request $request, User $user)
    {
        $allowedPermissions = $this->allowedMatrixPermissionNames();

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'string', 'email', Rule::in([$user->email])],
            'password' => 'nullable|string|min:8',
            'phone' => ['required', 'regex:/^[0-9]{10}$/'],
            'whatsapp' => 'nullable|string|max:50',
            'address' => 'nullable|string',
            'image' => 'nullable|file|mimes:avif,webp,jpg,jpeg,png,gif,bmp,svg|max:2048',
            'permissions' => 'nullable|array',
            'permissions.*' => ['string', Rule::in($allowedPermissions)],
        ], [
            'email.in' => 'Email cannot be changed.',
            'phone.regex' => 'Phone number must be exactly 10 digits.',
            'image.mimes' => 'Please select a valid image! Allowed: AVIF, WEBP, JPG, JPEG, PNG, GIF, BMP, SVG.',
            'image.max' => 'Image size must not exceed 2MB.',
        ]);

        if ($request->hasFile('image')) {
            if ($user->avatar_path && Storage::disk('public')->exists($user->avatar_path)) {
                Storage::disk('public')->delete($user->avatar_path);
            }
            $user->avatar_path = $request->file('image')->store('users', 'public');
        }

        $user->name = $data['name'];
        $user->phone = $data['phone'];
        $user->whatsapp = $data['whatsapp'] ?? null;
        $user->address = $data['address'];
        if (!empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        }
        $user->save();

        $this->syncMatrixPermissions($user, $data['permissions'] ?? []);
        app(\App\Services\UserLogService::class)->updated($user, 'Updated a Staff ' . $user->name);

        try {
            $phone = $user->whatsapp ?: $user->phone;
            if ($phone) {
                $roleText = $user->roles()->pluck('name')->map(fn($role) => ucfirst($role))->implode(', ');
                if ($roleText === '') {
                    $roleText = 'Staff';
                }

                app(\App\Services\WhatsAppService::class)->sendForModule(
                    'staff_account_updated',
                    $phone,
                    [
                        $user->name ?? 'Staff',
                        $user->name ?? 'Staff',
                        $user->email ?? '--',
                        $roleText,
                    ],
                    $user->id
                );
            }
        } catch (Throwable $e) {
            Log::error('Staff update WhatsApp block failed', [
                'user_id' => $user->id ?? null,
                'error' => $e->getMessage(),
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'User updated successfully.',
            'data' => $user->load(['roles', 'permissions']),
            'redirect' => route('users.index'),
        ]);
    }

    public function destroy(User $user)
    {
        if (auth()->id() === $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'You cannot delete yourself.',
            ], 422);
        }

        try {
            if ($user->avatar_path && Storage::disk('public')->exists($user->avatar_path)) {
                Storage::disk('public')->delete($user->avatar_path);
            }

            app(\App\Services\UserLogService::class)->deleted($user, 'Deleted a Staff ' . $user->name);
            $user->delete();

            return response()->json([
                'success' => true,
                'message' => 'User deleted successfully.',
            ]);
        } catch (QueryException $exception) {
            // Foreign-key protected records exist for this user in other modules.
            $isConstraintError = (string) $exception->getCode() === '23000'
                || (int) ($exception->errorInfo[1] ?? 0) === 1451;

            if ($isConstraintError) {
                return response()->json([
                    'success' => false,
                    'message' => 'Module in use. This staff is linked with existing records, so it cannot be deleted.',
                ], 422);
            }

            return response()->json([
                'success' => false,
                'message' => 'Unable to delete staff right now. Please try again.',
            ], 500);
        } catch (Throwable $exception) {
            return response()->json([
                'success' => false,
                'message' => 'Unable to delete staff right now. Please try again.',
            ], 500);
        }
    }

    public function updateStatus(Request $request, User $user)
    {
        if (auth()->id() === $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'You cannot change your own status.',
            ], 422);
        }

        $data = $request->validate([
            'is_active' => ['required', 'boolean'],
        ]);

        $user->is_active = (bool) $data['is_active'];
        $user->save();
        app(\App\Services\UserLogService::class)->updated($user, 'Updated a Staff ' . $user->name);

        return response()->json([
            'success' => true,
            'message' => $user->name . ' marked as ' . ($user->is_active ? 'active' : 'inactive') . '.',
            'is_active' => (bool) $user->is_active,
        ]);
    }

    public function updatePermissions(Request $request, User $user)
    {
        $allowedPermissions = $this->allowedMatrixPermissionNames();

        $data = $request->validate([
            'permissions' => 'nullable|array',
            'permissions.*' => [
                'string',
                Rule::in($allowedPermissions),
            ],
        ]);

        $this->syncMatrixPermissions($user, $data['permissions'] ?? []);
        app(\App\Services\UserLogService::class)->updated($user, 'Updated a Staff ' . $user->name);

        return response()->json([
            'success' => true,
            'message' => 'Permissions updated successfully.',
        ]);
    }
}
