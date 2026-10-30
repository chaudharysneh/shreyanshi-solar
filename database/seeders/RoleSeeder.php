<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // List of all permissions
        $permissions = [

            // Dashboard
            'view_dashboard',

            // Meetings (ADDED)
            'view_meetings', 'create_meetings', 'edit_meetings', 'delete_meetings',

            // Leads
            'view_leads', 'create_leads', 'edit_leads', 'delete_leads',

            // Followups
            'view_followups', 'create_followups', 'edit_followups', 'delete_followups',

            // Tour Packages
            'view_packages', 'create_packages', 'edit_packages', 'delete_packages',

            // Quotations
            'view_quotations', 'create_quotations', 'edit_quotations', 'delete_quotations',

            // Bookings
            'view_bookings', 'create_bookings', 'edit_bookings', 'delete_bookings',

            // Masters
            'view_masters', 'create_masters', 'edit_masters', 'delete_masters',

            // Users & Roles
            'view_users', 'create_users', 'edit_users', 'delete_users',
            'view_roles', 'create_roles', 'edit_roles', 'delete_roles',

            // Settings
            'view_settings', 'edit_settings',

            // Products & Services
            'view_products', 'create_products', 'edit_products', 'delete_products',
            'view_bom', 'create_bom', 'edit_bom', 'delete_bom',
            'view_make', 'create_make', 'edit_make', 'delete_make',
            'view_warranty', 'create_warranty', 'edit_warranty', 'delete_warranty',
            'view_technology', 'create_technology', 'edit_technology', 'delete_technology',
            'view_sales', 'create_sales', 'edit_sales', 'delete_sales',
            'view_purchases', 'create_purchases', 'edit_purchases', 'delete_purchases',
            'view_inventory', 'create_inventory', 'edit_inventory', 'delete_inventory',
            'view_categories', 'create_categories', 'edit_categories', 'delete_categories',
            'view_vendors', 'create_vendors', 'edit_vendors', 'delete_vendors',
            'view_handover_persons', 'create_handover_persons', 'edit_handover_persons', 'delete_handover_persons',
            'view_estimates', 'create_estimates', 'edit_estimates', 'delete_estimates',
            'view_invoices', 'create_invoices', 'edit_invoices', 'delete_invoices',
            'view_templates', 'create_templates', 'edit_templates', 'delete_templates',
            'view_services', 'create_services', 'edit_services', 'delete_services',

            // Documents
            'view_documents', 'create_documents', 'delete_documents',

            // Customers
            'view_customers', 'create_customers', 'edit_customers', 'delete_customers',
        ];

        // Create permissions with guard_name = web
        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name'       => $permission,
                'guard_name' => 'web'
            ]);
        }

        // -------------------------
        // ROLES
        // -------------------------

        $superAdmin = Role::firstOrCreate([
            'name'       => 'super-admin',
            'guard_name' => 'web'
        ]);

        $admin = Role::firstOrCreate([
            'name'       => 'admin',
            'guard_name' => 'web'
        ]);

        $manager = Role::firstOrCreate([
            'name'       => 'manager',
            'guard_name' => 'web'
        ]);

        $staff = Role::firstOrCreate([
            'name'       => 'staff',
            'guard_name' => 'web'
        ]);

        // Super Admin → All permissions
        $superAdmin->syncPermissions(Permission::all());

        // Admin → All except delete_users + delete_roles
        $admin->syncPermissions(
            Permission::whereNotIn('name', ['delete_users', 'delete_roles'])->get()
        );

        // Manager → Limited permissions
        $manager->syncPermissions([
            'view_dashboard',

            // Meetings
            'view_meetings', 'create_meetings', 'edit_meetings',

            'view_leads', 'create_leads', 'edit_leads',

            'view_followups', 'create_followups', 'edit_followups',

            'view_packages', 'create_packages', 'edit_packages',

            'view_quotations', 'create_quotations', 'edit_quotations',

            'view_bookings', 'create_bookings', 'edit_bookings',

            'view_masters',

            'view_products',
            'view_bom', 'create_bom', 'edit_bom',
            'view_make', 'create_make', 'edit_make',
            'view_warranty', 'create_warranty', 'edit_warranty',
            'view_technology', 'create_technology', 'edit_technology',
            'view_sales', 'create_sales', 'edit_sales',
            'view_purchases', 'create_purchases', 'edit_purchases',
            'view_inventory', 'edit_inventory',
            'view_categories', 'create_categories', 'edit_categories',
            'view_vendors', 'create_vendors', 'edit_vendors',
            'view_handover_persons', 'create_handover_persons', 'edit_handover_persons',
            'view_estimates', 'create_estimates', 'edit_estimates',
            'view_invoices', 'create_invoices', 'edit_invoices',
            'view_templates', 'create_templates', 'edit_templates',
            'view_services',

            'view_documents', 'create_documents',

            'view_customers',
        ]);

        // Staff → Basic access
        $staff->syncPermissions([
            'view_dashboard',

            // Meetings
            'view_meetings',

            'view_leads', 'create_leads', 'edit_leads',

            'view_followups', 'create_followups', 'edit_followups',

            'view_quotations', 'create_quotations',

            'view_bookings',

            'view_masters',

            'view_bom',
            'view_make',
            'view_warranty',
            'view_technology',
            'view_sales',
            'view_purchases',
            'view_inventory',
            'view_categories',
            'view_vendors',
            'view_handover_persons',
            'view_estimates',
            'view_invoices',
            'view_templates',
            'view_documents', 'create_documents',

            'view_customers',
        ]);

        // Assign super-admin to the first admin user
        $adminUser = User::where('email', 'like', 'admin%')->first()
                      ?? User::first();

        if ($adminUser && $adminUser->roles->isEmpty()) {
            $adminUser->assignRole('super-admin');
            $this->command->info("Assigned super-admin to: {$adminUser->email}");
        }

        // Display summary table
        $this->command->info('Roles & Permissions seeded successfully!');
        $this->command->table(
            ['Role', 'Permissions Count'],
            Role::withCount('permissions')->get()
                ->map(fn($r) => [$r->name, $r->permissions_count])
                ->toArray()
        );
    }
}
