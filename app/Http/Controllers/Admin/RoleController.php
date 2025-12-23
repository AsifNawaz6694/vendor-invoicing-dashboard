<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\Permission;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RoleController extends Controller
{
    /**
     * Display a listing of roles.
     */
    public function index(Request $request)
    {
        $roles = Role::with('permissions')
            ->withCount(['users', 'permissions'])
            ->when($request->search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            })
            ->orderBy('is_system', 'desc')
            ->orderBy('name')
            ->paginate(10);

        // Always return Inertia response for web requests
        return Inertia::render('admin/roles/index', [
            'roles' => $roles
        ]);
    }

    /**
     * Show the form for creating a new role.
     */
    public function create()
    {
        $permissions = Permission::orderBy('module')->orderBy('name')->get();

        return Inertia::render('admin/roles/create', [
            'permissions' => $permissions
        ]);
    }

    /**
     * Store a newly created role in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:roles',
            'slug' => 'nullable|string|max:255|unique:roles',
            'description' => 'nullable|string|max:500',
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,id'
        ]);

        DB::beginTransaction();
        try {
            $role = Role::create($request->only(['name', 'slug', 'description']));

            if ($request->has('permissions')) {
                $role->permissions()->sync($request->permissions);
            }

            // Clear permission cache for all users with this role
            $this->clearRolePermissionCache($role);

            DB::commit();

            return redirect()->route('admin.roles.index')
                ->with('success', 'Role created successfully');
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to create role: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified role.
     */
    public function show(Request $request, Role $role)
    {
        $role->load(['permissions', 'users']);

        if ($request->ajax()) {
            return response()->json($role);
        }

        return view('admin.roles.show', compact('role'));
    }

    /**
     * Show the form for editing the specified role.
     */
    public function edit(Role $role)
    {
        $permissions = Permission::orderBy('module')->orderBy('name')->get();
        $role->load('permissions');

        return Inertia::render('admin/roles/edit', [
            'role' => $role,
            'permissions' => $permissions
        ]);
    }

    /**
     * Update the specified role in storage.
     */
    public function update(Request $request, Role $role)
    {
        // Prevent editing system roles
        if ($role->is_system) {
            return redirect()->route('admin.roles.index')
                ->with('error', 'System roles cannot be edited');
        }

        $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('roles')->ignore($role->id)],
            'description' => 'nullable|string|max:500',
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,id'
        ]);

        DB::beginTransaction();
        try {
            $role->update($request->only(['name', 'description']));

            if ($request->has('permissions')) {
                $role->permissions()->sync($request->permissions);
            }

            // Clear permission cache for all users with this role
            $this->clearRolePermissionCache($role);

            DB::commit();

            return redirect()->route('admin.roles.index')
                ->with('success', 'Role updated successfully');
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to update role: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified role from storage.
     */
    public function destroy(Request $request, Role $role)
    {
        // Prevent deletion of system roles
        if ($role->is_system) {
            return redirect()->route('admin.roles.index')
                ->with('error', 'System roles cannot be deleted');
        }

        // Check if role is in use
        if ($role->users()->exists()) {
            return redirect()->route('admin.roles.index')
                ->with('error', 'Cannot delete role that is assigned to users');
        }

        DB::beginTransaction();
        try {
            $role->delete();
            DB::commit();

            return redirect()->route('admin.roles.index')
                ->with('success', 'Role deleted successfully');
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->back()
                ->with('error', 'Failed to delete role: ' . $e->getMessage());
        }
    }

    /**
     * Assign permissions to a role.
     */
    public function assignPermissions(Request $request, Role $role)
    {
        // Prevent editing system roles
        if ($role->is_system) {
            return response()->json([
                'message' => 'System roles cannot be edited'
            ], 403);
        }

        $request->validate([
            'permissions' => 'required|array',
            'permissions.*' => 'exists:permissions,id'
        ]);

        DB::beginTransaction();
        try {
            $role->assignPermissions($request->permissions);

            // Clear permission cache for all users with this role
            $this->clearRolePermissionCache($role);

            DB::commit();

            return response()->json([
                'message' => 'Permissions assigned successfully',
                'role' => $role->load('permissions')
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to assign permissions',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove permissions from a role.
     */
    public function removePermissions(Request $request, Role $role)
    {
        // Prevent editing system roles
        if ($role->is_system) {
            return response()->json([
                'message' => 'System roles cannot be edited'
            ], 403);
        }

        $request->validate([
            'permissions' => 'required|array',
            'permissions.*' => 'exists:permissions,id'
        ]);

        DB::beginTransaction();
        try {
            $role->removePermissions($request->permissions);

            // Clear permission cache for all users with this role
            $this->clearRolePermissionCache($role);

            DB::commit();

            return response()->json([
                'message' => 'Permissions removed successfully',
                'role' => $role->load('permissions')
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to remove permissions',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Clear permission cache for all users with the given role.
     */
    protected function clearRolePermissionCache(Role $role)
    {
        $role->users()->each(function ($user) {
            $user->clearPermissionCache();
        });
    }

    /**
     * Export roles to CSV.
     */
    public function export(Request $request): StreamedResponse
    {
        $query = Role::with('permissions')
            ->withCount(['users', 'permissions']);

        // Apply filters
        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('description', 'like', "%{$request->search}%");
            });
        }

        if ($request->has('is_system')) {
            $query->where('is_system', $request->input('is_system') === 'yes');
        }

        $roles = $query->orderBy('is_system', 'desc')
            ->orderBy('name')
            ->get();

        $filename = 'roles_export_' . date('Y-m-d_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0'
        ];

        $callback = function () use ($roles) {
            $file = fopen('php://output', 'w');

            // Add BOM for Excel UTF-8 compatibility
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

            // Add headers
            fputcsv($file, [
                'ID',
                'Name',
                'Slug',
                'Description',
                'System Role',
                'User Count',
                'Permission Count',
                'Permissions',
                'Created At'
            ]);

            // Add data
            foreach ($roles as $role) {
                fputcsv($file, [
                    $role->id,
                    $role->name,
                    $role->slug,
                    $role->description ?? '',
                    $role->is_system ? 'Yes' : 'No',
                    $role->users_count,
                    $role->permissions_count,
                    $role->permissions->pluck('name')->join(', '),
                    $role->created_at->format('Y-m-d H:i:s')
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}