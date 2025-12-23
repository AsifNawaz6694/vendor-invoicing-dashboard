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
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Badge } from '@/components/ui/badge';
import AppLayout from '@/layouts/app-layout';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { MoreHorizontal, Plus, Search, UserCheck, UserX, Mail, Key, Trash2, Pencil, Download, Filter } from 'lucide-react';
import { useState } from 'react';

interface User {
    id: number;
    name: string;
    email: string;
    is_admin: boolean;
    is_active: boolean;
    two_factor_method: string | null;
    email_verified_at: string | null;
    created_at: string;
    creator?: { id: number; name: string } | null;
    role?: { id: number; name: string } | null;
}

interface UsersIndexProps {
    users: {
        data: User[];
        links: any[];
        current_page: number;
        last_page: number;
    };
    filters: {
        search?: string;
        status?: string;
        admin?: string;
    };
}

export default function UsersIndex({ users, filters }: UsersIndexProps) {
    const [search, setSearch] = useState(filters.search || '');
    const [statusFilter, setStatusFilter] = useState(filters.status || 'all');
    const [adminFilter, setAdminFilter] = useState(filters.admin || 'all');
    const [twoFaFilter, setTwoFaFilter] = useState(filters['2fa'] || 'all');
    const [roleFilter, setRoleFilter] = useState(filters.role || 'all');
    const { flash } = usePage().props as any;

    const handleSearch = (e: React.FormEvent) => {
        e.preventDefault();
        applyFilters();
    };

    const applyFilters = () => {
        const params: any = {};
        if (search) params.search = search;
        if (statusFilter && statusFilter !== 'all') params.status = statusFilter;
        if (adminFilter && adminFilter !== 'all') params.admin = adminFilter;
        if (twoFaFilter && twoFaFilter !== 'all') params['2fa'] = twoFaFilter;
        if (roleFilter && roleFilter !== 'all') params.role = roleFilter;

        router.get('/admin/users', params, { preserveState: true });
    };

    const handleExport = () => {
        const params = new URLSearchParams();
        if (search) params.append('search', search);
        if (statusFilter && statusFilter !== 'all') params.append('status', statusFilter);
        if (adminFilter && adminFilter !== 'all') params.append('admin', adminFilter);
        if (twoFaFilter && twoFaFilter !== 'all') params.append('2fa', twoFaFilter);
        if (roleFilter && roleFilter !== 'all') params.append('role', roleFilter);

        window.location.href = `/admin/users-export?${params.toString()}`;
    };

    const clearFilters = () => {
        setSearch('');
        setStatusFilter('all');
        setAdminFilter('all');
        setTwoFaFilter('all');
        setRoleFilter('all');
        router.get('/admin/users', {}, { preserveState: true });
    };

    const handleToggleStatus = (user: User) => {
        if (confirm(`Are you sure you want to ${user.is_active ? 'deactivate' : 'activate'} this user?`)) {
            router.post(`/admin/users/${user.id}/toggle-status`);
        }
    };

    const handleResetPassword = (user: User) => {
        if (confirm('Are you sure you want to reset this user\'s password? They will receive an email to set a new password.')) {
            router.post(`/admin/users/${user.id}/reset-password`);
        }
    };

    const handleResendWelcome = (user: User) => {
        router.post(`/admin/users/${user.id}/resend-welcome`);
    };

    const handleDelete = (user: User) => {
        if (confirm('Are you sure you want to delete this user? This action cannot be undone.')) {
            router.delete(`/admin/users/${user.id}`);
        }
    };

    return (
        <AppLayout>
            <Head title="User Management" />

            <div className="space-y-6 p-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-3xl font-bold tracking-tight">User Management</h1>
                        <p className="text-muted-foreground mt-2">
                            Manage user accounts and permissions
                        </p>
                    </div>
                    <div className="flex gap-2">
                        <Button variant="outline" onClick={handleExport}>
                            <Download className="mr-2 h-4 w-4" />
                            Export CSV
                        </Button>
                        <Link href="/admin/users/create">
                            <Button>
                                <Plus className="mr-2 h-4 w-4" />
                                Add User
                            </Button>
                        </Link>
                    </div>
                </div>

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

                <div className="space-y-4">
                    <div className="flex items-center gap-4">
                        <form onSubmit={handleSearch} className="flex-1">
                            <div className="relative max-w-sm">
                                <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                                <Input
                                    placeholder="Search users..."
                                    value={search}
                                    onChange={(e) => setSearch(e.target.value)}
                                    className="pl-9"
                                />
                            </div>
                        </form>

                        <Button type="button" variant="outline" onClick={applyFilters}>
                            <Filter className="mr-2 h-4 w-4" />
                            Apply Filters
                        </Button>

                        {(search || statusFilter !== 'all' || adminFilter !== 'all' || twoFaFilter !== 'all' || roleFilter !== 'all') && (
                            <Button type="button" variant="ghost" onClick={clearFilters}>
                                Clear All
                            </Button>
                        )}
                    </div>

                    <div className="flex items-center gap-3">
                        <Select value={statusFilter} onValueChange={setStatusFilter}>
                            <SelectTrigger className="w-[150px]">
                                <SelectValue placeholder="All Status" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">All Status</SelectItem>
                                <SelectItem value="active">Active</SelectItem>
                                <SelectItem value="inactive">Inactive</SelectItem>
                            </SelectContent>
                        </Select>

                        <Select value={adminFilter} onValueChange={setAdminFilter}>
                            <SelectTrigger className="w-[150px]">
                                <SelectValue placeholder="All Users" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">All Users</SelectItem>
                                <SelectItem value="yes">Admin Only</SelectItem>
                                <SelectItem value="no">Non-Admin</SelectItem>
                            </SelectContent>
                        </Select>

                        <Select value={twoFaFilter} onValueChange={setTwoFaFilter}>
                            <SelectTrigger className="w-[150px]">
                                <SelectValue placeholder="All 2FA" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">All 2FA</SelectItem>
                                <SelectItem value="enabled">2FA Enabled</SelectItem>
                                <SelectItem value="disabled">2FA Disabled</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                </div>

                <div className="rounded-md border">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Name</TableHead>
                                <TableHead>Email</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead>2FA</TableHead>
                                <TableHead>Role</TableHead>
                                <TableHead>Created</TableHead>
                                <TableHead className="w-[50px]"></TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {users.data.length === 0 ? (
                                <TableRow>
                                    <TableCell colSpan={7} className="text-center py-8 text-muted-foreground">
                                        No users found.
                                    </TableCell>
                                </TableRow>
                            ) : (
                                users.data.map((user) => (
                                    <TableRow key={user.id}>
                                        <TableCell className="font-medium">{user.name}</TableCell>
                                        <TableCell>{user.email}</TableCell>
                                        <TableCell>
                                            <Badge variant={user.is_active ? 'default' : 'secondary'}>
                                                {user.is_active ? 'Active' : 'Inactive'}
                                            </Badge>
                                        </TableCell>
                                        <TableCell>
                                            {user.two_factor_method ? (
                                                <Badge variant="outline" className="capitalize">
                                                    {user.two_factor_method}
                                                </Badge>
                                            ) : (
                                                <span className="text-muted-foreground text-sm">Not set</span>
                                            )}
                                        </TableCell>
                                        <TableCell>
                                            {user.role ? (
                                                <Badge variant={user.is_admin ? 'destructive' : 'outline'}>
                                                    {user.role.name}
                                                </Badge>
                                            ) : (
                                                <span className="text-muted-foreground text-sm">No role</span>
                                            )}
                                        </TableCell>
                                        <TableCell className="text-sm text-muted-foreground">
                                            {new Date(user.created_at).toLocaleDateString()}
                                        </TableCell>
                                        <TableCell>
                                            <DropdownMenu>
                                                <DropdownMenuTrigger asChild>
                                                    <Button variant="ghost" size="icon">
                                                        <MoreHorizontal className="h-4 w-4" />
                                                    </Button>
                                                </DropdownMenuTrigger>
                                                <DropdownMenuContent align="end">
                                                    <DropdownMenuItem asChild>
                                                        <Link href={`/admin/users/${user.id}/edit`}>
                                                            <Pencil className="mr-2 h-4 w-4" />
                                                            Edit
                                                        </Link>
                                                    </DropdownMenuItem>
                                                    <DropdownMenuItem onClick={() => handleToggleStatus(user)}>
                                                        {user.is_active ? (
                                                            <>
                                                                <UserX className="mr-2 h-4 w-4" />
                                                                Deactivate
                                                            </>
                                                        ) : (
                                                            <>
                                                                <UserCheck className="mr-2 h-4 w-4" />
                                                                Activate
                                                            </>
                                                        )}
                                                    </DropdownMenuItem>
                                                    <DropdownMenuSeparator />
                                                    <DropdownMenuItem onClick={() => handleResetPassword(user)}>
                                                        <Key className="mr-2 h-4 w-4" />
                                                        Reset Password
                                                    </DropdownMenuItem>
                                                    <DropdownMenuItem onClick={() => handleResendWelcome(user)}>
                                                        <Mail className="mr-2 h-4 w-4" />
                                                        Resend Welcome Email
                                                    </DropdownMenuItem>
                                                    <DropdownMenuSeparator />
                                                    <DropdownMenuItem
                                                        onClick={() => handleDelete(user)}
                                                        className="text-red-600"
                                                    >
                                                        <Trash2 className="mr-2 h-4 w-4" />
                                                        Delete
                                                    </DropdownMenuItem>
                                                </DropdownMenuContent>
                                            </DropdownMenu>
                                        </TableCell>
                                    </TableRow>
                                ))
                            )}
                        </TableBody>
                    </Table>
                </div>

                {/* Pagination */}
                {users.last_page > 1 && (
                    <div className="flex items-center justify-center gap-2">
                        {users.links.map((link, index) => (
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
                            />
                        ))}
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
