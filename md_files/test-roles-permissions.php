<?php

/**
 * Comprehensive Test Script for Roles & Permissions System
 * This script performs deep testing of all components
 */

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use App\Models\User;
use App\Models\Role;
use App\Models\Permission;
use App\Models\Invoice;

$app = require __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Test results array
$testResults = [];
$totalTests = 0;
$passedTests = 0;
$failedTests = 0;

// Helper function to run a test
function runTest($name, $test) {
    global $testResults, $totalTests, $passedTests, $failedTests;
    $totalTests++;

    try {
        $result = $test();
        if ($result === true) {
            $testResults[] = ["✅ PASS", $name, "Test passed successfully"];
            $passedTests++;
            echo "✅ PASS: $name\n";
        } else {
            $testResults[] = ["❌ FAIL", $name, $result ?: "Test failed"];
            $failedTests++;
            echo "❌ FAIL: $name - " . ($result ?: "Test failed") . "\n";
        }
    } catch (Exception $e) {
        $testResults[] = ["❌ ERROR", $name, $e->getMessage()];
        $failedTests++;
        echo "❌ ERROR: $name - " . $e->getMessage() . "\n";
    }
}

echo "===========================================\n";
echo "ROLES & PERMISSIONS COMPREHENSIVE TEST SUITE\n";
echo "===========================================\n\n";

// Start database transaction for testing
DB::beginTransaction();

