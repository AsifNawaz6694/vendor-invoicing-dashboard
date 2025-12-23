import AppLayout from '@/layouts/app-layout';
import { Head, Link, router } from '@inertiajs/react';
import { Shield, Plus, Edit, Trash2, Key, Download, Filter, Search } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
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
import { Badge } from '@/components/ui/badge';
import { useState } from 'react';

interface Role {
    id: number;
    name: string;
    slug: string;
    description: string;
    is_system: boolean;
    users_count: number;
    permissions_count: number;
    created_at: string;
    updated_at: string;
}

interface Props {
    roles: {
        data: Role[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
}

export default function RolesIndex({ roles }: Props) {
    const [search, setSearch] = useState('');
    const [systemFilter, setSystemFilter] = useState('all');

    const breadcrumbs = [
        { name: 'Dashboard', href: '/dashboard' },
        { name: 'Roles', href: '/admin/roles' },
    ];

    const handleDelete = (roleId: number) => {
        if (confirm('Are you sure you want to delete this role?')) {
            router.delete(`/admin/roles/${roleId}`, {
                preserveScroll: true,
                onSuccess: () => {
                    router.reload();
                },
            });
        }
    };

    const handleSearch = (e: React.FormEvent) => {
        e.preventDefault();
        applyFilters();
    };

    const applyFilters = () => {
        const params: any = {};
        if (search) params.search = search;
        if (systemFilter && systemFilter !== 'all') params.is_system = systemFilter;

        router.get('/admin/roles', params, { preserveState: true });
    };

    const handleExport = () => {
        const params = new URLSearchParams();
        if (search) params.append('search', search);
        if (systemFilter && systemFilter !== 'all') params.append('is_system', systemFilter);

        window.location.href = `/admin/roles-export?${params.toString()}`;
    };

    const clearFilters = () => {
        setSearch('');
        setSystemFilter('all');
        router.get('/admin/roles', {}, { preserveState: true });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Role Management" />

            <div className="space-y-6 p-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-3xl font-bold tracking-tight">
                            Role Management
                        </h1>
                        <p className="text-muted-foreground mt-2">
                            Manage system roles and their permissions
                        </p>
                    </div>
                    <div className="flex gap-2">
                        <Button variant="outline" onClick={handleExport}>
                            <Download className="mr-2 h-4 w-4" />
                            Export CSV
                        </Button>
                        <Button asChild>
                            <Link href="/admin/roles/create">
                                <Plus className="mr-2 h-4 w-4" />
                                Create Role
                            </Link>
                        </Button>
                    </div>
                </div>

                <div className="space-y-4">
                    <div className="flex items-center gap-4">
                        <form onSubmit={handleSearch} className="flex-1">
                            <div className="relative max-w-sm">
                                <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                                <Input
                                    placeholder="Search roles..."
                                    value={search}
                                    onChange={(e) => setSearch(e.target.value)}
                                    className="pl-9"
                                />
                            </div>
                        </form>

                        <Select value={systemFilter} onValueChange={setSystemFilter}>
                            <SelectTrigger className="w-[150px]">
                                <SelectValue placeholder="All Roles" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">All Roles</SelectItem>
                                <SelectItem value="yes">System Only</SelectItem>
                                <SelectItem value="no">Custom Only</SelectItem>
                            </SelectContent>
                        </Select>

                        <Button type="button" variant="outline" onClick={applyFilters}>
                            <Filter className="mr-2 h-4 w-4" />
                            Apply Filters
                        </Button>

                        {(search || systemFilter !== 'all') && (
                            <Button type="button" variant="ghost" onClick={clearFilters}>
                                Clear All
                            </Button>
                        )}
                    </div>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>All Roles</CardTitle>
                        <CardDescription>
                            A list of all roles in the system including their
                            permissions and user count.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Name</TableHead>
                                    <TableHead>Slug</TableHead>
                                    <TableHead>Description</TableHead>
                                    <TableHead>Users</TableHead>
                                    <TableHead>Permissions</TableHead>
                                    <TableHead>Type</TableHead>
                                    <TableHead className="text-right">
                                        Actions
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {roles.data.length === 0 ? (
                                    <TableRow>
                                        <TableCell
                                            colSpan={7}
                                            className="text-center h-24 text-muted-foreground"
                                        >
                                            No roles found. Create your first
                                            role to get started.
                                        </TableCell>
                                    </TableRow>
                                ) : (
                                    roles.data.map((role) => (
                                        <TableRow key={role.id}>
                                            <TableCell className="font-medium">
                                                <div className="flex items-center">
                                                    <Shield className="mr-2 h-4 w-4 text-primary" />
                                                    {role.name}
                                                </div>
                                            </TableCell>
                                            <TableCell>
                                                <code className="bg-muted px-2 py-1 rounded text-sm">
                                                    {role.slug}
                                                </code>
                                            </TableCell>
                                            <TableCell className="max-w-md truncate">
                                                {role.description || '-'}
                                            </TableCell>
                                            <TableCell>
                                                <Badge variant="secondary">
                                                    {role.users_count} users
                                                </Badge>
                                            </TableCell>
                                            <TableCell>
                                                <Badge variant="outline">
                                                    {role.permissions_count}{' '}
                                                    permissions
                                                </Badge>
                                            </TableCell>
                                            <TableCell>
                                                {role.is_system ? (
                                                    <Badge>System</Badge>
                                                ) : (
                                                    <Badge variant="secondary">
                                                        Custom
                                                    </Badge>
                                                )}
                                            </TableCell>
                                            <TableCell className="text-right">
                                                <div className="flex justify-end gap-2">
                                                    <Button
                                                        variant="ghost"
                                                        size="sm"
                                                        asChild
                                                    >
                                                        <Link
                                                            href={`/admin/roles/${role.id}/permissions`}
                                                        >
                                                            <Key className="h-4 w-4" />
                                                        </Link>
                                                    </Button>
                                                    <Button
                                                        variant="ghost"
                                                        size="sm"
                                                        asChild
                                                    >
                                                        <Link
                                                            href={`/admin/roles/${role.id}/edit`}
                                                        >
                                                            <Edit className="h-4 w-4" />
                                                        </Link>
                                                    </Button>
                                                    {!role.is_system && (
                                                        <Button
                                                            variant="ghost"
                                                            size="sm"
                                                            onClick={() =>
                                                                handleDelete(
                                                                    role.id,
                                                                )
                                                            }
                                                        >
                                                            <Trash2 className="h-4 w-4 text-destructive" />
                                                        </Button>
                                                    )}
                                                </div>
                                            </TableCell>
                                        </TableRow>
                                    ))
                                )}
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>

                {roles.last_page > 1 && (
                    <div className="flex items-center justify-between">
                        <p className="text-sm text-muted-foreground">
                            Showing {(roles.current_page - 1) * roles.per_page + 1} to{' '}
                            {Math.min(
                                roles.current_page * roles.per_page,
                                roles.total,
                            )}{' '}
                            of {roles.total} roles
                        </p>
                        <div className="flex gap-2">
                            {roles.current_page > 1 && (
                                <Button
                                    variant="outline"
                                    onClick={() =>
                                        router.get(
                                            `/admin/roles?page=${roles.current_page - 1}`,
                                        )
                                    }
                                >
                                    Previous
                                </Button>
                            )}
                            {roles.current_page < roles.last_page && (
                                <Button
                                    variant="outline"
                                    onClick={() =>
                                        router.get(
                                            `/admin/roles?page=${roles.current_page + 1}`,
                                        )
                                    }
                                >
                                    Next
                                </Button>
                            )}
                        </div>
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
