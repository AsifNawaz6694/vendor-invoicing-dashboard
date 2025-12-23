<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class CurrentJobsController extends Controller
{
    /**
     * Display the current jobs monitoring page.
     */
    public function index(Request $request): Response
    {
        // Get jobs from the jobs table (pending jobs)
        $jobsQuery = DB::table('jobs');

        // Search filter
        if ($search = $request->input('search')) {
            $jobsQuery->where('payload', 'like', "%{$search}%");
        }

        // Queue filter
        if ($queue = $request->input('queue')) {
            $jobsQuery->where('queue', $queue);
        }

        $jobs = $jobsQuery
            ->orderBy('created_at', 'desc')
            ->paginate(50)
            ->withQueryString();

        // Transform jobs data
        $jobs->getCollection()->transform(function ($job) {
            $payload = json_decode($job->payload, true);
            $jobName = $payload['displayName'] ?? 'Unknown Job';

            return [
                'id' => $job->id,
                'job_name' => $jobName,
                'queue' => $job->queue,
                'attempts' => $job->attempts,
                'reserved_at' => $job->reserved_at ? date('Y-m-d H:i:s', $job->reserved_at) : null,
                'available_at' => date('Y-m-d H:i:s', $job->available_at),
                'created_at' => date('Y-m-d H:i:s', $job->created_at),
                'status' => $job->reserved_at ? 'processing' : 'pending',
            ];
        });

        // Get failed jobs from failed_jobs table
        $failedJobsQuery = DB::table('failed_jobs');

        if ($search) {
            $failedJobsQuery->where('payload', 'like', "%{$search}%");
        }

        if ($queue) {
            $failedJobsQuery->where('queue', $queue);
        }

        $failedJobs = $failedJobsQuery
            ->orderBy('failed_at', 'desc')
            ->limit(100)
            ->get()
            ->map(function ($job) {
                $payload = json_decode($job->payload, true);
                $jobName = $payload['displayName'] ?? 'Unknown Job';

                return [
                    'id' => $job->id,
                    'uuid' => $job->uuid,
                    'job_name' => $jobName,
                    'queue' => $job->queue,
                    'connection' => $job->connection,
                    'exception' => mb_substr($job->exception, 0, 500),
                    'failed_at' => $job->failed_at,
                ];
            });

        // Get job batches
        $batches = DB::table('job_batches')
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get()
            ->map(function ($batch) {
                return [
                    'id' => $batch->id,
                    'name' => $batch->name,
                    'total_jobs' => $batch->total_jobs,
                    'pending_jobs' => $batch->pending_jobs,
                    'failed_jobs' => $batch->failed_jobs,
                    'failed_job_ids' => json_decode($batch->failed_job_ids, true) ?? [],
                    'cancelled_at' => $batch->cancelled_at,
                    'finished_at' => $batch->finished_at,
                    'created_at' => $batch->created_at,
                ];
            });

        // Get stats
        $stats = [
            'processing' => DB::table('jobs')->whereNotNull('reserved_at')->count(),
            'pending' => DB::table('jobs')->whereNull('reserved_at')->count(),
            'failed_today' => DB::table('failed_jobs')
                ->whereDate('failed_at', today())
                ->count(),
            'active_batches' => DB::table('job_batches')
                ->whereNull('cancelled_at')
                ->whereNull('finished_at')
                ->count(),
        ];

        // Get unique queues for filter
        $queues = DB::table('jobs')
            ->select('queue')
            ->distinct()
            ->pluck('queue')
            ->merge(
                DB::table('failed_jobs')
                    ->select('queue')
                    ->distinct()
                    ->pluck('queue')
            )
            ->unique()
            ->values();

        return Inertia::render('admin/current-jobs/index', [
            'jobs' => $jobs,
            'failedJobs' => $failedJobs,
            'batches' => $batches,
            'stats' => $stats,
            'queues' => $queues,
            'filters' => $request->only(['search', 'queue']),
        ]);
    }

    /**
     * Retry a failed job.
     */
    public function retryJob(Request $request, string $uuid)
    {
        $job = DB::table('failed_jobs')->where('uuid', $uuid)->first();

        if (!$job) {
            return back()->with('error', 'Job not found.');
        }

        // Re-queue the job
        DB::table('jobs')->insert([
            'queue' => $job->queue,
            'payload' => $job->payload,
            'attempts' => 0,
            'reserved_at' => null,
            'available_at' => now()->timestamp,
            'created_at' => now()->timestamp,
        ]);

        // Remove from failed jobs
        DB::table('failed_jobs')->where('uuid', $uuid)->delete();

        return back()->with('success', 'Job has been queued for retry.');
    }

    /**
     * Delete a failed job.
     */
    public function deleteFailedJob(Request $request, string $uuid)
    {
        DB::table('failed_jobs')->where('uuid', $uuid)->delete();

        return back()->with('success', 'Failed job has been deleted.');
    }

    /**
     * Flush all failed jobs.
     */
    public function flushFailedJobs(Request $request)
    {
        DB::table('failed_jobs')->truncate();

        return back()->with('success', 'All failed jobs have been deleted.');
    }
}
