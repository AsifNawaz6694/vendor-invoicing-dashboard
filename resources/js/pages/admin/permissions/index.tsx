import AppLayout from '@/layouts/app-layout';
import { Head, Link, router } from '@inertiajs/react';
import { Key, Plus, Edit, Trash2, Filter, Search, Download } from 'lucide-react';
import { Button } from '@/components/ui/button';
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
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import { useState } from 'react';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

interface Permission {
    id: number;
    name: string;
    slug: string;
    description: string;
    module: string;
    is_system: boolean;
    roles_count: number;
    created_at: string;
    updated_at: string;
}

interface Props {
    permissions: {
        data: Permission[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
    modules: string[];
}

export default function PermissionsIndex({ permissions, modules }: Props) {
    const [search, setSearch] = useState('');
    const [selectedModule, setSelectedModule] = useState('all');
    const [systemFilter, setSystemFilter] = useState('all');

    const breadcrumbs = [
        { name: 'Dashboard', href: '/dashboard' },
        { name: 'Permissions', href: '/admin/permissions' },
    ];

    const handleDelete = (permissionId: number) => {
        if (confirm('Are you sure you want to delete this permission?')) {
            router.delete(`/admin/permissions/${permissionId}`, {
                preserveScroll: true,
                onSuccess: () => {
                    router.reload();
                },
            });
        }
    };

    const handleSearch = (e: React.FormEvent) => {
        e.preventDefault();
        const params: any = {};
        if (search) params.search = search;
        if (selectedModule !== 'all') params.module = selectedModule;
        if (systemFilter && systemFilter !== 'all') params.is_system = systemFilter;

        router.get('/admin/permissions', params, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const handleReset = () => {
        setSearch('');
        setSelectedModule('all');
        setSystemFilter('all');
        router.get('/admin/permissions', {}, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const handleExport = () => {
        const params = new URLSearchParams();
        if (search) params.append('search', search);
        if (selectedModule !== 'all') params.append('module', selectedModule);
        if (systemFilter && systemFilter !== 'all') params.append('is_system', systemFilter);

        window.location.href = `/admin/permissions-export?${params.toString()}`;
    };

    // Get module color based on name
    const getModuleColor = (module: string) => {
        const colors: Record<string, string> = {
            'Role Management': 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200',
            'Permission Management': 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-200',
            'User Management': 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
            'Invoice Management': 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
            'Payment Management': 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
            'Reports & Analytics': 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200',
            'System Settings': 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
            'Audit Logs': 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200',
        };
        return colors[module] || 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200';
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Permission Management" />

            <div className="space-y-6 p-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-3xl font-bold tracking-tight">
                            Permission Management
                        </h1>
                        <p className="text-muted-foreground mt-2">
                            Manage system permissions and access controls
                        </p>
                    </div>
                    <div className="flex gap-2">
                        <Button variant="outline" onClick={handleExport}>
                            <Download className="mr-2 h-4 w-4" />
                            Export CSV
                        </Button>
                        <Button asChild>
                            <Link href="/admin/permissions/create">
                                <Plus className="mr-2 h-4 w-4" />
                                Create Permission
                            </Link>
                        </Button>
                    </div>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Filters</CardTitle>
                        <CardDescription>
                            Search and filter permissions
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={handleSearch} className="flex flex-col md:flex-row gap-4">
                            <div className="flex-1">
                                <div className="relative">
                                    <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 h-4 w-4" />
                                    <Input
                                        type="text"
                                        placeholder="Search permissions..."
                                        value={search}
                                        onChange={(e) => setSearch(e.target.value)}
                                        className="pl-10"
                                    />
                                </div>
                            </div>
                            <Select value={selectedModule} onValueChange={setSelectedModule}>
                                <SelectTrigger className="w-full md:w-[200px]">
                                    <SelectValue placeholder="Select module" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">All Modules</SelectItem>
                                    {modules.map((module) => (
                                        <SelectItem key={module} value={module}>
                                            {module}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <Select value={systemFilter} onValueChange={setSystemFilter}>
                                <SelectTrigger className="w-full md:w-[150px]">
                                    <SelectValue placeholder="All Permissions" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">All Permissions</SelectItem>
                                    <SelectItem value="yes">System Only</SelectItem>
                                    <SelectItem value="no">Custom Only</SelectItem>
                                </SelectContent>
                            </Select>
                            <div className="flex gap-2">
                                <Button type="submit">
                                    <Filter className="mr-2 h-4 w-4" />
                                    Apply
                                </Button>
                                <Button type="button" variant="outline" onClick={handleReset}>
                                    Reset
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>All Permissions</CardTitle>
                        <CardDescription>
                            A list of all permissions in the system
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div className="overflow-x-auto">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Name</TableHead>
                                        <TableHead>Module</TableHead>
                                        <TableHead>Slug</TableHead>
                                        <TableHead>Description</TableHead>
                                        <TableHead>Roles</TableHead>
                                        <TableHead>Type</TableHead>
                                        <TableHead className="text-right">
                                            Actions
                                        </TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {permissions.data.length === 0 ? (
                                        <TableRow>
                                            <TableCell
                                                colSpan={7}
                                                className="text-center h-24 text-muted-foreground"
                                            >
                                                No permissions found.
                                            </TableCell>
                                        </TableRow>
                                    ) : (
                                        permissions.data.map((permission) => (
                                            <TableRow key={permission.id}>
                                                <TableCell className="font-medium">
                                                    <div className="flex items-center">
                                                        <Key className="mr-2 h-4 w-4 text-primary" />
                                                        {permission.name}
                                                    </div>
                                                </TableCell>
                                                <TableCell>
                                                    <Badge
                                                        className={getModuleColor(permission.module)}
                                                        variant="secondary"
                                                    >
                                                        {permission.module}
                                                    </Badge>
                                                </TableCell>
                                                <TableCell>
                                                    <code className="bg-muted px-2 py-1 rounded text-sm">
                                                        {permission.slug}
                                                    </code>
                                                </TableCell>
                                                <TableCell className="max-w-md">
                                                    <span className="text-sm text-muted-foreground truncate block">
                                                        {permission.description || '-'}
                                                    </span>
                                                </TableCell>
                                                <TableCell>
                                                    <Badge variant="outline">
                                                        {permission.roles_count} roles
                                                    </Badge>
                                                </TableCell>
                                                <TableCell>
                                                    {permission.is_system ? (
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
                                                                href={`/admin/permissions/${permission.id}/edit`}
                                                            >
                                                                <Edit className="h-4 w-4" />
                                                            </Link>
                                                        </Button>
                                                        {!permission.is_system && (
                                                            <Button
                                                                variant="ghost"
                                                                size="sm"
                                                                onClick={() =>
                                                                    handleDelete(
                                                                        permission.id,
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
                        </div>
                    </CardContent>
                </Card>

                {permissions.last_page > 1 && (
                    <div className="flex items-center justify-between">
                        <p className="text-sm text-muted-foreground">
                            Showing{' '}
                            {(permissions.current_page - 1) *
                                permissions.per_page +
                                1}{' '}
                            to{' '}
                            {Math.min(
                                permissions.current_page * permissions.per_page,
                                permissions.total,
                            )}{' '}
                            of {permissions.total} permissions
                        </p>
                        <div className="flex gap-2">
                            {permissions.current_page > 1 && (
                                <Button
                                    variant="outline"
                                    onClick={() =>
                                        router.get(
                                            `/admin/permissions?page=${permissions.current_page - 1}`,
                                        )
                                    }
                                >
                                    Previous
                                </Button>
                            )}
                            {permissions.current_page <
                                permissions.last_page && (
                                <Button
                                    variant="outline"
                                    onClick={() =>
                                        router.get(
                                            `/admin/permissions?page=${permissions.current_page + 1}`,
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