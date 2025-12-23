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
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import AppLayout from '@/layouts/app-layout';
import { Head, Link, router } from '@inertiajs/react';
import {
    Activity,
    Search,
    Download,
    Filter,
    Eye,
    Calendar,
    Users,
    Layers,
    Clock,
    Briefcase,
} from 'lucide-react';
import { useState, useCallback } from 'react';

interface ActivityLogItem {
    id: number;
    user_id: number | null;
    user_name: string | null;
    user_email: string | null;
    log_name: string;
    description: string;
    subject_type: string | null;
    subject_id: number | null;
    properties: Record<string, any> | null;
    ip_address: string | null;
    user_agent: string | null;
    created_at: string;
    user?: {
        id: number;
        name: string;
        email: string;
    } | null;
}

interface User {
    id: number;
    name: string;
    email: string;
}

interface Props {
    activities: {
        data: ActivityLogItem[];
        links: any[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
    stats: {
        total_activities: number;
        log_types: number;
        active_users: number;
        recent_activities: number;
    };
    logNames: string[];
    users: User[];
    filters: {
        search?: string;
        log_name?: string;
        user_id?: string;
        date_from?: string;
        date_to?: string;
    };
}

export default function ActivityLogsIndex({
    activities,
    stats,
    logNames,
    users,
    filters,
}: Props) {
    const [search, setSearch] = useState(filters.search || '');
    const [logNameFilter, setLogNameFilter] = useState(filters.log_name || 'all');
    const [userFilter, setUserFilter] = useState(filters.user_id || 'all');
    const [dateFrom, setDateFrom] = useState(filters.date_from || '');
    const [dateTo, setDateTo] = useState(filters.date_to || '');
    const [selectedActivity, setSelectedActivity] = useState<ActivityLogItem | null>(null);

    const breadcrumbs = [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Activity Logs', href: '/admin/activity-logs' },
    ];

    // Apply filters immediately on change
    const applyFilters = useCallback((overrides: Record<string, string> = {}) => {
        const params: Record<string, string> = {};

        const currentSearch = overrides.search !== undefined ? overrides.search : search;
        const currentLogName = overrides.log_name !== undefined ? overrides.log_name : logNameFilter;
        const currentUser = overrides.user_id !== undefined ? overrides.user_id : userFilter;
        const currentDateFrom = overrides.date_from !== undefined ? overrides.date_from : dateFrom;
        const currentDateTo = overrides.date_to !== undefined ? overrides.date_to : dateTo;

        if (currentSearch) params.search = currentSearch;
        if (currentLogName && currentLogName !== 'all') params.log_name = currentLogName;
        if (currentUser && currentUser !== 'all') params.user_id = currentUser;
        if (currentDateFrom) params.date_from = currentDateFrom;
        if (currentDateTo) params.date_to = currentDateTo;

        router.get('/admin/activity-logs', params, { preserveState: true, preserveScroll: true });
    }, [search, logNameFilter, userFilter, dateFrom, dateTo]);

    const handleSearchKeyDown = (e: React.KeyboardEvent) => {
        if (e.key === 'Enter') {
            applyFilters();
        }
    };

    const handleLogNameChange = (value: string) => {
        setLogNameFilter(value);
        applyFilters({ log_name: value });
    };

    const handleUserChange = (value: string) => {
        setUserFilter(value);
        applyFilters({ user_id: value });
    };

    const handleDateFromChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        const value = e.target.value;
        setDateFrom(value);
        applyFilters({ date_from: value });
    };

    const handleDateToChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        const value = e.target.value;
        setDateTo(value);
        applyFilters({ date_to: value });
    };

    const clearFilters = () => {
        setSearch('');
        setLogNameFilter('all');
        setUserFilter('all');
        setDateFrom('');
        setDateTo('');
        router.get('/admin/activity-logs', {}, { preserveState: true });
    };

    const handleExport = () => {
        const params = new URLSearchParams();
        if (search) params.append('search', search);
        if (logNameFilter && logNameFilter !== 'all') params.append('log_name', logNameFilter);
        if (userFilter && userFilter !== 'all') params.append('user_id', userFilter);
        if (dateFrom) params.append('date_from', dateFrom);
        if (dateTo) params.append('date_to', dateTo);

        window.location.href = `/admin/activity-logs-export?${params.toString()}`;
    };

