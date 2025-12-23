<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\Permission;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create Super Admin role
        $superAdmin = Role::firstOrCreate(
            ['slug' => 'super_admin'],
            [
                'name' => 'Super Admin',
                'description' => 'Has complete access to all system features and bypasses all permission checks',
                'is_system' => true
            ]
        );

        // Super Admin gets ALL permissions
        $allPermissions = Permission::pluck('id')->toArray();
        $superAdmin->permissions()->syncWithoutDetaching($allPermissions);

        // Create Vendor role
        $vendor = Role::firstOrCreate(
            ['slug' => 'vendor'],
            [
                'name' => 'Vendor',
                'description' => 'Can manage their own invoices and view payment status',
                'is_system' => true
            ]
        );

        // Vendor permissions
        $vendorPermissions = Permission::whereIn('slug', [
            'dashboard.view',
            'invoices.view',
            'invoices.create',
            'invoices.update',
            'invoices.delete',
            'invoices.export',
            'payments.view',
            'reports.view',
            'reports.export',
        ])->pluck('id')->toArray();

        $vendor->permissions()->syncWithoutDetaching($vendorPermissions);

        // Create Accountant role
        $accountant = Role::firstOrCreate(
            ['slug' => 'accountant'],
            [
                'name' => 'Accountant',
                'description' => 'Can manage all invoices, approve/reject them, and process payments',
                'is_system' => true
            ]
        );

        // Accountant permissions
        $accountantPermissions = Permission::whereIn('slug', [
            'dashboard.view',

            // Invoice management
            'invoices.view',
            'invoices.update',
            'invoices.delete',
            'invoices.approve',
            'invoices.reject',
            'invoices.mark-paid',
            'invoices.restore',
            'invoices.force-delete',
            'invoices.export',
            'invoices.report',

            // Payment management
            'payments.view',
            'payments.create',
            'payments.update',
            'payments.delete',
            'payments.process',

            // User management (limited)
            'users.view',

            // Reports
            'reports.view',
            'reports.generate',
            'reports.export',

            // Audit logs
            'audit-logs.view',
            'audit-logs.export',
        ])->pluck('id')->toArray();

        $accountant->permissions()->syncWithoutDetaching($accountantPermissions);
    }
}