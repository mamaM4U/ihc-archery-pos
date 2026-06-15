<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    // Refactor the RoleSeeder to improve readability and avoid repetitive code
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->createRoleWithPermissions('users-access', '%users%');
        $this->createRoleWithPermissions('roles-access', '%roles%');
        $this->createRoleWithPermissions('permission-access', '%permissions%');
        $this->createRoleWithPermissions('categories-access', '%categories%');
        $this->createRoleWithPermissions('products-access', '%products%');
        $this->createRoleWithPermissions('customers-access', '%customers%');
        $this->createRoleWithPermissions('transactions-access', '%transactions%');
        $this->createRoleWithPermissions('receivables-access', '%receivables%');
        $this->createRoleWithPermissions('payables-access', '%payables%');
        $this->createRoleWithPermissions('suppliers-access', '%suppliers%');
        $this->createRoleWithPermissions('purchases-access', '%purchases%');
        $this->createRoleWithPermissions('purchase-returns-access', '%purchase-returns%');
        $this->createRoleWithPermissions('reports-access', '%reports%');
        $this->createRoleWithPermissions('profits-access', '%profits%');
        $this->createRoleWithPermissions('payment-settings-access', '%payment-settings%');
        $this->createRoleWithPermissions('stock-opnames-access', '%stock-opnames%');
        $this->createRoleWithPermissions('stock-mutations-access', '%stock-mutations%');
        $this->createRoleWithPermissions('sales-returns-access', '%sales-returns%');
        $this->createRoleWithPermissions('cashier-shifts-access', '%cashier-shifts%');
        $this->createRoleWithPermissions('audit-logs-access', '%audit-logs%');

        $superAdminRole = Role::firstOrCreate(['name' => 'super-admin']);
        $superAdminRole->syncPermissions(Permission::all());

        // Create rewrite roles for archery management
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $adminRole->syncPermissions(Permission::all());

        Role::firstOrCreate(['name' => 'coach']);
        Role::firstOrCreate(['name' => 'guardian']);
        Role::firstOrCreate(['name' => 'member']);

        // Create cashier role with basic permissions for public registration
        $cashierRole = Role::firstOrCreate(['name' => 'cashier']);
        $cashierPermissions = Permission::whereIn('name', [
            'dashboard-access',
            'transactions-access',
            'cashier-shifts-access',
            'cashier-shifts-open',
            'cashier-shifts-close',
            'customers-access',
            'customers-create',
            'receivables-access',
            'receivables-pay',
            'payables-access',
            'payables-pay',
            'suppliers-access',
        ])->get();
        $cashierRole->syncPermissions($cashierPermissions);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function createRoleWithPermissions($roleName, $permissionNamePattern)
    {
        $permissions = Permission::where('name', 'like', $permissionNamePattern)->get();
        $role = Role::firstOrCreate(['name' => $roleName]);
        $role->syncPermissions($permissions);
    }
}