    const getLogTypeBadgeVariant = (logName: string): "default" | "secondary" | "destructive" | "outline" => {
        const variants: Record<string, "default" | "secondary" | "destructive" | "outline"> = {
            'authentication': 'default',
            'user-management': 'secondary',
            'system': 'destructive',
            'invoice': 'outline',
        };
        return variants[logName] || 'outline';
    };

    const getLogTypeColor = (logName: string): string => {
        const colors: Record<string, string> = {
            'authentication': 'bg-blue-100 text-blue-800 border-blue-200',
            'user-management': 'bg-purple-100 text-purple-800 border-purple-200',
            'system': 'bg-gray-100 text-gray-800 border-gray-200',
            'invoice': 'bg-green-100 text-green-800 border-green-200',
            'shipment-tracking-update': 'bg-orange-100 text-orange-800 border-orange-200',
            'role-management': 'bg-indigo-100 text-indigo-800 border-indigo-200',
            'permission-management': 'bg-pink-100 text-pink-800 border-pink-200',
        };
        return colors[logName] || 'bg-gray-100 text-gray-800 border-gray-200';
    };

    const hasActiveFilters = search || logNameFilter !== 'all' || userFilter !== 'all' || dateFrom || dateTo;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Activity Logs" />

            <div className="space-y-6 p-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-3xl font-bold tracking-tight">Activity Logs</h1>
                        <p className="text-muted-foreground mt-2">
                            Monitor and track all user activities across the system
                        </p>
                    </div>
                    <div className="flex gap-2">
                        <Link href="/admin/current-jobs">
                            <Button variant="outline">
                                <Briefcase className="mr-2 h-4 w-4" />
                                Current Jobs
                            </Button>
                        </Link>
                        <Button variant="outline" onClick={handleExport}>
                            <Download className="mr-2 h-4 w-4" />
                            Export CSV
                        </Button>
                    </div>
                </div>

                {/* Stats Cards */}
                <div className="grid gap-4 md:grid-cols-4">
                    <Card>
                        <CardContent className="p-6">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-sm font-medium text-muted-foreground">Total Activities</p>
                                    <p className="text-3xl font-bold">{stats.total_activities.toLocaleString()}</p>
                                    <p className="text-xs text-muted-foreground mt-1">Last 30 days</p>
                                </div>
                                <Activity className="h-8 w-8 text-muted-foreground" />
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardContent className="p-6">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-sm font-medium text-muted-foreground">Log Types</p>
                                    <p className="text-3xl font-bold">{stats.log_types}</p>
                                    <p className="text-xs text-muted-foreground mt-1">Different log categories</p>
                                </div>
                                <Layers className="h-8 w-8 text-muted-foreground" />
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardContent className="p-6">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-sm font-medium text-muted-foreground">Active Users</p>
                                    <p className="text-3xl font-bold">{stats.active_users}</p>
                                    <p className="text-xs text-muted-foreground mt-1">Users with activities</p>
                                </div>
                                <Users className="h-8 w-8 text-muted-foreground" />
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardContent className="p-6">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-sm font-medium text-muted-foreground">Recent Activities</p>
                                    <p className="text-3xl font-bold">{stats.recent_activities}</p>
                                    <p className="text-xs text-muted-foreground mt-1">Last 24 hours</p>
                                </div>
                                <Clock className="h-8 w-8 text-muted-foreground" />
                            </div>
                        </CardContent>
                    </Card>
                </div>

                {/* Filters */}
                <Card>
                    <CardContent className="p-6">
                        <div className="flex items-center gap-2 mb-4">
                            <Filter className="h-4 w-4 text-muted-foreground" />
                            <span className="font-medium">Filters</span>
                        </div>

                        <div className="grid gap-4 md:grid-cols-5">
                            {/* Search */}
                            <div>
                                <label className="text-sm font-medium text-muted-foreground mb-2 block">
                                    Search
                                </label>
                                <div className="relative">
                                    <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                                    <Input
                                        placeholder="Search activities..."
                                        value={search}
                                        onChange={(e) => setSearch(e.target.value)}
                                        onKeyDown={handleSearchKeyDown}
                                        onBlur={() => applyFilters()}
                                        className="pl-9"
                                    />
                                </div>
                            </div>

