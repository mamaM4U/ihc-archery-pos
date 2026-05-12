<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $create = fn ($name) => Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);

        // dashboard permissions
        $create('dashboard-access');

        // users permissions
        $create('users-access');
        $create('users-create');
        $create('users-update');
        $create('users-delete');

        // roles permissions
        $create('roles-access');
        $create('roles-create');
        $create('roles-update');
        $create('roles-delete');

        // permissions permissions
        $create('permissions-access');
        $create('permissions-create');
        $create('permissions-update');
        $create('permissions-delete');

        //permission categories
        $create('categories-access');
        $create('categories-create');
        $create('categories-edit');
        $create('categories-delete');

        //permission products
        $create('products-access');
        $create('products-create');
        $create('products-edit');
        $create('products-delete');

        //permission customers
        $create('customers-access');
        $create('customers-create');
        $create('customers-edit');
        $create('customers-delete');

        //permission transactions
        $create('transactions-access');

        // permission receivables & payables & purchases
        $create('receivables-access');
        $create('receivables-pay');
        $create('payables-access');
        $create('payables-pay');
        $create('suppliers-access');
        $create('purchases-access');
        $create('purchases-create');
        $create('purchases-finalize');
        $create('purchase-returns-access');
        $create('purchase-returns-create');
        $create('purchase-returns-complete');

        // permission reports
        $create('reports-access');
        $create('profits-access');

        // payment settings
        $create('payment-settings-access');

        // stock opnames
        $create('stock-opnames-access');
        $create('stock-opnames-create');
        $create('stock-opnames-finalize');
        $create('stock-mutations-access');

        // sales returns
        $create('sales-returns-access');
        $create('sales-returns-create');
        $create('sales-returns-complete');

        // cashier shifts
        $create('cashier-shifts-access');
        $create('cashier-shifts-open');
        $create('cashier-shifts-close');
        $create('cashier-shifts-force-close');

        // audit logs
        $create('audit-logs-access');

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
