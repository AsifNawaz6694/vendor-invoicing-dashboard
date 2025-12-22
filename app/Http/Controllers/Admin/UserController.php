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

class UserController extends Controller
{
    /**
     * Display a listing of users.
     */
    public function index(Request $request): Response
    {
        $query = User::query()
            ->with('creator:id,name')
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
        return Inertia::render('admin/users/create');
    }

    /**
     * Store a newly created user.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
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
        return Inertia::render('admin/users/edit', [
            'user' => $user->load('creator:id,name'),
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
}