try {
    // ========================================
    // SECTION 1: DATABASE AND SEEDERS
    // ========================================
    echo "\n📋 TESTING DATABASE STRUCTURE AND SEEDERS\n";
    echo "----------------------------------------\n";

    runTest("Tables exist", function() {
        $tables = ['roles', 'permissions', 'role_permission'];
        foreach ($tables as $table) {
            if (!DB::getSchemaBuilder()->hasTable($table)) {
                return "Table '$table' does not exist";
            }
        }
        return true;
    });

    runTest("Users table has role_id column", function() {
        return DB::getSchemaBuilder()->hasColumn('users', 'role_id');
    });

    runTest("Seeders create initial data", function() {
        Artisan::call('db:seed', ['--class' => 'PermissionSeeder']);
        Artisan::call('db:seed', ['--class' => 'RoleSeeder']);

        $rolesCount = Role::count();
        $permissionsCount = Permission::count();

        if ($rolesCount < 3) return "Expected at least 3 roles, got $rolesCount";
        if ($permissionsCount < 30) return "Expected at least 30 permissions, got $permissionsCount";

        return true;
    });

    runTest("Three initial roles exist", function() {
        $superAdmin = Role::where('slug', 'super_admin')->first();
        $vendor = Role::where('slug', 'vendor')->first();
        $accountant = Role::where('slug', 'accountant')->first();

        if (!$superAdmin) return "Super Admin role not found";
        if (!$vendor) return "Vendor role not found";
        if (!$accountant) return "Accountant role not found";

        return true;
    });

    runTest("Seeders are idempotent", function() {
        $initialRoles = Role::count();
        $initialPermissions = Permission::count();

        Artisan::call('db:seed', ['--class' => 'PermissionSeeder']);
        Artisan::call('db:seed', ['--class' => 'RoleSeeder']);

        if (Role::count() != $initialRoles) return "Roles count changed after re-seeding";
        if (Permission::count() != $initialPermissions) return "Permissions count changed after re-seeding";

        return true;
    });

    // ========================================
    // SECTION 2: ROLE MODEL FUNCTIONALITY
    // ========================================
    echo "\n🎭 TESTING ROLE MODEL\n";
    echo "----------------------------------------\n";

    runTest("Create new role", function() {
        $role = Role::create([
            'name' => 'Test Manager',
            'slug' => 'test_manager',
            'description' => 'Test role for testing'
        ]);

        return $role->exists && $role->name == 'Test Manager';
    });

    runTest("Auto-generate slug from name", function() {
        $role = Role::create([
            'name' => 'Quality Assurance Lead',
            'description' => 'QA Lead role'
        ]);

        return $role->slug == 'quality-assurance-lead';
    });

    runTest("System roles cannot be deleted", function() {
        $superAdmin = Role::where('slug', 'super_admin')->first();
        $result = $superAdmin->delete();

        // Should return false and role should still exist
        return $result === false && Role::where('slug', 'super_admin')->exists();
    });

    runTest("Assign permissions to role", function() {
        $role = Role::create(['name' => 'Test Role 1', 'slug' => 'test_role_1']);
        $permissions = Permission::whereIn('slug', ['invoices.view', 'invoices.create'])->pluck('slug')->toArray();

        $role->assignPermissions($permissions);

        return $role->hasPermission('invoices.view') && $role->hasPermission('invoices.create');
    });

    runTest("Remove permissions from role", function() {
        $role = Role::create(['name' => 'Test Role 2', 'slug' => 'test_role_2']);
        $role->assignPermissions(['invoices.view', 'invoices.create', 'invoices.delete']);
        $role->removePermissions(['invoices.delete']);

        return $role->hasPermission('invoices.view') && !$role->hasPermission('invoices.delete');
    });

    runTest("Sync permissions for role", function() {
        $role = Role::create(['name' => 'Test Role 3', 'slug' => 'test_role_3']);
        $role->assignPermissions(['invoices.view', 'invoices.create']);
        $role->syncPermissions(['users.view', 'users.create']);

        return !$role->hasPermission('invoices.view') && $role->hasPermission('users.view');
    });

    // ========================================
    // SECTION 3: PERMISSION MODEL FUNCTIONALITY
    // ========================================
    echo "\n🔐 TESTING PERMISSION MODEL\n";
    echo "----------------------------------------\n";

    runTest("Create new permission", function() {
        $permission = Permission::create([
            'name' => 'Test Permission',
            'slug' => 'test.permission',
            'description' => 'Test permission',
            'module' => 'Testing'
        ]);

        return $permission->exists && $permission->slug == 'test.permission';
    });

    runTest("System permissions cannot be deleted", function() {
        $systemPerm = Permission::where('is_system', true)->first();
        $result = $systemPerm->delete();

        return $result === false && Permission::where('id', $systemPerm->id)->exists();
    });

    runTest("Permissions grouped by module", function() {
        $grouped = Permission::getGroupedByModule();

        return is_array($grouped) &&
               isset($grouped['Invoice Management']) &&
               isset($grouped['User Management']);
    });

    // ========================================
    // SECTION 4: USER TRAIT (HasRoles)
    // ========================================
    echo "\n👤 TESTING USER TRAIT (HasRoles)\n";
    echo "----------------------------------------\n";

    runTest("Assign role to user", function() {
        $user = User::factory()->create(['email' => 'test1@example.com']);
        $vendorRole = Role::where('slug', 'vendor')->first();

        $user->assignRole($vendorRole);

        return $user->hasRole('vendor') && !$user->hasRole('accountant');
    });

    runTest("Check user permissions", function() {
        $user = User::factory()->create(['email' => 'test2@example.com']);
        $vendorRole = Role::where('slug', 'vendor')->first();

        $user->assignRole($vendorRole);

        return $user->hasPermission('invoices.view') &&
               $user->hasPermission('invoices.create') &&
               !$user->hasPermission('invoices.approve');
    });

    runTest("Super admin bypasses all checks", function() {
        $user = User::factory()->create(['email' => 'superadmin@example.com']);
        $superAdminRole = Role::where('slug', 'super_admin')->first();

        $user->assignRole($superAdminRole);

        return $user->hasPermission('any.random.permission') &&
               $user->hasPermission('non.existent.permission') &&
               $user->isSuperAdmin();
    });

    runTest("Check multiple permissions", function() {
        $user = User::factory()->create(['email' => 'test3@example.com']);
        $vendorRole = Role::where('slug', 'vendor')->first();

        $user->assignRole($vendorRole);

        $hasAll = $user->hasAllPermissions(['invoices.view', 'invoices.create']);
        $notHasAll = $user->hasAllPermissions(['invoices.view', 'invoices.approve']);
        $hasAny = $user->hasAnyPermission(['invoices.approve', 'invoices.view']);
        $notHasAny = $user->hasAnyPermission(['invoices.approve', 'invoices.reject']);

        return $hasAll && !$notHasAll && $hasAny && !$notHasAny;
    });

    runTest("Permission caching works", function() {
        $user = User::factory()->create(['email' => 'test4@example.com']);
        $vendorRole = Role::where('slug', 'vendor')->first();

        $user->assignRole($vendorRole);

        // First call caches permissions
        $perms1 = $user->getCachedPermissions();

        // Clear and get again
        $user->clearPermissionCache();
        $perms2 = $user->getCachedPermissions();

        return $perms1->count() == $perms2->count();
    });

    // ========================================
    // SECTION 5: INVOICE POLICY
    // ========================================
    echo "\n📄 TESTING INVOICE POLICY\n";
    echo "----------------------------------------\n";

    runTest("Vendor can only view own invoices", function() {
        $vendor1 = User::factory()->create(['email' => 'vendor1@example.com']);
        $vendor2 = User::factory()->create(['email' => 'vendor2@example.com']);
        $vendorRole = Role::where('slug', 'vendor')->first();

        $vendor1->assignRole($vendorRole);
        $vendor2->assignRole($vendorRole);

        $invoice1 = Invoice::factory()->create(['vendor_id' => $vendor1->id]);
        $invoice2 = Invoice::factory()->create(['vendor_id' => $vendor2->id]);

        $can1View1 = $vendor1->can('view', $invoice1);
        $can1View2 = $vendor1->can('view', $invoice2);

        return $can1View1 && !$can1View2;
    });

    runTest("Accountant can view all invoices", function() {
        $accountant = User::factory()->create(['email' => 'accountant1@example.com']);
        $vendor = User::factory()->create(['email' => 'vendor3@example.com']);

        $accountantRole = Role::where('slug', 'accountant')->first();
        $vendorRole = Role::where('slug', 'vendor')->first();

        $accountant->assignRole($accountantRole);
        $vendor->assignRole($vendorRole);

        $vendorInvoice = Invoice::factory()->create(['vendor_id' => $vendor->id]);
        $otherInvoice = Invoice::factory()->create(['vendor_id' => 999]);

        return $accountant->can('view', $vendorInvoice) &&
               $accountant->can('view', $otherInvoice);
    });

    runTest("Only accountant can approve invoices", function() {
        $accountant = User::factory()->create(['email' => 'accountant2@example.com']);
        $vendor = User::factory()->create(['email' => 'vendor4@example.com']);

        $accountantRole = Role::where('slug', 'accountant')->first();
        $vendorRole = Role::where('slug', 'vendor')->first();

        $accountant->assignRole($accountantRole);
        $vendor->assignRole($vendorRole);

        $invoice = Invoice::factory()->create([
            'vendor_id' => $vendor->id,
            'status' => 'pending'
        ]);

        return $accountant->can('approve', $invoice) &&
               !$vendor->can('approve', $invoice);
    });

    runTest("Vendor can only update own pending invoices", function() {
        $vendor = User::factory()->create(['email' => 'vendor5@example.com']);
        $vendorRole = Role::where('slug', 'vendor')->first();
        $vendor->assignRole($vendorRole);

        $pendingInvoice = Invoice::factory()->create([
            'vendor_id' => $vendor->id,
            'status' => 'pending'
        ]);

        $approvedInvoice = Invoice::factory()->create([
            'vendor_id' => $vendor->id,
            'status' => 'approved'
        ]);

        $otherVendorInvoice = Invoice::factory()->create([
            'vendor_id' => 999,
            'status' => 'pending'
        ]);

        return $vendor->can('update', $pendingInvoice) &&
               !$vendor->can('update', $approvedInvoice) &&
               !$vendor->can('update', $otherVendorInvoice);
    });

    // ========================================
    // SECTION 6: GATES FUNCTIONALITY
    // ========================================
    echo "\n🚪 TESTING GATES\n";
    echo "----------------------------------------\n";

    runTest("Gates registered dynamically", function() {
        $user = User::factory()->create(['email' => 'gatetest@example.com']);
        $vendorRole = Role::where('slug', 'vendor')->first();
        $user->assignRole($vendorRole);

        return Gate::allows('invoices.view', [$user]) &&
               Gate::allows('invoices.create', [$user]) &&
               !Gate::allows('invoices.approve', [$user]);
    });

    runTest("Super admin gate bypasses checks", function() {
        $superAdmin = User::factory()->create(['email' => 'supergate@example.com']);
        $superAdminRole = Role::where('slug', 'super_admin')->first();
        $superAdmin->assignRole($superAdminRole);

        return Gate::allows('any.permission', [$superAdmin]) &&
               Gate::allows('non.existent', [$superAdmin]);
    });

    // ========================================
    // SECTION 7: RELATIONSHIPS
    // ========================================
    echo "\n🔗 TESTING RELATIONSHIPS\n";
    echo "----------------------------------------\n";

    runTest("Role has many users", function() {
        $role = Role::where('slug', 'vendor')->first();
        $initialCount = $role->users()->count();

        $user1 = User::factory()->create(['email' => 'rel1@example.com']);
        $user2 = User::factory()->create(['email' => 'rel2@example.com']);

        $user1->assignRole($role);
        $user2->assignRole($role);

        return $role->users()->count() == $initialCount + 2;
    });

    runTest("Role has many permissions", function() {
        $role = Role::where('slug', 'vendor')->first();

        return $role->permissions()->count() > 0 &&
               $role->permissions()->where('slug', 'invoices.view')->exists();
    });

    // ========================================
    // SECTION 8: SOFT DELETES
    // ========================================
    echo "\n🗑️ TESTING SOFT DELETES\n";
    echo "----------------------------------------\n";

    runTest("Roles use soft deletes", function() {
        $role = Role::create(['name' => 'Soft Delete Test', 'slug' => 'soft_delete_test']);
        $roleId = $role->id;

        $role->delete();

        // Should be soft deleted
        $softDeleted = Role::onlyTrashed()->find($roleId);
        if (!$softDeleted) return "Role was not soft deleted";

        // Restore
        $softDeleted->restore();

        return Role::find($roleId) !== null;
    });

    runTest("Permissions use soft deletes", function() {
        $permission = Permission::create([
            'name' => 'Soft Delete Perm',
            'slug' => 'soft.delete.perm'
        ]);
        $permId = $permission->id;

        $permission->delete();

        // Should be soft deleted
        $softDeleted = Permission::onlyTrashed()->find($permId);
        if (!$softDeleted) return "Permission was not soft deleted";

        // Restore
        $softDeleted->restore();

        return Permission::find($permId) !== null;
    });

    // ========================================
    // SECTION 9: CASCADE DELETES
    // ========================================
    echo "\n🔀 TESTING CASCADE DELETES\n";
    echo "----------------------------------------\n";

    runTest("Deleting role removes pivot records", function() {
        $role = Role::create(['name' => 'Cascade Test', 'slug' => 'cascade_test']);
        $permission = Permission::where('slug', 'invoices.view')->first();

        $role->assignPermissions([$permission->slug]);

        // Check pivot exists
        $pivotExists = DB::table('role_permission')
            ->where('role_id', $role->id)
            ->where('permission_id', $permission->id)
            ->exists();

        if (!$pivotExists) return "Pivot record was not created";

        // Force delete role
        $role->forceDelete();

        // Pivot should be gone
        $pivotGone = !DB::table('role_permission')
            ->where('role_id', $role->id)
            ->where('permission_id', $permission->id)
            ->exists();

        return $pivotGone;
    });

    // ========================================
    // SECTION 10: UNIQUE CONSTRAINTS
    // ========================================
    echo "\n🔒 TESTING UNIQUE CONSTRAINTS\n";
    echo "----------------------------------------\n";

    runTest("Role slug must be unique", function() {
        Role::create(['name' => 'Unique Test 1', 'slug' => 'unique_test']);

        try {
            Role::create(['name' => 'Unique Test 2', 'slug' => 'unique_test']);
            return "Duplicate slug was allowed";
        } catch (\Exception $e) {
            return true; // Expected exception
        }
    });

    runTest("Permission slug must be unique", function() {
        Permission::create(['name' => 'Unique Perm 1', 'slug' => 'unique.perm']);

        try {
            Permission::create(['name' => 'Unique Perm 2', 'slug' => 'unique.perm']);
            return "Duplicate slug was allowed";
        } catch (\Exception $e) {
            return true; // Expected exception
        }
    });

    // ========================================
    // SECTION 11: PERFORMANCE TESTS
    // ========================================
    echo "\n⚡ TESTING PERFORMANCE\n";
    echo "----------------------------------------\n";

    runTest("Eager loading prevents N+1 queries", function() {
        // Create test roles
        for ($i = 1; $i <= 5; $i++) {
            $role = Role::create(['name' => "Perf Test $i", 'slug' => "perf_test_$i"]);
            $role->assignPermissions(['invoices.view', 'invoices.create']);
        }

        // Enable query log
        DB::enableQueryLog();

        // Eager load permissions
        $roles = Role::with('permissions')->where('slug', 'like', 'perf_test_%')->get();
        $queryCount = count(DB::getQueryLog());

        // Access permissions on each role
        foreach ($roles as $role) {
            $role->permissions->count();
        }

        $finalQueryCount = count(DB::getQueryLog());
        DB::disableQueryLog();

        // Should not have additional queries
        return $queryCount == $finalQueryCount;
    });

    // ========================================
    // SECTION 12: INITIAL SYSTEM VALIDATION
    // ========================================
    echo "\n✅ TESTING INITIAL SYSTEM STATE\n";
    echo "----------------------------------------\n";

    runTest("Super Admin has all permissions", function() {
        $superAdmin = Role::where('slug', 'super_admin')->first();
        $allPermissions = Permission::count();

        return $superAdmin->permissions()->count() == $allPermissions;
    });

    runTest("Vendor has correct permissions", function() {
        $vendor = Role::where('slug', 'vendor')->first();

        return $vendor->hasPermission('invoices.view') &&
               $vendor->hasPermission('invoices.create') &&
               !$vendor->hasPermission('invoices.approve') &&
               !$vendor->hasPermission('invoices.reject');
    });

    runTest("Accountant has correct permissions", function() {
        $accountant = Role::where('slug', 'accountant')->first();

        return $accountant->hasPermission('invoices.approve') &&
               $accountant->hasPermission('invoices.reject') &&
               $accountant->hasPermission('payments.process') &&
               !$accountant->hasPermission('roles.create');
    });

    // ========================================
    // FINAL REPORT
    // ========================================
    echo "\n===========================================\n";
    echo "TEST RESULTS SUMMARY\n";
    echo "===========================================\n";
    echo "Total Tests: $totalTests\n";
    echo "✅ Passed: $passedTests\n";
    echo "❌ Failed: $failedTests\n";
    echo "Success Rate: " . round(($passedTests / $totalTests) * 100, 2) . "%\n";
    echo "===========================================\n";

    if ($failedTests > 0) {
        echo "\n❌ FAILED TESTS:\n";
        foreach ($testResults as $result) {
            if ($result[0] !== "✅ PASS") {
                echo "  - {$result[1]}: {$result[2]}\n";
            }
        }
    }

    echo "\n";

} finally {
    // Rollback transaction to keep database clean
    DB::rollBack();
    echo "\n✅ Database rolled back - no permanent changes made.\n";
}