                            {/* Log Name */}
                            <div>
                                <label className="text-sm font-medium text-muted-foreground mb-2 block">
                                    Log Name
                                </label>
                                <Select value={logNameFilter} onValueChange={handleLogNameChange}>
                                    <SelectTrigger>
                                        <SelectValue placeholder="All log types" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">All log types</SelectItem>
                                        {logNames.map((name) => (
                                            <SelectItem key={name} value={name}>
                                                {name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>

                            {/* User Filter */}
                            <div>
                                <label className="text-sm font-medium text-muted-foreground mb-2 block">
                                    User ({users.length} users)
                                </label>
                                <Select value={userFilter} onValueChange={handleUserChange}>
                                    <SelectTrigger>
                                        <SelectValue placeholder="All users" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">All users</SelectItem>
                                        {users.map((user) => (
                                            <SelectItem key={user.id} value={user.id.toString()}>
                                                {user.name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>

                            {/* Date From */}
                            <div>
                                <label className="text-sm font-medium text-muted-foreground mb-2 block">
                                    Date From
                                </label>
                                <div className="relative">
                                    <Calendar className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground pointer-events-none" />
                                    <Input
                                        type="date"
                                        value={dateFrom}
                                        onChange={handleDateFromChange}
                                        className="pl-9"
                                    />
                                </div>
                            </div>

                            {/* Date To */}
                            <div>
                                <label className="text-sm font-medium text-muted-foreground mb-2 block">
                                    Date To
                                </label>
                                <div className="relative">
                                    <Calendar className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground pointer-events-none" />
                                    <Input
                                        type="date"
                                        value={dateTo}
                                        onChange={handleDateToChange}
                                        className="pl-9"
                                    />
                                </div>
                            </div>
                        </div>

                        {hasActiveFilters && (
                            <div className="mt-4 flex justify-end">
                                <Button variant="ghost" size="sm" onClick={clearFilters}>
                                    Clear Filters
                                </Button>
                            </div>
                        )}
                    </CardContent>
                </Card>

                {/* Activities Table */}
                <Card>
                    <CardContent className="p-6">
                        <div className="mb-4">
                            <h2 className="text-lg font-semibold">Activities</h2>
                            <p className="text-sm text-muted-foreground">
                                Showing {activities.data.length} of {activities.total.toLocaleString()} activities
                            </p>
                        </div>

                        <div className="rounded-md border">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead className="w-[80px]">ID</TableHead>
                                        <TableHead>User</TableHead>
                                        <TableHead>Description</TableHead>
                                        <TableHead>Log Type</TableHead>
                                        <TableHead>Date</TableHead>
                                        <TableHead className="w-[80px]">Actions</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {activities.data.length === 0 ? (
                                        <TableRow>
                                            <TableCell colSpan={6} className="text-center py-8 text-muted-foreground">
                                                No activities found.
                                            </TableCell>
                                        </TableRow>
                                    ) : (
                                        activities.data.map((activity) => (
                                            <TableRow key={activity.id}>
                                                <TableCell className="font-mono text-sm text-muted-foreground">
                                                    #{activity.id}
                                                </TableCell>
                                                <TableCell>
                                                    <div>
                                                        <div className="font-medium">
                                                            {activity.user_name || 'System'}
                                                        </div>
                                                        {activity.user_email && (
                                                            <div className="text-sm text-muted-foreground">
                                                                {activity.user_email}
                                                            </div>
                                                        )}
                                                    </div>
                                                </TableCell>
                                                <TableCell>
                                                    <span className="text-sm">{activity.description}</span>
                                                </TableCell>
                                                <TableCell>
                                                    <Badge
                                                        variant="outline"
                                                        className={getLogTypeColor(activity.log_name)}
                                                    >
                                                        {activity.log_name}
                                                    </Badge>
                                                </TableCell>
                                                <TableCell className="text-sm text-muted-foreground">
                                                    {new Date(activity.created_at).toLocaleString('en-US', {
                                                        year: 'numeric',
                                                        month: '2-digit',
                                                        day: '2-digit',
                                                        hour: '2-digit',
                                                        minute: '2-digit',
                                                        second: '2-digit',
                                                    })}
                                                </TableCell>
                                                <TableCell>
                                                    <Button
                                                        variant="ghost"
                                                        size="sm"
                                                        onClick={() => setSelectedActivity(activity)}
                                                    >
                                                        <Eye className="h-4 w-4 text-blue-600" />
                                                        <span className="ml-1 text-blue-600">View</span>
                                                    </Button>
                                                </TableCell>
                                            </TableRow>
                                        ))
                                    )}
                                </TableBody>
                            </Table>
                        </div>

                        {/* Pagination */}
                        {activities.last_page > 1 && (
                            <div className="flex items-center justify-center gap-2 mt-6">
                                {activities.links.map((link, index) => (
                                    <Link
                                        key={index}
                                        href={link.url || '#'}
                                        className={`px-3 py-1 rounded text-sm ${
                                            link.active
                                                ? 'bg-primary text-primary-foreground'
                                                : link.url
                                                ? 'hover:bg-muted'
                                                : 'text-muted-foreground cursor-not-allowed'
                                        }`}
                                        dangerouslySetInnerHTML={{ __html: link.label }}
                                        preserveState
                                    />
                                ))}
                            </div>
                        )}
                    </CardContent>
                </Card>

                {/* Activity Detail Dialog */}
                <Dialog open={!!selectedActivity} onOpenChange={() => setSelectedActivity(null)}>
                    <DialogContent className="max-w-2xl">
                        <DialogHeader>
                            <DialogTitle>Activity Details</DialogTitle>
                            <DialogDescription>
                                Activity #{selectedActivity?.id}
                            </DialogDescription>
                        </DialogHeader>

                        {selectedActivity && (
                            <div className="space-y-4">
                                <div className="grid grid-cols-2 gap-4">
                                    <div>
                                        <label className="text-sm font-medium text-muted-foreground">User</label>
                                        <p className="mt-1">{selectedActivity.user_name || 'System'}</p>
                                        {selectedActivity.user_email && (
                                            <p className="text-sm text-muted-foreground">{selectedActivity.user_email}</p>
                                        )}
                                    </div>
                                    <div>
                                        <label className="text-sm font-medium text-muted-foreground">Log Type</label>
                                        <p className="mt-1">
                                            <Badge
                                                variant="outline"
                                                className={getLogTypeColor(selectedActivity.log_name)}
                                            >
                                                {selectedActivity.log_name}
                                            </Badge>
                                        </p>
                                    </div>
                                </div>

                                <div>
                                    <label className="text-sm font-medium text-muted-foreground">Description</label>
                                    <p className="mt-1">{selectedActivity.description}</p>
                                </div>

                                <div className="grid grid-cols-2 gap-4">
                                    <div>
                                        <label className="text-sm font-medium text-muted-foreground">IP Address</label>
                                        <p className="mt-1 font-mono text-sm">{selectedActivity.ip_address || '-'}</p>
                                    </div>
                                    <div>
                                        <label className="text-sm font-medium text-muted-foreground">Date</label>
                                        <p className="mt-1 text-sm">
                                            {new Date(selectedActivity.created_at).toLocaleString()}
                                        </p>
                                    </div>
                                </div>

                                {selectedActivity.user_agent && (
                                    <div>
                                        <label className="text-sm font-medium text-muted-foreground">User Agent</label>
                                        <p className="mt-1 text-sm text-muted-foreground break-all">
                                            {selectedActivity.user_agent}
                                        </p>
                                    </div>
                                )}

                                {selectedActivity.properties && Object.keys(selectedActivity.properties).length > 0 && (
                                    <div>
                                        <label className="text-sm font-medium text-muted-foreground">Properties</label>
                                        <pre className="mt-1 bg-muted p-3 rounded-md text-sm overflow-auto max-h-48">
                                            {JSON.stringify(selectedActivity.properties, null, 2)}
                                        </pre>
                                    </div>
                                )}
                            </div>
                        )}
                    </DialogContent>
                </Dialog>
            </div>
        </AppLayout>
    );
}
