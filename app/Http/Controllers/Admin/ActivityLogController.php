<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ActivityLogController extends Controller
{
    /**
     * Display the activity logs listing.
     */
    public function index(Request $request): Response
    {
        $query = ActivityLog::query()
            ->with('user:id,name,email');

        // Search filter
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                    ->orWhere('user_name', 'like', "%{$search}%")
                    ->orWhere('user_email', 'like', "%{$search}%");
            });
        }

        // Log name filter
        if ($logName = $request->input('log_name')) {
            $query->where('log_name', $logName);
        }

        // User filter
        if ($userId = $request->input('user_id')) {
            $query->where('user_id', $userId);
        }

        // Date range filter
        if ($dateFrom = $request->input('date_from')) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }
        if ($dateTo = $request->input('date_to')) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        // Get paginated results
        $activities = $query->latest()->paginate(50)->withQueryString();

        // Get stats for the dashboard
        $stats = [
            'total_activities' => ActivityLog::where('created_at', '>=', now()->subDays(30))->count(),
            'log_types' => ActivityLog::distinct('log_name')->count('log_name'),
            'active_users' => ActivityLog::where('created_at', '>=', now()->subDays(30))
                ->whereNotNull('user_id')
                ->distinct('user_id')
                ->count('user_id'),
            'recent_activities' => ActivityLog::where('created_at', '>=', now()->subDay())->count(),
        ];

        // Get unique log names for filter dropdown
        $logNames = ActivityLog::select('log_name')
            ->distinct()
            ->orderBy('log_name')
            ->pluck('log_name');

        // Get users for filter dropdown
        $users = User::select('id', 'name', 'email')
            ->orderBy('name')
            ->get();

        return Inertia::render('admin/activity-logs/index', [
            'activities' => $activities,
            'stats' => $stats,
            'logNames' => $logNames,
            'users' => $users,
            'filters' => $request->only(['search', 'log_name', 'user_id', 'date_from', 'date_to']),
        ]);
    }

    /**
     * Display a specific activity log.
     */
    public function show(ActivityLog $activityLog): Response
    {
        $activityLog->load('user:id,name,email');

        return Inertia::render('admin/activity-logs/show', [
            'activity' => $activityLog,
        ]);
    }

    /**
     * Export activity logs as CSV.
     */
    public function export(Request $request): StreamedResponse
    {
        $query = ActivityLog::query()
            ->with('user:id,name,email');

        // Apply same filters as index
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                    ->orWhere('user_name', 'like', "%{$search}%")
                    ->orWhere('user_email', 'like', "%{$search}%");
            });
        }

        if ($logName = $request->input('log_name')) {
            $query->where('log_name', $logName);
        }

        if ($userId = $request->input('user_id')) {
            $query->where('user_id', $userId);
        }

        if ($dateFrom = $request->input('date_from')) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }
        if ($dateTo = $request->input('date_to')) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        $activities = $query->latest()->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="activity_logs_' . date('Y-m-d_His') . '.csv"',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        return response()->stream(function () use ($activities) {
            $handle = fopen('php://output', 'w');

            // CSV headers
            fputcsv($handle, [
                'ID',
                'User',
                'Email',
                'Log Type',
                'Description',
                'IP Address',
                'User Agent',
                'Date',
            ]);

            // CSV rows
            foreach ($activities as $activity) {
                fputcsv($handle, [
                    $activity->id,
                    $activity->user_name ?? 'System',
                    $activity->user_email ?? '-',
                    $activity->log_name,
                    $activity->description,
                    $activity->ip_address ?? '-',
                    $activity->user_agent ?? '-',
                    $activity->created_at->format('Y-m-d H:i:s'),
                ]);
            }

            fclose($handle);
        }, 200, $headers);
    }
}
