import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Card,
    CardContent,
} from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import AppLayout from '@/layouts/app-layout';
import { Head, router, usePage } from '@inertiajs/react';
import {
    Search,
    RefreshCw,
    Play,
    Clock,
    AlertCircle,
    CheckCircle,
    Layers,
    Trash2,
    RotateCcw,
} from 'lucide-react';
import { useState, useCallback } from 'react';

interface Job {
    id: number;
    job_name: string;
    queue: string;
    attempts: number;
    reserved_at: string | null;
    available_at: string;
    created_at: string;
    status: 'processing' | 'pending';
}

interface FailedJob {
    id: number;
    uuid: string;
    job_name: string;
    queue: string;
    connection: string;
    exception: string;
    failed_at: string;
}

interface Batch {
    id: string;
    name: string;
    total_jobs: number;
    pending_jobs: number;
    failed_jobs: number;
    failed_job_ids: string[];
    cancelled_at: string | null;
    finished_at: string | null;
    created_at: string;
}

interface Props {
    jobs: {
        data: Job[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
    failedJobs: FailedJob[];
    batches: Batch[];
    stats: {
        processing: number;
        pending: number;
        failed_today: number;
        active_batches: number;
    };
    queues: string[];
    filters: {
        search?: string;
        queue?: string;
    };
}

export default function CurrentJobsIndex({
    jobs,
    failedJobs,
    batches,
    stats,
    queues,
    filters,
}: Props) {
    const [search, setSearch] = useState(filters.search || '');
    const [queueFilter, setQueueFilter] = useState(filters.queue || 'all');
    const [activeTab, setActiveTab] = useState<'running' | 'failed' | 'batches'>('running');
    const { flash } = usePage().props as any;

    const breadcrumbs = [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Current Jobs', href: '/admin/current-jobs' },
    ];

    // Apply filters immediately on change
    const applyFilters = useCallback((overrides: Record<string, string> = {}) => {
        const params: Record<string, string> = {};

        const currentSearch = overrides.search !== undefined ? overrides.search : search;
        const currentQueue = overrides.queue !== undefined ? overrides.queue : queueFilter;

        if (currentSearch) params.search = currentSearch;
        if (currentQueue && currentQueue !== 'all') params.queue = currentQueue;

        router.get('/admin/current-jobs', params, { preserveState: true, preserveScroll: true });
    }, [search, queueFilter]);

    const handleSearchKeyDown = (e: React.KeyboardEvent) => {
        if (e.key === 'Enter') {
            applyFilters();
        }
    };

    const handleQueueChange = (value: string) => {
        setQueueFilter(value);
        applyFilters({ queue: value });
    };

    const clearFilters = () => {
        setSearch('');
        setQueueFilter('all');
        router.get('/admin/current-jobs', {}, { preserveState: true });
    };

    const handleRefresh = () => {
        router.get('/admin/current-jobs', {}, { preserveState: true, preserveScroll: true });
    };

    const handleRetryJob = (uuid: string) => {
        if (confirm('Are you sure you want to retry this job?')) {
            router.post(`/admin/current-jobs/retry/${uuid}`);
        }
    };

    const handleDeleteFailedJob = (uuid: string) => {
        if (confirm('Are you sure you want to delete this failed job?')) {
            router.delete(`/admin/current-jobs/failed/${uuid}`);
        }
    };

    const handleFlushFailedJobs = () => {
        if (confirm('Are you sure you want to delete ALL failed jobs? This action cannot be undone.')) {
            router.post('/admin/current-jobs/flush-failed');
        }
    };

    const hasActiveFilters = search || queueFilter !== 'all';

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Current Jobs" />

            <div className="space-y-6 p-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-3xl font-bold tracking-tight">Current Jobs</h1>
                        <p className="text-muted-foreground mt-2">
                            Monitor and manage background queue jobs in real-time
                        </p>
                    </div>
                    <Button variant="outline" onClick={handleRefresh}>
                        <RefreshCw className="mr-2 h-4 w-4" />
                        Refresh
                    </Button>
                </div>

                {/* Flash Messages */}
                {flash?.success && (
                    <div className="rounded-md bg-green-50 p-3 text-sm font-medium text-green-600">
                        {flash.success}
                    </div>
                )}

                {flash?.error && (
                    <div className="rounded-md bg-red-50 p-3 text-sm font-medium text-red-600">
                        {flash.error}
                    </div>
                )}

                {/* Stats Cards */}
                <div className="grid gap-4 md:grid-cols-4">
                    <Card>
                        <CardContent className="p-6">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-sm font-medium text-muted-foreground">Processing</p>
                                    <p className="text-3xl font-bold">{stats.processing}</p>
                                    <p className="text-xs text-muted-foreground mt-1">Currently running jobs</p>
                                </div>
                                <Play className="h-8 w-8 text-blue-500" />
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardContent className="p-6">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-sm font-medium text-muted-foreground">Pending</p>
                                    <p className="text-3xl font-bold">{stats.pending}</p>
                                    <p className="text-xs text-muted-foreground mt-1">Waiting in queue</p>
                                </div>
                                <Clock className="h-8 w-8 text-yellow-500" />
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardContent className="p-6">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-sm font-medium text-muted-foreground">Failed Today</p>
                                    <p className="text-3xl font-bold">{stats.failed_today}</p>
                                    <p className="text-xs text-muted-foreground mt-1">Last 24 hours</p>
                                </div>
                                <AlertCircle className="h-8 w-8 text-red-500" />
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardContent className="p-6">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-sm font-medium text-muted-foreground">Active Batches</p>
                                    <p className="text-3xl font-bold">{stats.active_batches}</p>
                                    <p className="text-xs text-muted-foreground mt-1">Batch jobs running</p>
                                </div>
                                <CheckCircle className="h-8 w-8 text-green-500" />
                            </div>
                        </CardContent>
                    </Card>
                </div>

                {/* Filters */}
                <Card>
                    <CardContent className="p-6">
                        <div className="flex items-center gap-2 mb-4">
                            <Search className="h-4 w-4 text-muted-foreground" />
                            <span className="font-medium">Filters</span>
                        </div>

                        <div className="flex gap-4 items-end">
                            {/* Search */}
                            <div className="flex-1 max-w-md">
                                <label className="text-sm font-medium text-muted-foreground mb-2 block">
                                    Search
                                </label>
                                <div className="relative">
                                    <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                                    <Input
                                        placeholder="Search in job payload..."
                                        value={search}
                                        onChange={(e) => setSearch(e.target.value)}
                                        onKeyDown={handleSearchKeyDown}
                                        onBlur={() => applyFilters()}
                                        className="pl-9"
                                    />
                                </div>
                            </div>

                            {/* Queue Filter */}
                            <div className="w-[200px]">
                                <label className="text-sm font-medium text-muted-foreground mb-2 block">
                                    Queue
                                </label>
                                <Select value={queueFilter} onValueChange={handleQueueChange}>
                                    <SelectTrigger>
                                        <SelectValue placeholder="All queues" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">All queues</SelectItem>
                                        {queues.map((queue) => (
                                            <SelectItem key={queue} value={queue}>
                                                {queue}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>

                            {hasActiveFilters && (
                                <Button variant="ghost" size="sm" onClick={clearFilters}>
                                    Clear Filters
                                </Button>
                            )}
                        </div>
                    </CardContent>
                </Card>

                {/* Tabs */}
                <div className="flex gap-2 border-b">
                    <button
                        onClick={() => setActiveTab('running')}
                        className={`px-4 py-2 text-sm font-medium border-b-2 transition-colors ${
                            activeTab === 'running'
                                ? 'border-primary text-primary'
                                : 'border-transparent text-muted-foreground hover:text-foreground'
                        }`}
                    >
                        Running Jobs ({jobs.total})
                    </button>
                    <button
                        onClick={() => setActiveTab('failed')}
                        className={`px-4 py-2 text-sm font-medium border-b-2 transition-colors ${
                            activeTab === 'failed'
                                ? 'border-primary text-primary'
                                : 'border-transparent text-muted-foreground hover:text-foreground'
                        }`}
                    >
                        Failed Jobs ({failedJobs.length})
                    </button>
                    <button
                        onClick={() => setActiveTab('batches')}
                        className={`px-4 py-2 text-sm font-medium border-b-2 transition-colors ${
                            activeTab === 'batches'
                                ? 'border-primary text-primary'
                                : 'border-transparent text-muted-foreground hover:text-foreground'
                        }`}
                    >
                        Batches ({batches.length})
                    </button>
                </div>

                {/* Running Jobs Tab */}
                {activeTab === 'running' && (
                    <Card>
                        <CardContent className="p-6">
                            <div className="mb-4">
                                <h2 className="text-lg font-semibold">Running Jobs</h2>
                                <p className="text-sm text-muted-foreground">
                                    Showing {jobs.data.length} of {jobs.total} jobs currently being processed
                                </p>
                            </div>

                            <div className="rounded-md border">
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead className="w-[80px]">ID</TableHead>
                                            <TableHead>Job Name</TableHead>
                                            <TableHead>Queue</TableHead>
                                            <TableHead>Attempts</TableHead>
                                            <TableHead>Processing Time</TableHead>
                                            <TableHead>Started At</TableHead>
                                            <TableHead>Status</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {jobs.data.length === 0 ? (
                                            <TableRow>
                                                <TableCell colSpan={7} className="text-center py-8 text-muted-foreground">
                                                    No jobs currently running
                                                </TableCell>
                                            </TableRow>
                                        ) : (
                                            jobs.data.map((job) => (
                                                <TableRow key={job.id}>
                                                    <TableCell className="font-mono text-sm text-muted-foreground">
                                                        {job.id}
                                                    </TableCell>
                                                    <TableCell className="font-medium">
                                                        {job.job_name}
                                                    </TableCell>
                                                    <TableCell>
                                                        <Badge variant="outline">{job.queue}</Badge>
                                                    </TableCell>
                                                    <TableCell>{job.attempts}</TableCell>
                                                    <TableCell className="text-sm text-muted-foreground">
                                                        {job.reserved_at ? (
                                                            <span>
                                                                {Math.round(
                                                                    (Date.now() - new Date(job.reserved_at).getTime()) / 1000
                                                                )}s
                                                            </span>
                                                        ) : (
                                                            '-'
                                                        )}
                                                    </TableCell>
                                                    <TableCell className="text-sm text-muted-foreground">
                                                        {job.created_at}
                                                    </TableCell>
                                                    <TableCell>
                                                        <Badge
                                                            variant={job.status === 'processing' ? 'default' : 'secondary'}
                                                        >
                                                            {job.status}
                                                        </Badge>
                                                    </TableCell>
                                                </TableRow>
                                            ))
                                        )}
                                    </TableBody>
                                </Table>
                            </div>
                        </CardContent>
                    </Card>
                )}

                {/* Failed Jobs Tab */}
                {activeTab === 'failed' && (
                    <Card>
                        <CardContent className="p-6">
                            <div className="mb-4 flex items-center justify-between">
                                <div>
                                    <h2 className="text-lg font-semibold">Failed Jobs</h2>
                                    <p className="text-sm text-muted-foreground">
                                        {failedJobs.length} failed jobs
                                    </p>
                                </div>
                                {failedJobs.length > 0 && (
                                    <Button
                                        variant="destructive"
                                        size="sm"
                                        onClick={handleFlushFailedJobs}
                                    >
                                        <Trash2 className="mr-2 h-4 w-4" />
                                        Flush All
                                    </Button>
                                )}
                            </div>

                            <div className="rounded-md border">
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead className="w-[80px]">ID</TableHead>
                                            <TableHead>Job Name</TableHead>
                                            <TableHead>Queue</TableHead>
                                            <TableHead>Exception</TableHead>
                                            <TableHead>Failed At</TableHead>
                                            <TableHead className="w-[100px]">Actions</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {failedJobs.length === 0 ? (
                                            <TableRow>
                                                <TableCell colSpan={6} className="text-center py-8 text-muted-foreground">
                                                    No failed jobs
                                                </TableCell>
                                            </TableRow>
                                        ) : (
                                            failedJobs.map((job) => (
                                                <TableRow key={job.uuid}>
                                                    <TableCell className="font-mono text-sm text-muted-foreground">
                                                        {job.id}
                                                    </TableCell>
                                                    <TableCell className="font-medium">
                                                        {job.job_name}
                                                    </TableCell>
                                                    <TableCell>
                                                        <Badge variant="outline">{job.queue}</Badge>
                                                    </TableCell>
                                                    <TableCell className="max-w-md">
                                                        <p className="text-sm text-red-600 truncate" title={job.exception}>
                                                            {job.exception.substring(0, 100)}...
                                                        </p>
                                                    </TableCell>
                                                    <TableCell className="text-sm text-muted-foreground">
                                                        {new Date(job.failed_at).toLocaleString()}
                                                    </TableCell>
                                                    <TableCell>
                                                        <div className="flex gap-1">
                                                            <Button
                                                                variant="ghost"
                                                                size="sm"
                                                                onClick={() => handleRetryJob(job.uuid)}
                                                                title="Retry Job"
                                                            >
                                                                <RotateCcw className="h-4 w-4" />
                                                            </Button>
                                                            <Button
                                                                variant="ghost"
                                                                size="sm"
                                                                onClick={() => handleDeleteFailedJob(job.uuid)}
                                                                title="Delete Job"
                                                            >
                                                                <Trash2 className="h-4 w-4 text-red-500" />
                                                            </Button>
                                                        </div>
                                                    </TableCell>
                                                </TableRow>
                                            ))
                                        )}
                                    </TableBody>
                                </Table>
                            </div>
                        </CardContent>
                    </Card>
                )}

                {/* Batches Tab */}
                {activeTab === 'batches' && (
                    <Card>
                        <CardContent className="p-6">
                            <div className="mb-4">
                                <h2 className="text-lg font-semibold">Job Batches</h2>
                                <p className="text-sm text-muted-foreground">
                                    {batches.length} batches
                                </p>
                            </div>

                            <div className="rounded-md border">
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>Batch ID</TableHead>
                                            <TableHead>Name</TableHead>
                                            <TableHead>Total Jobs</TableHead>
                                            <TableHead>Pending</TableHead>
                                            <TableHead>Failed</TableHead>
                                            <TableHead>Status</TableHead>
                                            <TableHead>Created At</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {batches.length === 0 ? (
                                            <TableRow>
                                                <TableCell colSpan={7} className="text-center py-8 text-muted-foreground">
                                                    No batches found
                                                </TableCell>
                                            </TableRow>
                                        ) : (
                                            batches.map((batch) => (
                                                <TableRow key={batch.id}>
                                                    <TableCell className="font-mono text-sm">
                                                        {batch.id.substring(0, 8)}...
                                                    </TableCell>
                                                    <TableCell className="font-medium">
                                                        {batch.name || 'Unnamed Batch'}
                                                    </TableCell>
                                                    <TableCell>{batch.total_jobs}</TableCell>
                                                    <TableCell>{batch.pending_jobs}</TableCell>
                                                    <TableCell>
                                                        {batch.failed_jobs > 0 ? (
                                                            <Badge variant="destructive">{batch.failed_jobs}</Badge>
                                                        ) : (
                                                            <span>0</span>
                                                        )}
                                                    </TableCell>
                                                    <TableCell>
                                                        {batch.cancelled_at ? (
                                                            <Badge variant="destructive">Cancelled</Badge>
                                                        ) : batch.finished_at ? (
                                                            <Badge variant="default">Completed</Badge>
                                                        ) : (
                                                            <Badge variant="secondary">Running</Badge>
                                                        )}
                                                    </TableCell>
                                                    <TableCell className="text-sm text-muted-foreground">
                                                        {new Date(batch.created_at).toLocaleString()}
                                                    </TableCell>
                                                </TableRow>
                                            ))
                                        )}
                                    </TableBody>
                                </Table>
                            </div>
                        </CardContent>
                    </Card>
                )}
            </div>
        </AppLayout>
    );
}
