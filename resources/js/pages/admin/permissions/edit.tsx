import AppLayout from '@/layouts/app-layout';
import { Head, Link, useForm } from '@inertiajs/react';
import { Key, Save, X } from 'lucide-react';
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
}

interface Props {
    permission: Permission;
    modules: string[];
}

const commonModules = [
    'Role Management',
    'Permission Management',
    'User Management',
    'Invoice Management',
    'Payment Management',
    'Reports & Analytics',
    'System Settings',
    'Audit Logs',
];

export default function PermissionEdit({ permission, modules }: Props) {
    const breadcrumbs = [
        { name: 'Dashboard', href: '/dashboard' },
        { name: 'Permissions', href: '/admin/permissions' },
        { name: 'Edit Permission', href: '#' },
    ];

    const { data, setData, put, processing, errors } = useForm({
        name: permission.name,
        description: permission.description || '',
        module: permission.module || '',
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        put(`/admin/permissions/${permission.id}`);
    };

    if (permission.is_system) {
        return (
            <AppLayout breadcrumbs={breadcrumbs}>
                <Head title={`Edit ${permission.name}`} />
                <div className="space-y-6 p-6">
                    <Card>
                        <CardHeader>
                            <CardTitle>System Permission</CardTitle>
                            <CardDescription>
                                System permissions cannot be edited for security reasons.
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <Button asChild>
                                <Link href="/admin/permissions">Back to Permissions</Link>
                            </Button>
                        </CardContent>
                    </Card>
                </div>
            </AppLayout>
        );
    }

    const allModules = [...new Set([...commonModules, ...modules])];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Edit ${permission.name}`} />

            <div className="space-y-6 p-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-3xl font-bold tracking-tight">
                            Edit Permission
                        </h1>
                        <p className="text-muted-foreground mt-2">
                            Update permission details
                        </p>
                    </div>
                    <Button variant="outline" asChild>
                        <Link href="/admin/permissions">
                            <X className="mr-2 h-4 w-4" />
                            Cancel
                        </Link>
                    </Button>
                </div>

                <form onSubmit={handleSubmit} className="space-y-6">
                    <Card>
                        <CardHeader>
                            <CardTitle>Permission Details</CardTitle>
                            <CardDescription>
                                Basic information about the permission
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="space-y-2">
                                <Label htmlFor="name">Permission Name</Label>
                                <Input
                                    id="name"
                                    value={data.name}
                                    onChange={(e) => setData('name', e.target.value)}
                                    placeholder="e.g., View Reports"
                                    disabled={processing}
                                />
                                {errors.name && (
                                    <p className="text-sm text-destructive">{errors.name}</p>
                                )}
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="slug">Slug</Label>
                                <Input
                                    id="slug"
                                    value={permission.slug}
                                    disabled
                                    className="bg-muted"
                                />
                                <p className="text-xs text-muted-foreground">
                                    The slug is automatically generated and cannot be changed.
                                </p>
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="module">Module</Label>
                                <Select value={data.module} onValueChange={(value) => setData('module', value)}>
                                    <SelectTrigger id="module">
                                        <SelectValue placeholder="Select a module" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {allModules.map((module) => (
                                            <SelectItem key={module} value={module}>
                                                {module}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                {errors.module && (
                                    <p className="text-sm text-destructive">{errors.module}</p>
                                )}
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="description">Description</Label>
                                <Textarea
                                    id="description"
                                    value={data.description}
                                    onChange={(e) => setData('description', e.target.value)}
                                    placeholder="Describe what this permission allows"
                                    rows={3}
                                    disabled={processing}
                                />
                                {errors.description && (
                                    <p className="text-sm text-destructive">{errors.description}</p>
                                )}
                            </div>
                        </CardContent>
                    </Card>

                    <div className="flex justify-end gap-2">
                        <Button variant="outline" asChild>
                            <Link href="/admin/permissions">Cancel</Link>
                        </Button>
                        <Button type="submit" disabled={processing}>
                            <Save className="mr-2 h-4 w-4" />
                            {processing ? 'Saving...' : 'Save Changes'}
                        </Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}