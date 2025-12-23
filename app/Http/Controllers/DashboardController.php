<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Carbon\Carbon;

class DashboardController extends Controller
{
    /**
     * Display the dashboard based on user role.
     */
    public function index()
    {
        $user = auth()->user();

        if (!$user->role) {
            // If user has no role, show basic dashboard
            return Inertia::render('dashboard', [
                'metrics' => $this->getBasicMetrics(),
                'userRole' => null
            ]);
        }

        // Get role-specific metrics
        $metrics = match(true) {
            $user->isSuperAdmin() => $this->getSuperAdminMetrics(),
            $user->isAccountant() => $this->getAccountantMetrics(),
            $user->isVendor() => $this->getVendorMetrics($user),
            default => $this->getBasicMetrics()
        };

        return Inertia::render('dashboard', [
            'metrics' => $metrics,
            'userRole' => $user->role->slug,
            'userName' => $user->name,
        ]);
    }

    /**
     * Get metrics for Super Admin role.
     */
    private function getSuperAdminMetrics()
    {
        $totalInvoices = Invoice::count();
        $totalUsers = User::count();
        $totalVendors = User::whereHas('role', fn($q) => $q->where('slug', 'vendor'))->count();

        // Invoice status breakdown
        $statusBreakdown = Invoice::select('status', DB::raw('count(*) as count'), DB::raw('sum(amount) as total_amount'))
            ->groupBy('status')
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->status => [
                    'count' => $item->count,
                    'amount' => $item->total_amount ?? 0
                ]];
            })->toArray();

        // Ensure all statuses are present
        $statuses = ['pending', 'approved', 'rejected', 'paid'];
        foreach ($statuses as $status) {
            if (!isset($statusBreakdown[$status])) {
                $statusBreakdown[$status] = ['count' => 0, 'amount' => 0];
            }
        }

        // Total amounts
        $totalAmountDue = Invoice::whereIn('status', ['pending', 'approved'])->sum('amount');
        $totalAmountPaid = Invoice::where('status', 'paid')->sum('amount');

        // Upcoming due invoices (next 7 days)
        $upcomingDueInvoices = Invoice::where('status', '!=', 'paid')
            ->where('due_date', '>=', Carbon::today())
            ->where('due_date', '<=', Carbon::today()->addDays(7))
            ->with('vendor:id,name')
            ->select('id', 'invoice_number', 'vendor_id', 'vendor_name', 'amount', 'due_date', 'status')
            ->orderBy('due_date')
            ->limit(5)
            ->get();

        // Recently paid invoices
        $recentlyPaidInvoices = Invoice::where('status', 'paid')
            ->with('vendor:id,name')
            ->select('id', 'invoice_number', 'vendor_id', 'vendor_name', 'amount', 'approved_at')
            ->orderBy('approved_at', 'desc')
            ->limit(5)
            ->get();

        // Invoice uploads over time (last 30 days)
        $invoiceUploadsOverTime = Invoice::where('created_at', '>=', Carbon::now()->subDays(30))
            ->selectRaw('DATE(created_at) as date, count(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(function ($item) {
                return [
                    'date' => Carbon::parse($item->date)->format('M d'),
                    'count' => $item->count
                ];
            });

        // Company breakdown (top vendors)
        $companyBreakdown = Invoice::select('vendor_name', DB::raw('count(*) as invoice_count'), DB::raw('sum(amount) as total_amount'))
            ->groupBy('vendor_name')
            ->orderBy('total_amount', 'desc')
            ->limit(5)
            ->get();

        // Currency breakdown
        $currencyBreakdown = [
            ['currency' => 'USD', 'amount' => Invoice::sum('amount'), 'count' => Invoice::count()]
        ];

        // System health metrics
        $pendingApprovals = Invoice::where('status', 'pending')->count();
        $overdueInvoices = Invoice::where('status', '!=', 'paid')
            ->where('due_date', '<', Carbon::today())
            ->count();

        // Role distribution
        $roleDistribution = Role::withCount('users')
            ->get()
            ->map(function ($role) {
                return [
                    'role' => $role->name,
                    'count' => $role->users_count
                ];
            });

        return [
            'totalInvoices' => $totalInvoices,
            'totalUsers' => $totalUsers,
            'totalVendors' => $totalVendors,
            'statusBreakdown' => $statusBreakdown,
            'totalAmountDue' => $totalAmountDue,
            'totalAmountPaid' => $totalAmountPaid,
            'upcomingDueInvoices' => $upcomingDueInvoices,
            'recentlyPaidInvoices' => $recentlyPaidInvoices,
            'invoiceUploadsOverTime' => $invoiceUploadsOverTime,
            'companyBreakdown' => $companyBreakdown,
            'currencyBreakdown' => $currencyBreakdown,
            'pendingApprovals' => $pendingApprovals,
            'overdueInvoices' => $overdueInvoices,
            'roleDistribution' => $roleDistribution,
        ];
    }

    /**
     * Get metrics for Accountant role.
     */
    private function getAccountantMetrics()
    {
        $totalInvoices = Invoice::count();

        // Invoice status breakdown
        $statusBreakdown = Invoice::select('status', DB::raw('count(*) as count'), DB::raw('sum(amount) as total_amount'))
            ->groupBy('status')
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->status => [
                    'count' => $item->count,
                    'amount' => $item->total_amount ?? 0
                ]];
            })->toArray();

        // Ensure all statuses are present
        $statuses = ['pending', 'approved', 'rejected', 'paid'];
        foreach ($statuses as $status) {
            if (!isset($statusBreakdown[$status])) {
                $statusBreakdown[$status] = ['count' => 0, 'amount' => 0];
            }
        }

        // Total amounts
        $totalAmountDue = Invoice::whereIn('status', ['pending', 'approved'])->sum('amount');
        $totalAmountPaid = Invoice::where('status', 'paid')->sum('amount');

        // Upcoming due invoices
        $upcomingDueInvoices = Invoice::where('status', '!=', 'paid')
            ->where('due_date', '>=', Carbon::today())
            ->where('due_date', '<=', Carbon::today()->addDays(7))
            ->with('vendor:id,name')
            ->select('id', 'invoice_number', 'vendor_id', 'vendor_name', 'amount', 'due_date', 'status')
            ->orderBy('due_date')
            ->limit(10)
            ->get();

        // Recently paid invoices
        $recentlyPaidInvoices = Invoice::where('status', 'paid')
            ->with('vendor:id,name')
            ->select('id', 'invoice_number', 'vendor_id', 'vendor_name', 'amount', 'approved_at')
            ->orderBy('approved_at', 'desc')
            ->limit(10)
            ->get();

        // Invoices requiring action
        $pendingApprovals = Invoice::where('status', 'pending')
            ->with('vendor:id,name')
            ->select('id', 'invoice_number', 'vendor_id', 'vendor_name', 'amount', 'created_at', 'due_date')
            ->orderBy('created_at')
            ->limit(10)
            ->get();

        // Invoice uploads over time
        $invoiceUploadsOverTime = Invoice::where('created_at', '>=', Carbon::now()->subDays(30))
            ->selectRaw('DATE(created_at) as date, count(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(function ($item) {
                return [
                    'date' => Carbon::parse($item->date)->format('M d'),
                    'count' => $item->count
                ];
            });

        // Company breakdown
        $companyBreakdown = Invoice::select('vendor_name', DB::raw('count(*) as invoice_count'), DB::raw('sum(amount) as total_amount'))
            ->groupBy('vendor_name')
            ->orderBy('total_amount', 'desc')
            ->limit(10)
            ->get();

        // Overdue invoices
        $overdueInvoices = Invoice::where('status', '!=', 'paid')
            ->where('due_date', '<', Carbon::today())
            ->with('vendor:id,name')
            ->select('id', 'invoice_number', 'vendor_id', 'vendor_name', 'amount', 'due_date', 'status')
            ->orderBy('due_date')
            ->limit(10)
            ->get();

        return [
            'totalInvoices' => $totalInvoices,
            'statusBreakdown' => $statusBreakdown,
            'totalAmountDue' => $totalAmountDue,
            'totalAmountPaid' => $totalAmountPaid,
            'upcomingDueInvoices' => $upcomingDueInvoices,
            'recentlyPaidInvoices' => $recentlyPaidInvoices,
            'pendingApprovals' => $pendingApprovals,
            'invoiceUploadsOverTime' => $invoiceUploadsOverTime,
            'companyBreakdown' => $companyBreakdown,
            'overdueInvoices' => $overdueInvoices,
            'pendingApprovalsCount' => $pendingApprovals->count(),
            'overdueCount' => $overdueInvoices->count(),
        ];
    }

    /**
     * Get metrics for Vendor role.
     */
    private function getVendorMetrics(User $user)
    {
        $vendorId = $user->id;

        // Total invoices for this vendor
        $totalInvoices = Invoice::where('vendor_id', $vendorId)->count();

        // Invoice status breakdown for vendor
        $statusBreakdown = Invoice::where('vendor_id', $vendorId)
            ->select('status', DB::raw('count(*) as count'), DB::raw('sum(amount) as total_amount'))
            ->groupBy('status')
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->status => [
                    'count' => $item->count,
                    'amount' => $item->total_amount ?? 0
                ]];
            })->toArray();

        // Ensure all statuses are present
        $statuses = ['pending', 'approved', 'rejected', 'paid'];
        foreach ($statuses as $status) {
            if (!isset($statusBreakdown[$status])) {
                $statusBreakdown[$status] = ['count' => 0, 'amount' => 0];
            }
        }

        // Total amounts for vendor
        $totalAmountDue = Invoice::where('vendor_id', $vendorId)
            ->whereIn('status', ['pending', 'approved'])
            ->sum('amount');
        $totalAmountPaid = Invoice::where('vendor_id', $vendorId)
            ->where('status', 'paid')
            ->sum('amount');

        // Upcoming due invoices for vendor
        $upcomingDueInvoices = Invoice::where('vendor_id', $vendorId)
            ->where('status', '!=', 'paid')
            ->where('due_date', '>=', Carbon::today())
            ->where('due_date', '<=', Carbon::today()->addDays(14))
            ->select('id', 'invoice_number', 'amount', 'due_date', 'status')
            ->orderBy('due_date')
            ->get();

        // Recently paid invoices for vendor
        $recentlyPaidInvoices = Invoice::where('vendor_id', $vendorId)
            ->where('status', 'paid')
            ->select('id', 'invoice_number', 'amount', 'approved_at')
            ->orderBy('approved_at', 'desc')
            ->limit(10)
            ->get();

        // Recent invoices
        $recentInvoices = Invoice::where('vendor_id', $vendorId)
            ->select('id', 'invoice_number', 'amount', 'status', 'created_at', 'due_date')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        // Invoice uploads over time (vendor's invoices)
        $invoiceUploadsOverTime = Invoice::where('vendor_id', $vendorId)
            ->where('created_at', '>=', Carbon::now()->subDays(30))
            ->selectRaw('DATE(created_at) as date, count(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(function ($item) {
                return [
                    'date' => Carbon::parse($item->date)->format('M d'),
                    'count' => $item->count
                ];
            });

        // Monthly summary
        $monthlySummary = Invoice::where('vendor_id', $vendorId)
            ->where('created_at', '>=', Carbon::now()->subMonths(6))
            ->selectRaw('DATE_FORMAT(created_at, "%Y-%m") as month, count(*) as count, sum(amount) as total_amount')
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->map(function ($item) {
                return [
                    'month' => Carbon::parse($item->month)->format('M Y'),
                    'count' => $item->count,
                    'amount' => $item->total_amount
                ];
            });

        // Overdue invoices for vendor
        $overdueInvoices = Invoice::where('vendor_id', $vendorId)
            ->where('status', '!=', 'paid')
            ->where('due_date', '<', Carbon::today())
            ->select('id', 'invoice_number', 'amount', 'due_date', 'status')
            ->orderBy('due_date')
            ->get();

        return [
            'totalInvoices' => $totalInvoices,
            'statusBreakdown' => $statusBreakdown,
            'totalAmountDue' => $totalAmountDue,
            'totalAmountPaid' => $totalAmountPaid,
            'upcomingDueInvoices' => $upcomingDueInvoices,
            'recentlyPaidInvoices' => $recentlyPaidInvoices,
            'recentInvoices' => $recentInvoices,
            'invoiceUploadsOverTime' => $invoiceUploadsOverTime,
            'monthlySummary' => $monthlySummary,
            'overdueInvoices' => $overdueInvoices,
            'overdueCount' => $overdueInvoices->count(),
            'pendingCount' => $statusBreakdown['pending']['count'] ?? 0,
        ];
    }

    /**
     * Get basic metrics for users without roles.
     */
    private function getBasicMetrics()
    {
        return [
            'totalInvoices' => 0,
            'statusBreakdown' => [
                'pending' => ['count' => 0, 'amount' => 0],
                'approved' => ['count' => 0, 'amount' => 0],
                'rejected' => ['count' => 0, 'amount' => 0],
                'paid' => ['count' => 0, 'amount' => 0],
            ],
            'totalAmountDue' => 0,
            'totalAmountPaid' => 0,
            'upcomingDueInvoices' => [],
            'recentlyPaidInvoices' => [],
            'invoiceUploadsOverTime' => [],
        ];
    }
}