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

interface Props {
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

export default function PermissionCreate({ modules }: Props) {
    const breadcrumbs = [
        { name: 'Dashboard', href: '/dashboard' },
        { name: 'Permissions', href: '/admin/permissions' },
        { name: 'Create Permission', href: '#' },
    ];

    const { data, setData, post, processing, errors } = useForm({
        name: '',
        slug: '',
        description: '',
        module: '',
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post('/admin/permissions');
    };

    const handleNameChange = (value: string) => {
        setData('name', value);
        // Auto-generate slug from name
        const slug = value
            .toLowerCase()
            .replace(/\s+/g, '.')
            .replace(/[^a-z0-9.]/g, '');
        setData('slug', slug);
    };

    const allModules = [...new Set([...commonModules, ...modules])];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Create Permission" />

            <div className="space-y-6 p-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-3xl font-bold tracking-tight">
                            Create New Permission
                        </h1>
                        <p className="text-muted-foreground mt-2">
                            Define a new permission for access control
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
                                <Label htmlFor="name">Permission Name *</Label>
                                <Input
                                    id="name"
                                    value={data.name}
                                    onChange={(e) => handleNameChange(e.target.value)}
                                    placeholder="e.g., View Reports"
                                    disabled={processing}
                                    required
                                />
                                {errors.name && (
                                    <p className="text-sm text-destructive">{errors.name}</p>
                                )}
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="slug">Slug</Label>
                                <Input
                                    id="slug"
                                    value={data.slug}
                                    onChange={(e) => setData('slug', e.target.value)}
                                    placeholder="e.g., reports.view"
                                    disabled={processing}
                                />
                                <p className="text-xs text-muted-foreground">
                                    The slug is auto-generated from the name. You can customize it if needed.
                                </p>
                                {errors.slug && (
                                    <p className="text-sm text-destructive">{errors.slug}</p>
                                )}
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="module">Module *</Label>
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
                                        <SelectItem value="Other">Other</SelectItem>
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
                                    placeholder="Describe what this permission allows users to do"
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
                            {processing ? 'Creating...' : 'Create Permission'}
                        </Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}