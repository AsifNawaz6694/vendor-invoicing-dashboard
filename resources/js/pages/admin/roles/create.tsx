import AppLayout from '@/layouts/app-layout';
import { Head, Link, useForm } from '@inertiajs/react';
import { Shield, Save, X, Key } from 'lucide-react';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Checkbox } from '@/components/ui/checkbox';
import { Badge } from '@/components/ui/badge';

interface Permission {
    id: number;
    name: string;
    slug: string;
    description: string;
    module: string;
}

interface Props {
    permissions: Permission[];
}

export default function RoleCreate({ permissions }: Props) {
    const breadcrumbs = [
        { name: 'Dashboard', href: '/dashboard' },
        { name: 'Roles', href: '/admin/roles' },
        { name: 'Create Role', href: '#' },
    ];

    const { data, setData, post, processing, errors } = useForm({
        name: '',
        description: '',
        permissions: [] as number[],
    });

    // Group permissions by module
    const groupedPermissions = permissions.reduce((acc, permission) => {
        const module = permission.module || 'Other';
        if (!acc[module]) acc[module] = [];
        acc[module].push(permission);
        return acc;
    }, {} as Record<string, Permission[]>);

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post('/admin/roles');
    };

    const togglePermission = (permissionId: number) => {
        setData('permissions',
            data.permissions.includes(permissionId)
                ? data.permissions.filter(id => id !== permissionId)
                : [...data.permissions, permissionId]
        );
    };

    const toggleAllInModule = (module: string) => {
        const modulePermissionIds = groupedPermissions[module].map(p => p.id);
        const allSelected = modulePermissionIds.every(id => data.permissions.includes(id));

        if (allSelected) {
            setData('permissions', data.permissions.filter(id => !modulePermissionIds.includes(id)));
        } else {
            setData('permissions', [...new Set([...data.permissions, ...modulePermissionIds])]);
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Create Role" />

            <div className="space-y-6 p-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-3xl font-bold tracking-tight">
                            Create New Role
                        </h1>
                        <p className="text-muted-foreground mt-2">
                            Define a new role with specific permissions
                        </p>
                    </div>
                    <Button variant="outline" asChild>
                        <Link href="/admin/roles">
                            <X className="mr-2 h-4 w-4" />
                            Cancel
                        </Link>
                    </Button>
                </div>

                <form onSubmit={handleSubmit} className="space-y-6">
                    <Card>
                        <CardHeader>
                            <CardTitle>Role Details</CardTitle>
                            <CardDescription>
                                Basic information about the role
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="space-y-2">
                                <Label htmlFor="name">Role Name *</Label>
                                <Input
                                    id="name"
                                    value={data.name}
                                    onChange={(e) => setData('name', e.target.value)}
                                    placeholder="e.g., Content Manager"
                                    disabled={processing}
                                    required
                                />
                                {errors.name && (
                                    <p className="text-sm text-destructive">{errors.name}</p>
                                )}
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="description">Description</Label>
                                <Textarea
                                    id="description"
                                    value={data.description}
                                    onChange={(e) => setData('description', e.target.value)}
                                    placeholder="Describe the purpose and responsibilities of this role"
                                    rows={3}
                                    disabled={processing}
                                />
                                {errors.description && (
                                    <p className="text-sm text-destructive">{errors.description}</p>
                                )}
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Permissions</CardTitle>
                            <CardDescription>
                                Select permissions to assign to this role
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div className="space-y-6">
                                {Object.entries(groupedPermissions).map(([module, modulePermissions]) => (
                                    <div key={module} className="space-y-3">
                                        <div className="flex items-center justify-between">
                                            <h3 className="font-semibold text-lg flex items-center gap-2">
                                                <Key className="h-4 w-4" />
                                                {module}
                                                <Badge variant="secondary">
                                                    {modulePermissions.filter(p => data.permissions.includes(p.id)).length}/{modulePermissions.length}
                                                </Badge>
                                            </h3>
                                            <Button
                                                type="button"
                                                variant="ghost"
                                                size="sm"
                                                onClick={() => toggleAllInModule(module)}
                                            >
                                                {modulePermissions.every(p => data.permissions.includes(p.id))
                                                    ? 'Deselect All'
                                                    : 'Select All'}
                                            </Button>
                                        </div>
                                        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3 pl-6">
                                            {modulePermissions.map((permission) => (
                                                <div
                                                    key={permission.id}
                                                    className="flex items-start space-x-2"
                                                >
                                                    <Checkbox
                                                        id={`permission-${permission.id}`}
                                                        checked={data.permissions.includes(permission.id)}
                                                        onCheckedChange={() => togglePermission(permission.id)}
                                                        disabled={processing}
                                                    />
                                                    <div className="space-y-1 leading-none">
                                                        <label
                                                            htmlFor={`permission-${permission.id}`}
                                                            className="text-sm font-medium cursor-pointer"
                                                        >
                                                            {permission.name}
                                                        </label>
                                                        {permission.description && (
                                                            <p className="text-xs text-muted-foreground">
                                                                {permission.description}
                                                            </p>
                                                        )}
                                                    </div>
                                                </div>
                                            ))}
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </CardContent>
                    </Card>

                    <div className="flex justify-end gap-2">
                        <Button variant="outline" asChild>
                            <Link href="/admin/roles">Cancel</Link>
                        </Button>
                        <Button type="submit" disabled={processing}>
                            <Save className="mr-2 h-4 w-4" />
                            {processing ? 'Creating...' : 'Create Role'}
                        </Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}