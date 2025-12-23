<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Permission;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            // Role Management Permissions
            [
                'name' => 'View Roles',
                'slug' => 'roles.view',
                'description' => 'View all roles in the system',
                'module' => 'Role Management',
                'is_system' => true
            ],
            [
                'name' => 'Create Roles',
                'slug' => 'roles.create',
                'description' => 'Create new roles',
                'module' => 'Role Management',
                'is_system' => true
            ],
            [
                'name' => 'Update Roles',
                'slug' => 'roles.update',
                'description' => 'Update existing roles',
                'module' => 'Role Management',
                'is_system' => true
            ],
            [
                'name' => 'Delete Roles',
                'slug' => 'roles.delete',
                'description' => 'Delete roles',
                'module' => 'Role Management',
                'is_system' => true
            ],

            // Permission Management Permissions
            [
                'name' => 'View Permissions',
                'slug' => 'permissions.view',
                'description' => 'View all permissions in the system',
                'module' => 'Permission Management',
                'is_system' => true
            ],
            [
                'name' => 'Create Permissions',
                'slug' => 'permissions.create',
                'description' => 'Create new permissions',
                'module' => 'Permission Management',
                'is_system' => true
            ],
            [
                'name' => 'Update Permissions',
                'slug' => 'permissions.update',
                'description' => 'Update existing permissions',
                'module' => 'Permission Management',
                'is_system' => true
            ],
            [
                'name' => 'Delete Permissions',
                'slug' => 'permissions.delete',
                'description' => 'Delete permissions',
                'module' => 'Permission Management',
                'is_system' => true
            ],

            // User Management Permissions
            [
                'name' => 'View Users',
                'slug' => 'users.view',
                'description' => 'View all users',
                'module' => 'User Management',
                'is_system' => true
            ],
            [
                'name' => 'Create Users',
                'slug' => 'users.create',
                'description' => 'Create new users',
                'module' => 'User Management',
                'is_system' => true
            ],
            [
                'name' => 'Update Users',
                'slug' => 'users.update',
                'description' => 'Update existing users',
                'module' => 'User Management',
                'is_system' => true
            ],
            [
                'name' => 'Delete Users',
                'slug' => 'users.delete',
                'description' => 'Delete users',
                'module' => 'User Management',
                'is_system' => true
            ],
            [
                'name' => 'Assign Roles',
                'slug' => 'users.assign-roles',
                'description' => 'Assign roles to users',
                'module' => 'User Management',
                'is_system' => true
            ],

            // Invoice Management Permissions
            [
                'name' => 'View Invoices',
                'slug' => 'invoices.view',
                'description' => 'View invoices',
                'module' => 'Invoice Management',
                'is_system' => true
            ],
            [
                'name' => 'Create Invoices',
                'slug' => 'invoices.create',
                'description' => 'Create new invoices',
                'module' => 'Invoice Management',
                'is_system' => true
            ],
            [
                'name' => 'Update Invoices',
                'slug' => 'invoices.update',
                'description' => 'Update existing invoices',
                'module' => 'Invoice Management',
                'is_system' => true
            ],
            [
                'name' => 'Delete Invoices',
                'slug' => 'invoices.delete',
                'description' => 'Delete invoices',
                'module' => 'Invoice Management',
                'is_system' => true
            ],
            [
                'name' => 'Approve Invoices',
                'slug' => 'invoices.approve',
                'description' => 'Approve pending invoices',
                'module' => 'Invoice Management',
                'is_system' => true
            ],
            [
                'name' => 'Reject Invoices',
                'slug' => 'invoices.reject',
                'description' => 'Reject pending invoices',
                'module' => 'Invoice Management',
                'is_system' => true
            ],
            [
                'name' => 'Mark Invoices as Paid',
                'slug' => 'invoices.mark-paid',
                'description' => 'Mark approved invoices as paid',
                'module' => 'Invoice Management',
                'is_system' => true
            ],
            [
                'name' => 'Restore Invoices',
                'slug' => 'invoices.restore',
                'description' => 'Restore soft-deleted invoices',
                'module' => 'Invoice Management',
                'is_system' => true
            ],
            [
                'name' => 'Force Delete Invoices',
                'slug' => 'invoices.force-delete',
                'description' => 'Permanently delete invoices',
                'module' => 'Invoice Management',
                'is_system' => true
            ],
            [
                'name' => 'Export Invoices',
                'slug' => 'invoices.export',
                'description' => 'Export invoices to various formats',
                'module' => 'Invoice Management',
                'is_system' => true
            ],
            [
                'name' => 'Generate Invoice Reports',
                'slug' => 'invoices.report',
                'description' => 'Generate invoice reports',
                'module' => 'Invoice Management',
                'is_system' => true
            ],

            // Payment Management Permissions
            [
                'name' => 'View Payments',
                'slug' => 'payments.view',
                'description' => 'View payment records',
                'module' => 'Payment Management',
                'is_system' => true
            ],
            [
                'name' => 'Create Payments',
                'slug' => 'payments.create',
                'description' => 'Create payment records',
                'module' => 'Payment Management',
                'is_system' => true
            ],
            [
                'name' => 'Update Payments',
                'slug' => 'payments.update',
                'description' => 'Update payment records',
                'module' => 'Payment Management',
                'is_system' => true
            ],
            [
                'name' => 'Delete Payments',
                'slug' => 'payments.delete',
                'description' => 'Delete payment records',
                'module' => 'Payment Management',
                'is_system' => true
            ],
            [
                'name' => 'Process Payments',
                'slug' => 'payments.process',
                'description' => 'Process pending payments',
                'module' => 'Payment Management',
                'is_system' => true
            ],

            // Reports & Analytics Permissions
            [
                'name' => 'View Dashboard',
                'slug' => 'dashboard.view',
                'description' => 'View dashboard and analytics',
                'module' => 'Reports & Analytics',
                'is_system' => true
            ],
            [
                'name' => 'View Reports',
                'slug' => 'reports.view',
                'description' => 'View all reports',
                'module' => 'Reports & Analytics',
                'is_system' => true
            ],
            [
                'name' => 'Generate Reports',
                'slug' => 'reports.generate',
                'description' => 'Generate new reports',
                'module' => 'Reports & Analytics',
                'is_system' => true
            ],
            [
                'name' => 'Export Reports',
                'slug' => 'reports.export',
                'description' => 'Export reports to various formats',
                'module' => 'Reports & Analytics',
                'is_system' => true
            ],

            // System Settings Permissions
            [
                'name' => 'View Settings',
                'slug' => 'settings.view',
                'description' => 'View system settings',
                'module' => 'System Settings',
                'is_system' => true
            ],
            [
                'name' => 'Update Settings',
                'slug' => 'settings.update',
                'description' => 'Update system settings',
                'module' => 'System Settings',
                'is_system' => true
            ],

            // Audit Log Permissions
            [
                'name' => 'View Audit Logs',
                'slug' => 'audit-logs.view',
                'description' => 'View system audit logs',
                'module' => 'Audit Logs',
                'is_system' => true
            ],
            [
                'name' => 'Export Audit Logs',
                'slug' => 'audit-logs.export',
                'description' => 'Export audit logs',
                'module' => 'Audit Logs',
                'is_system' => true
            ],
        ];

        // Create permissions (idempotent)
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(
                ['slug' => $permission['slug']],
                $permission
            );
        }
    }
}