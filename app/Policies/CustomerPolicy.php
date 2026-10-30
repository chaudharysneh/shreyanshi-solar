<?php

namespace App\Policies;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CustomerPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        // Give admins full access to all abilities
        if (method_exists($user, 'isAdmin') && $user->isAdmin()) {
            return true;
        }

        return null;
    }

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Customer $customer): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Customer $customer): bool
    {
        // Admin access is handled in before() method
        // ✅ CHANGED: All staff can now edit any customer
        return true;
    }

    public function delete(User $user, Customer $customer): bool
    {
        // Admin access is handled in before() method
        // ✅ CHANGED: All staff can now delete any customer
        return true;
    }

    /**
     * ✅ REMOVED: isAssignedThroughModule method
     * This method is no longer used - permission logic is simplified
     * Staff can edit/delete customers they created, regardless of assignments
     */

    /**
     * Discover all tables that have both customer_id and assigned_user_id
     */
    private function discoverAssignableCustomerTables(): array
    {
        $customerTable = 'customers';

        $tables = collect(Schema::getTableListing())
            ->filter(function (string $table) use ($customerTable) {
                if ($table === $customerTable) {
                    return false;
                }

                return Schema::hasColumn($table, 'customer_id')
                    && Schema::hasColumn($table, 'assigned_user_id');
            })
            ->values()
            ->all();

        return $tables;
    }
}
