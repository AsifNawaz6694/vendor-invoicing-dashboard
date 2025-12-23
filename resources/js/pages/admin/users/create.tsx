import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import { useState } from 'react';

interface Role {
    id: number;
    name: string;
    description?: string;
}

interface Props {
    roles: Role[];
}

export default function CreateUser({ roles }: Props) {
    const [processing, setProcessing] = useState(false);
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [formData, setFormData] = useState({
        name: '',
        email: '',
        role_id: '',
        is_admin: false,
        send_welcome_email: true,
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        setProcessing(true);

        router.post('/admin/users', formData, {
            onError: (errors) => setErrors(errors),
            onFinish: () => setProcessing(false),
        });
    };

    return (
        <AppLayout>
            <Head title="Create User" />

            <div className="space-y-6 p-6">
                <div className="flex items-center gap-4">
                    <Link href="/admin/users">
                        <Button variant="ghost" size="icon">
                            <ArrowLeft className="h-4 w-4" />
                        </Button>
                    </Link>
                    <div>
                        <h1 className="text-3xl font-bold tracking-tight">Create User</h1>
                        <p className="text-muted-foreground mt-2">
                            Add a new user to the system
                        </p>
                    </div>
                </div>

                <Card className="max-w-2xl">
                    <CardHeader>
                        <CardTitle>User Details</CardTitle>
                        <CardDescription>
                            Enter the user's information. They will receive an email to set their password.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={handleSubmit} className="space-y-6">
                            <div className="grid gap-2">
                                <Label htmlFor="name">Name</Label>
                                <Input
                                    id="name"
                                    value={formData.name}
                                    onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                                    placeholder="Full name"
                                    required
                                />
                                <InputError message={errors.name} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="email">Email</Label>
                                <Input
                                    id="email"
                                    type="email"
                                    value={formData.email}
                                    onChange={(e) => setFormData({ ...formData, email: e.target.value })}
                                    placeholder="email@example.com"
                                    required
                                />
                                <InputError message={errors.email} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="role">Role</Label>
                                <Select
                                    value={formData.role_id}
                                    onValueChange={(value) => setFormData({ ...formData, role_id: value })}
                                    required
                                >
                                    <SelectTrigger id="role">
                                        <SelectValue placeholder="Select a role" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {roles.map((role) => (
                                            <SelectItem key={role.id} value={role.id.toString()}>
                                                <div>
                                                    <div className="font-medium">{role.name}</div>
                                                    {role.description && (
                                                        <div className="text-xs text-muted-foreground">
                                                            {role.description}
                                                        </div>
                                                    )}
                                                </div>
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <InputError message={errors.role_id} />
                            </div>

                            <div className="flex items-center space-x-3">
                                <Checkbox
                                    id="is_admin"
                                    checked={formData.is_admin}
                                    onCheckedChange={(checked) =>
                                        setFormData({ ...formData, is_admin: checked as boolean })
                                    }
                                />
                                <div className="space-y-1">
                                    <Label htmlFor="is_admin">Administrator</Label>
                                    <p className="text-sm text-muted-foreground">
                                        Grant admin privileges to manage users and settings
                                    </p>
                                </div>
                            </div>

                            <div className="flex items-center space-x-3">
                                <Checkbox
                                    id="send_welcome_email"
                                    checked={formData.send_welcome_email}
                                    onCheckedChange={(checked) =>
                                        setFormData({ ...formData, send_welcome_email: checked as boolean })
                                    }
                                />
                                <div className="space-y-1">
                                    <Label htmlFor="send_welcome_email">Send Welcome Email</Label>
                                    <p className="text-sm text-muted-foreground">
                                        Send an email with instructions to set up their password
                                    </p>
                                </div>
                            </div>

                            <div className="flex gap-4">
                                <Button type="submit" disabled={processing}>
                                    {processing && <Spinner />}
                                    Create User
                                </Button>
                                <Link href="/admin/users">
                                    <Button type="button" variant="outline">
                                        Cancel
                                    </Button>
                                </Link>
                            </div>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
