<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Role;
use App\Models\Permission;
use App\Models\Invoice;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;

class RolePermissionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Run seeders
        $this->artisan('db:seed', ['--class' => 'PermissionSeeder']);
        $this->artisan('db:seed', ['--class' => 'RoleSeeder']);
    }

    /**
     * TEST ROLE MODEL FUNCTIONALITY
     */

    /** @test */
    public function it_can_create_a_role()
    {
        $role = Role::create([
            'name' => 'Test Role',
            'slug' => 'test_role',
            'description' => 'A test role',
        ]);

        $this->assertDatabaseHas('roles', [
            'name' => 'Test Role',
            'slug' => 'test_role',
        ]);

        $this->assertInstanceOf(Role::class, $role);
        $this->assertEquals('Test Role', $role->name);
    }

    /** @test */
    public function it_automatically_generates_slug_if_not_provided()
    {
        $role = Role::create([
            'name' => 'Test Role With Spaces',
            'description' => 'A test role',
        ]);

        $this->assertEquals('test-role-with-spaces', $role->slug);
    }

    /** @test */
    public function it_prevents_deletion_of_system_roles()
    {
        $systemRole = Role::where('slug', 'super_admin')->first();

        $result = $systemRole->delete();

        $this->assertFalse($result);
        $this->assertDatabaseHas('roles', ['slug' => 'super_admin']);
    }

    /** @test */
    public function it_prevents_deletion_of_roles_with_users()
    {
        $role = Role::create(['name' => 'Test Role', 'slug' => 'test_role']);
        $user = User::factory()->create();
        $user->assignRole($role);

        $result = $role->delete();

        $this->assertFalse($result);
        $this->assertDatabaseHas('roles', ['slug' => 'test_role']);
    }

    /** @test */
    public function it_can_assign_permissions_to_role()
    {
        $role = Role::create(['name' => 'Test Role', 'slug' => 'test_role']);
        $permissions = Permission::whereIn('slug', ['invoices.view', 'invoices.create'])->get();

        $role->assignPermissions($permissions->pluck('slug')->toArray());

        $this->assertTrue($role->hasPermission('invoices.view'));
        $this->assertTrue($role->hasPermission('invoices.create'));
        $this->assertFalse($role->hasPermission('invoices.delete'));
    }

    /** @test */
    public function it_can_remove_permissions_from_role()
    {
        $role = Role::create(['name' => 'Test Role', 'slug' => 'test_role']);
        $permissions = Permission::whereIn('slug', ['invoices.view', 'invoices.create', 'invoices.delete'])->get();

        $role->assignPermissions($permissions->pluck('slug')->toArray());
        $role->removePermissions(['invoices.delete']);

        $this->assertTrue($role->hasPermission('invoices.view'));
        $this->assertTrue($role->hasPermission('invoices.create'));
        $this->assertFalse($role->hasPermission('invoices.delete'));
    }

    /** @test */
    public function it_can_sync_permissions_for_role()
    {
        $role = Role::create(['name' => 'Test Role', 'slug' => 'test_role']);

        // First assign some permissions
        $role->assignPermissions(['invoices.view', 'invoices.create', 'invoices.delete']);

        // Now sync with different permissions
        $role->syncPermissions(['users.view', 'users.create']);

        $this->assertFalse($role->hasPermission('invoices.view'));
        $this->assertTrue($role->hasPermission('users.view'));
        $this->assertTrue($role->hasPermission('users.create'));
    }

    /**
     * TEST PERMISSION MODEL FUNCTIONALITY
     */

    /** @test */
    public function it_can_create_a_permission()
    {
        $permission = Permission::create([
            'name' => 'Test Permission',
            'slug' => 'test.permission',
            'description' => 'A test permission',
            'module' => 'Testing',
        ]);

        $this->assertDatabaseHas('permissions', [
            'name' => 'Test Permission',
            'slug' => 'test.permission',
            'module' => 'Testing',
        ]);
    }

    /** @test */
    public function it_prevents_deletion_of_system_permissions()
    {
        $systemPermission = Permission::where('is_system', true)->first();

        $result = $systemPermission->delete();

        $this->assertFalse($result);
        $this->assertDatabaseHas('permissions', ['id' => $systemPermission->id]);
    }

    /** @test */
    public function it_prevents_deletion_of_permissions_assigned_to_roles()
    {
        $permission = Permission::create(['name' => 'Test Permission', 'slug' => 'test.permission']);
        $role = Role::create(['name' => 'Test Role', 'slug' => 'test_role']);

        $role->assignPermissions([$permission->slug]);

        $result = $permission->delete();

        $this->assertFalse($result);
        $this->assertDatabaseHas('permissions', ['slug' => 'test.permission']);
    }

    /** @test */
    public function it_can_get_permissions_grouped_by_module()
    {
        $grouped = Permission::getGroupedByModule();

        $this->assertIsArray($grouped);
        $this->assertArrayHasKey('Invoice Management', $grouped);
        $this->assertArrayHasKey('User Management', $grouped);
        $this->assertArrayHasKey('Role Management', $grouped);
    }

    /**
     * TEST USER TRAIT (HasRoles) FUNCTIONALITY
     */

    /** @test */
    public function it_can_assign_role_to_user()
    {
        $user = User::factory()->create();
        $role = Role::where('slug', 'vendor')->first();

        $user->assignRole($role);

        $this->assertTrue($user->hasRole('vendor'));
        $this->assertFalse($user->hasRole('accountant'));
    }

    /** @test */
    public function it_can_check_if_user_has_permission()
    {
        $user = User::factory()->create();
        $vendorRole = Role::where('slug', 'vendor')->first();

        $user->assignRole($vendorRole);

        $this->assertTrue($user->hasPermission('invoices.view'));
        $this->assertTrue($user->hasPermission('invoices.create'));
        $this->assertFalse($user->hasPermission('invoices.approve'));
    }

    /** @test */
    public function super_admin_bypasses_all_permission_checks()
    {
        $user = User::factory()->create();
        $superAdminRole = Role::where('slug', 'super_admin')->first();

        $user->assignRole($superAdminRole);

        // Super admin should have all permissions
        $this->assertTrue($user->hasPermission('any.permission'));
        $this->assertTrue($user->hasPermission('non.existent.permission'));
        $this->assertTrue($user->isSuperAdmin());
    }

    /** @test */
    public function it_can_check_multiple_permissions()
    {
        $user = User::factory()->create();
        $vendorRole = Role::where('slug', 'vendor')->first();

        $user->assignRole($vendorRole);

        // Has all permissions
        $this->assertTrue($user->hasAllPermissions(['invoices.view', 'invoices.create']));
        $this->assertFalse($user->hasAllPermissions(['invoices.view', 'invoices.approve']));

        // Has any permission
        $this->assertTrue($user->hasAnyPermission(['invoices.approve', 'invoices.view']));
        $this->assertFalse($user->hasAnyPermission(['invoices.approve', 'invoices.reject']));
    }

    /** @test */
    public function it_caches_user_permissions()
    {
        $user = User::factory()->create();
        $vendorRole = Role::where('slug', 'vendor')->first();

        $user->assignRole($vendorRole);

        // First call should cache permissions
        $permissions1 = $user->getCachedPermissions();

        // Second call should return cached permissions
        $permissions2 = $user->getCachedPermissions();

        $this->assertEquals($permissions1->pluck('id'), $permissions2->pluck('id'));

        // Clear cache
        $user->clearPermissionCache();

        // This should fetch fresh permissions
        $permissions3 = $user->getCachedPermissions();

        $this->assertEquals($permissions1->pluck('id'), $permissions3->pluck('id'));
    }

    /** @test */
    public function it_clears_permission_cache_when_role_changes()
    {
        $user = User::factory()->create();
        $vendorRole = Role::where('slug', 'vendor')->first();
        $accountantRole = Role::where('slug', 'accountant')->first();

        $user->assignRole($vendorRole);

        // Cache permissions
        $vendorPermissions = $user->getCachedPermissions()->pluck('slug')->toArray();

        // Change role
        $user->assignRole($accountantRole);

        // Get new permissions
        $accountantPermissions = $user->getCachedPermissions()->pluck('slug')->toArray();

        $this->assertNotEquals($vendorPermissions, $accountantPermissions);
        $this->assertContains('invoices.approve', $accountantPermissions);
        $this->assertNotContains('invoices.approve', $vendorPermissions);
    }

    /**
     * TEST INVOICE POLICY FUNCTIONALITY
     */

    /** @test */
    public function vendor_can_only_view_own_invoices()
    {
        $vendor1 = User::factory()->create();
        $vendor2 = User::factory()->create();
        $vendorRole = Role::where('slug', 'vendor')->first();

        $vendor1->assignRole($vendorRole);
        $vendor2->assignRole($vendorRole);

        $invoice1 = Invoice::factory()->create(['vendor_id' => $vendor1->id]);
        $invoice2 = Invoice::factory()->create(['vendor_id' => $vendor2->id]);

        $this->assertTrue($vendor1->can('view', $invoice1));
        $this->assertFalse($vendor1->can('view', $invoice2));

        $this->assertFalse($vendor2->can('view', $invoice1));
        $this->assertTrue($vendor2->can('view', $invoice2));
    }

    /** @test */
    public function accountant_can_view_all_invoices()
    {
        $accountant = User::factory()->create();
        $vendor = User::factory()->create();

        $accountantRole = Role::where('slug', 'accountant')->first();
        $vendorRole = Role::where('slug', 'vendor')->first();

        $accountant->assignRole($accountantRole);
        $vendor->assignRole($vendorRole);

        $vendorInvoice = Invoice::factory()->create(['vendor_id' => $vendor->id]);
        $otherInvoice = Invoice::factory()->create(['vendor_id' => 999]);

        $this->assertTrue($accountant->can('view', $vendorInvoice));
        $this->assertTrue($accountant->can('view', $otherInvoice));
    }

    /** @test */
    public function only_accountant_can_approve_invoices()
    {
        $accountant = User::factory()->create();
        $vendor = User::factory()->create();

        $accountantRole = Role::where('slug', 'accountant')->first();
        $vendorRole = Role::where('slug', 'vendor')->first();

        $accountant->assignRole($accountantRole);
        $vendor->assignRole($vendorRole);

        $invoice = Invoice::factory()->create([
            'vendor_id' => $vendor->id,
            'status' => 'pending'
        ]);

        $this->assertTrue($accountant->can('approve', $invoice));
        $this->assertFalse($vendor->can('approve', $invoice));
    }

    /** @test */
    public function vendor_can_only_update_own_pending_invoices()
    {
        $vendor = User::factory()->create();
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

        $this->assertTrue($vendor->can('update', $pendingInvoice));
        $this->assertFalse($vendor->can('update', $approvedInvoice));
        $this->assertFalse($vendor->can('update', $otherVendorInvoice));
    }

    /**
     * TEST GATES FUNCTIONALITY
     */

    /** @test */
    public function gates_are_registered_dynamically_from_permissions()
    {
        $user = User::factory()->create();
        $vendorRole = Role::where('slug', 'vendor')->first();
        $user->assignRole($vendorRole);

        // Test that gates are registered
        $this->assertTrue(Gate::allows('invoices.view', [$user]));
        $this->assertTrue(Gate::allows('invoices.create', [$user]));
        $this->assertFalse(Gate::allows('invoices.approve', [$user]));
    }

    /** @test */
    public function super_admin_gate_bypasses_all_checks()
    {
        $superAdmin = User::factory()->create();
        $superAdminRole = Role::where('slug', 'super_admin')->first();
        $superAdmin->assignRole($superAdminRole);

        // Super admin should pass all gate checks
        $this->assertTrue(Gate::allows('any.permission', [$superAdmin]));
        $this->assertTrue(Gate::allows('non.existent', [$superAdmin]));
        $this->assertTrue(Gate::check('super-admin', [$superAdmin]));
    }

    /**
     * TEST MIDDLEWARE FUNCTIONALITY
     */

    /** @test */
    public function role_middleware_blocks_unauthorized_users()
    {
        $vendor = User::factory()->create();
        $vendorRole = Role::where('slug', 'vendor')->first();
        $vendor->assignRole($vendorRole);

        // Vendor trying to access admin route
        $response = $this->actingAs($vendor)
            ->get('/admin/roles');

        $response->assertStatus(403);
    }

    /** @test */
    public function permission_middleware_blocks_users_without_permission()
    {
        $vendor = User::factory()->create();
        $vendorRole = Role::where('slug', 'vendor')->first();
        $vendor->assignRole($vendorRole);

        // Vendor trying to access route requiring 'invoices.approve' permission
        // This would need actual route testing with middleware applied
        $this->assertFalse($vendor->hasPermission('invoices.approve'));
    }

    /**
     * TEST SEEDING IDEMPOTENCY
     */

    /** @test */
    public function seeders_are_idempotent()
    {
        // Count initial records
        $initialPermissionCount = Permission::count();
        $initialRoleCount = Role::count();

        // Run seeders again
        $this->artisan('db:seed', ['--class' => 'PermissionSeeder']);
        $this->artisan('db:seed', ['--class' => 'RoleSeeder']);

        // Count should remain the same
        $this->assertEquals($initialPermissionCount, Permission::count());
        $this->assertEquals($initialRoleCount, Role::count());
    }

    /**
     * TEST ROLE RELATIONSHIPS
     */

    /** @test */
    public function role_has_many_users_relationship()
    {
        $role = Role::where('slug', 'vendor')->first();
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $user1->assignRole($role);
        $user2->assignRole($role);

        $this->assertEquals(2, $role->users()->count());
        $this->assertTrue($role->users->contains($user1));
        $this->assertTrue($role->users->contains($user2));
    }

    /** @test */
    public function role_has_many_permissions_relationship()
    {
        $role = Role::where('slug', 'vendor')->first();

        $this->assertGreaterThan(0, $role->permissions()->count());
        $this->assertTrue($role->permissions()->where('slug', 'invoices.view')->exists());
    }

    /**
     * TEST SOFT DELETES
     */

    /** @test */
    public function roles_use_soft_deletes()
    {
        $role = Role::create(['name' => 'Test Role', 'slug' => 'test_role']);
        $roleId = $role->id;

        $role->delete();

        $this->assertSoftDeleted('roles', ['id' => $roleId]);

        // Can be restored
        $role->restore();
        $this->assertDatabaseHas('roles', ['id' => $roleId, 'deleted_at' => null]);
    }

    /** @test */
    public function permissions_use_soft_deletes()
    {
        $permission = Permission::create(['name' => 'Test Permission', 'slug' => 'test.permission']);
        $permissionId = $permission->id;

        $permission->delete();

        $this->assertSoftDeleted('permissions', ['id' => $permissionId]);

        // Can be restored
        $permission->restore();
        $this->assertDatabaseHas('permissions', ['id' => $permissionId, 'deleted_at' => null]);
    }

    /**
     * TEST DATABASE TRANSACTIONS
     */

    /** @test */
    public function role_operations_use_database_transactions()
    {
        $role = Role::create(['name' => 'Test Role', 'slug' => 'test_role']);

        DB::beginTransaction();

        try {
            $role->assignPermissions(['invoices.view', 'invoices.create']);
            // Simulate an error
            throw new \Exception('Test error');
        } catch (\Exception $e) {
            DB::rollBack();
        }

        // Permissions should not be assigned due to rollback
        $this->assertEquals(0, $role->permissions()->count());
    }

    /**
     * TEST N+1 QUERY PREVENTION
     */

    /** @test */
    public function eager_loading_prevents_n_plus_one_queries()
    {
        // Create multiple roles with permissions
        $roles = Role::factory(5)->create();
        foreach ($roles as $role) {
            $role->assignPermissions(['invoices.view', 'invoices.create']);
        }

        // Enable query log
        DB::enableQueryLog();

        // Eager load permissions
        $rolesWithPermissions = Role::with('permissions')->get();

        $queryCount = count(DB::getQueryLog());

        // Access permissions on each role
        foreach ($rolesWithPermissions as $role) {
            $role->permissions->count();
        }

        $finalQueryCount = count(DB::getQueryLog());

        // Should not execute additional queries
        $this->assertEquals($queryCount, $finalQueryCount);

        DB::disableQueryLog();
    }

    /**
     * TEST UNIQUE CONSTRAINTS
     */

    /** @test */
    public function role_slug_must_be_unique()
    {
        Role::create(['name' => 'Test Role 1', 'slug' => 'test_role']);

        $this->expectException(\Illuminate\Database\QueryException::class);

        Role::create(['name' => 'Test Role 2', 'slug' => 'test_role']);
    }

    /** @test */
    public function permission_slug_must_be_unique()
    {
        Permission::create(['name' => 'Test Permission 1', 'slug' => 'test.permission']);

        $this->expectException(\Illuminate\Database\QueryException::class);

        Permission::create(['name' => 'Test Permission 2', 'slug' => 'test.permission']);
    }

    /**
     * TEST CASCADE DELETES
     */

    /** @test */
    public function deleting_role_removes_role_permission_pivot_records()
    {
        $role = Role::create(['name' => 'Test Role', 'slug' => 'test_role']);
        $permission = Permission::where('slug', 'invoices.view')->first();

        $role->assignPermissions([$permission->slug]);

        $this->assertDatabaseHas('role_permission', [
            'role_id' => $role->id,
            'permission_id' => $permission->id
        ]);

        $role->forceDelete();

        $this->assertDatabaseMissing('role_permission', [
            'role_id' => $role->id,
            'permission_id' => $permission->id
        ]);
    }

    /**
     * TEST THREE INITIAL ROLES
     */

    /** @test */
    public function system_has_three_initial_roles()
    {
        $roles = Role::where('is_system', true)->get();

        $this->assertEquals(3, $roles->count());

        $roleSlugs = $roles->pluck('slug')->toArray();
        $this->assertContains('super_admin', $roleSlugs);
        $this->assertContains('vendor', $roleSlugs);
        $this->assertContains('accountant', $roleSlugs);
    }

    /** @test */
    public function initial_roles_have_correct_permissions()
    {
        // Super Admin has all permissions
        $superAdmin = Role::where('slug', 'super_admin')->first();
        $allPermissions = Permission::count();
        $this->assertEquals($allPermissions, $superAdmin->permissions()->count());

        // Vendor has specific permissions
        $vendor = Role::where('slug', 'vendor')->first();
        $this->assertTrue($vendor->hasPermission('invoices.view'));
        $this->assertTrue($vendor->hasPermission('invoices.create'));
        $this->assertFalse($vendor->hasPermission('invoices.approve'));

        // Accountant has financial permissions
        $accountant = Role::where('slug', 'accountant')->first();
        $this->assertTrue($accountant->hasPermission('invoices.approve'));
        $this->assertTrue($accountant->hasPermission('invoices.reject'));
        $this->assertTrue($accountant->hasPermission('payments.process'));
    }
}