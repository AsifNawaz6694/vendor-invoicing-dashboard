<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PermissionController extends Controller
{
    /**
     * Display a listing of permissions.
     */
    public function index(Request $request)
    {
        $permissions = Permission::with('roles')
            ->withCount('roles')
            ->when($request->search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('module', 'like', "%{$search}%");
            })
            ->when($request->module, function ($query, $module) {
                $query->where('module', $module);
            })
            ->orderBy('module')
            ->orderBy('name')
            ->paginate(50);

        $modules = Permission::distinct()->pluck('module')->filter();

        // Always return Inertia response for web requests
        return Inertia::render('admin/permissions/index', [
            'permissions' => $permissions,
            'modules' => $modules
        ]);
    }

    /**
     * Show the form for creating a new permission.
     */
    public function create()
    {
        $modules = Permission::distinct()->pluck('module')->filter();

        return Inertia::render('admin/permissions/create', [
            'modules' => $modules
        ]);
    }

    /**
     * Store a newly created permission in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:permissions',
            'slug' => 'nullable|string|max:255|unique:permissions',
            'description' => 'nullable|string|max:500',
            'module' => 'required|string|max:100'
        ]);

        DB::beginTransaction();
        try {
            $data = $request->only(['name', 'slug', 'description', 'module']);

            // Generate slug from name if not provided
            if (empty($data['slug'])) {
                $data['slug'] = \Illuminate\Support\Str::slug($data['name'], '.');
            }

            $permission = Permission::create($data);

            // Clear all permission caches
            $this->clearAllPermissionCaches();

            DB::commit();

            return redirect()->route('admin.permissions.index')
                ->with('success', 'Permission created successfully');
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to create permission: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified permission.
     */
    public function show(Request $request, Permission $permission)
    {
        $permission->load('roles');

        if ($request->ajax()) {
            return response()->json($permission);
        }

        return view('admin.permissions.show', compact('permission'));
    }

    /**
     * Show the form for editing the specified permission.
     */
    public function edit(Permission $permission)
    {
        $modules = Permission::distinct()->pluck('module')->filter();

        return Inertia::render('admin/permissions/edit', [
            'permission' => $permission,
            'modules' => $modules
        ]);
    }

    /**
     * Update the specified permission in storage.
     */
    public function update(Request $request, Permission $permission)
    {
        // Prevent editing system permissions
        if ($permission->is_system) {
            return redirect()->route('admin.permissions.index')
                ->with('error', 'System permissions cannot be edited');
        }

        $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('permissions')->ignore($permission->id)],
            'description' => 'nullable|string|max:500',
            'module' => 'required|string|max:100'
        ]);

        DB::beginTransaction();
        try {
            $permission->update($request->only(['name', 'description', 'module']));

            // Clear all permission caches
            $this->clearAllPermissionCaches();

            DB::commit();

            return redirect()->route('admin.permissions.index')
                ->with('success', 'Permission updated successfully');
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to update permission: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified permission from storage.
     */
    public function destroy(Request $request, Permission $permission)
    {
        // Prevent deletion of system permissions
        if ($permission->is_system) {
            return redirect()->route('admin.permissions.index')
                ->with('error', 'System permissions cannot be deleted');
        }

        // Check if permission is in use
        if ($permission->roles()->exists()) {
            return redirect()->route('admin.permissions.index')
                ->with('error', 'Cannot delete permission that is assigned to roles');
        }

        DB::beginTransaction();
        try {
            $permission->delete();

            // Clear all permission caches
            $this->clearAllPermissionCaches();

            DB::commit();

            return redirect()->route('admin.permissions.index')
                ->with('success', 'Permission deleted successfully');
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->back()
                ->with('error', 'Failed to delete permission: ' . $e->getMessage());
        }
    }

    /**
     * Get permissions grouped by module.
     */
    public function getGroupedByModule(Request $request)
    {
        $permissions = Permission::getGroupedByModule();

        return response()->json($permissions);
    }

    /**
     * Bulk create permissions.
     */
    public function bulkStore(Request $request)
    {
        $request->validate([
            'permissions' => 'required|array',
            'permissions.*.name' => 'required|string|max:255|distinct',
            'permissions.*.slug' => 'nullable|string|max:255|distinct',
            'permissions.*.description' => 'nullable|string|max:500',
            'permissions.*.module' => 'nullable|string|max:100'
        ]);

        DB::beginTransaction();
        try {
            Permission::createBulk($request->permissions);

            // Clear all permission caches
            $this->clearAllPermissionCaches();

            DB::commit();

            return response()->json([
                'message' => 'Permissions created successfully'
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to create permissions',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Sync permissions for a module.
     */
    public function syncModule(Request $request)
    {
        $request->validate([
            'module' => 'required|string|max:100',
            'permissions' => 'required|array',
            'permissions.*.name' => 'required|string|max:255',
            'permissions.*.slug' => 'nullable|string|max:255',
            'permissions.*.description' => 'nullable|string|max:500'
        ]);

        DB::beginTransaction();
        try {
            // Delete non-system permissions for this module that are not in the new list
            $slugsToKeep = collect($request->permissions)->pluck('slug')->filter();
            Permission::where('module', $request->module)
                ->where('is_system', false)
                ->whereNotIn('slug', $slugsToKeep)
                ->delete();

            // Create or update permissions
            foreach ($request->permissions as $permissionData) {
                $permissionData['module'] = $request->module;
                Permission::updateOrCreate(
                    ['slug' => $permissionData['slug'] ?? \Illuminate\Support\Str::slug($permissionData['name'])],
                    $permissionData
                );
            }

            // Clear all permission caches
            $this->clearAllPermissionCaches();

            DB::commit();

            return response()->json([
                'message' => 'Module permissions synchronized successfully'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to sync module permissions',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Clear all permission caches.
     */
    protected function clearAllPermissionCaches()
    {
        // Clear user permission caches
        Cache::flush(); // Or use a more specific cache tag if configured
    }

    /**
     * Export permissions to CSV.
     */
    public function export(Request $request): StreamedResponse
    {
        $query = Permission::with('roles')
            ->withCount('roles');

        // Apply filters
        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('description', 'like', "%{$request->search}%")
                  ->orWhere('module', 'like', "%{$request->search}%");
            });
        }

        if ($request->module) {
            $query->where('module', $request->module);
        }

        if ($request->has('is_system')) {
            $query->where('is_system', $request->input('is_system') === 'yes');
        }

        $permissions = $query->orderBy('module')
            ->orderBy('name')
            ->get();

        $filename = 'permissions_export_' . date('Y-m-d_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0'
        ];

        $callback = function () use ($permissions) {
            $file = fopen('php://output', 'w');

            // Add BOM for Excel UTF-8 compatibility
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

            // Add headers
            fputcsv($file, [
                'ID',
                'Name',
                'Slug',
                'Module',
                'Description',
                'System Permission',
                'Role Count',
                'Assigned to Roles',
                'Created At'
            ]);

            // Add data
            foreach ($permissions as $permission) {
                fputcsv($file, [
                    $permission->id,
                    $permission->name,
                    $permission->slug,
                    $permission->module,
                    $permission->description ?? '',
                    $permission->is_system ? 'Yes' : 'No',
                    $permission->roles_count,
                    $permission->roles->pluck('name')->join(', '),
                    $permission->created_at->format('Y-m-d H:i:s')
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}