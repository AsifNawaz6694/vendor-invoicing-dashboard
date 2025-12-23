<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\WelcomeUserNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class UserController extends Controller
{
    /**
     * Display a listing of users.
     */
    public function index(Request $request): Response
    {
        $query = User::query()
            ->with(['creator:id,name', 'role:id,name'])
            ->withCount('createdUsers');

        // Search
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('is_active', $request->input('status') === 'active');
        }

        // Filter by admin
        if ($request->has('admin')) {
            $query->where('is_admin', $request->input('admin') === 'yes');
        }

        $users = $query->latest()->paginate(15)->withQueryString();

        return Inertia::render('admin/users/index', [
            'users' => $users,
            'filters' => $request->only(['search', 'status', 'admin']),
        ]);
    }

    /**
     * Show the form for creating a new user.
     */
    public function create(): Response
    {
        $roles = \App\Models\Role::orderBy('name')->get(['id', 'name', 'description']);

        return Inertia::render('admin/users/create', [
            'roles' => $roles,
        ]);
    }

    /**
     * Store a newly created user.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'role_id' => ['required', 'exists:roles,id'],
            'is_admin' => ['boolean'],
            'send_welcome_email' => ['boolean'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => strtolower($validated['email']),
            'password' => Hash::make(Str::random(32)), // Random password, will be set via welcome email
            'is_admin' => $validated['is_admin'] ?? false,
            'is_active' => true,
            'created_by' => $request->user()->id,
        ]);

        // Assign the selected role to the user
        $user->assignRole($validated['role_id']);

        if ($validated['send_welcome_email'] ?? true) {
            $user->notify(new WelcomeUserNotification());
        }

        return redirect()->route('admin.users.index')
            ->with('success', 'User created successfully.' . ($validated['send_welcome_email'] ?? true ? ' Welcome email sent.' : ''));
    }

    /**
     * Show the form for editing a user.
     */
    public function edit(User $user): Response
    {
        $roles = \App\Models\Role::orderBy('name')->get(['id', 'name', 'description']);
        $user->load(['creator:id,name', 'role:id,name']);

        return Inertia::render('admin/users/edit', [
            'user' => $user,
            'roles' => $roles,
        ]);
    }

    /**
     * Update the specified user.
     */
    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'role_id' => ['nullable', 'exists:roles,id'],
            'is_admin' => ['boolean'],
        ]);

        // Prevent removing admin from self
        if ($user->id === $request->user()->id && !($validated['is_admin'] ?? true)) {
            return back()->with('error', 'You cannot remove your own admin privileges.');
        }

        $user->update([
            'name' => $validated['name'],
            'email' => strtolower($validated['email']),
            'is_admin' => $validated['is_admin'] ?? false,
        ]);

        // Update role if provided
        if (isset($validated['role_id'])) {
            $user->syncRoles([$validated['role_id']]);
        }

        return redirect()->route('admin.users.index')
            ->with('success', 'User updated successfully.');
    }

    /**
     * Remove the specified user.
     */
    public function destroy(Request $request, User $user): RedirectResponse
    {
        // Prevent self-deletion
        if ($user->id === $request->user()->id) {
            return back()->with('error', 'You cannot delete your own account.');
        }

        $user->delete();

        return redirect()->route('admin.users.index')
            ->with('success', 'User deleted successfully.');
    }

    /**
     * Toggle user active status.
     */
    public function toggleStatus(Request $request, User $user): RedirectResponse
    {
        // Prevent deactivating self
        if ($user->id === $request->user()->id) {
            return back()->with('error', 'You cannot deactivate your own account.');
        }

        $user->update([
            'is_active' => !$user->is_active,
        ]);

        $status = $user->is_active ? 'activated' : 'deactivated';

        return back()->with('success', "User {$status} successfully.");
    }

    /**
     * Reset user password and send reset email.
     */
    public function resetPassword(Request $request, User $user): RedirectResponse
    {
        // Generate a new random password and send welcome email
        $user->update([
            'password' => Hash::make(Str::random(32)),
        ]);

        $user->notify(new WelcomeUserNotification());

        return back()->with('success', 'Password reset email sent to user.');
    }

    /**
     * Resend welcome email to user.
     */
    public function resendWelcome(User $user): RedirectResponse
    {
        $user->notify(new WelcomeUserNotification());

        return back()->with('success', 'Welcome email sent successfully.');
    }

    /**
     * Export users to CSV.
     */
    public function export(Request $request): StreamedResponse
    {
        $query = User::query()
            ->with(['creator:id,name', 'role:id,name']);

        // Apply same filters as index
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->has('status')) {
            $query->where('is_active', $request->input('status') === 'active');
        }

        if ($request->has('admin')) {
            $query->where('is_admin', $request->input('admin') === 'yes');
        }

        if ($request->has('role')) {
            $query->whereHas('role', function ($q) use ($request) {
                $q->where('id', $request->input('role'));
            });
        }

        if ($request->has('2fa')) {
            if ($request->input('2fa') === 'enabled') {
                $query->whereNotNull('two_factor_method');
            } else {
                $query->whereNull('two_factor_method');
            }
        }

        $users = $query->latest()->get();

        $filename = 'users_export_' . date('Y-m-d_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0'
        ];

        $callback = function () use ($users) {
            $file = fopen('php://output', 'w');

            // Add BOM for Excel UTF-8 compatibility
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

            // Add headers
            fputcsv($file, [
                'ID',
                'Name',
                'Email',
                'Role',
                'Status',
                'Admin',
                '2FA Method',
                'Email Verified',
                'Created By',
                'Created At'
            ]);

            // Add data
            foreach ($users as $user) {
                fputcsv($file, [
                    $user->id,
                    $user->name,
                    $user->email,
                    $user->role?->name ?? 'No Role',
                    $user->is_active ? 'Active' : 'Inactive',
                    $user->is_admin ? 'Yes' : 'No',
                    $user->two_factor_method ?? 'Not Enabled',
                    $user->email_verified_at ? 'Yes' : 'No',
                    $user->creator?->name ?? 'System',
                    $user->created_at->format('Y-m-d H:i:s')
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
