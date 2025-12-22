import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import AppLayout from '@/layouts/app-layout';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { ArrowLeft, Key, Mail, Trash2 } from 'lucide-react';
import { useState } from 'react';

interface User {
    id: number;
    name: string;
    email: string;
    is_admin: boolean;
    is_active: boolean;
    two_factor_method: string | null;
    google_id: string | null;
    email_verified_at: string | null;
    created_at: string;
    creator?: { id: number; name: string } | null;
}

interface EditUserProps {
    user: User;
}

export default function EditUser({ user }: EditUserProps) {
    const [processing, setProcessing] = useState(false);
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [formData, setFormData] = useState({
        name: user.name,
        email: user.email,
        is_admin: user.is_admin,
    });
    const { flash } = usePage().props as any;
    const { auth } = usePage().props as any;

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        setProcessing(true);

        router.put(`/admin/users/${user.id}`, formData, {
            onError: (errors) => setErrors(errors),
            onFinish: () => setProcessing(false),
        });
    };

    const handleResetPassword = () => {
        if (confirm('Are you sure you want to reset this user\'s password? They will receive an email to set a new password.')) {
            router.post(`/admin/users/${user.id}/reset-password`);
        }
    };

    const handleResendWelcome = () => {
        router.post(`/admin/users/${user.id}/resend-welcome`);
    };

    const handleToggleStatus = () => {
        if (confirm(`Are you sure you want to ${user.is_active ? 'deactivate' : 'activate'} this user?`)) {
            router.post(`/admin/users/${user.id}/toggle-status`);
        }
    };

    const handleDelete = () => {
        if (confirm('Are you sure you want to delete this user? This action cannot be undone.')) {
            router.delete(`/admin/users/${user.id}`);
        }
    };

    const isCurrentUser = auth?.user?.id === user.id;

    return (
        <AppLayout>
            <Head title={`Edit User - ${user.name}`} />

            <div className="space-y-6 p-6">
                <div className="flex items-center gap-4">
                    <Link href="/admin/users">
                        <Button variant="ghost" size="icon">
                            <ArrowLeft className="h-4 w-4" />
                        </Button>
                    </Link>
                    <div className="flex-1">
                        <h1 className="text-2xl font-semibold">Edit User</h1>
                        <p className="text-sm text-muted-foreground">
                            Update user information and settings
                        </p>
                    </div>
                    <Badge variant={user.is_active ? 'default' : 'secondary'}>
                        {user.is_active ? 'Active' : 'Inactive'}
                    </Badge>
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

                <div className="grid gap-6 lg:grid-cols-2">
                    <Card>
                        <CardHeader>
                            <CardTitle>User Details</CardTitle>
                            <CardDescription>
                                Basic information about the user
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

                                <div className="flex items-center space-x-3">
                                    <Checkbox
                                        id="is_admin"
                                        checked={formData.is_admin}
                                        onCheckedChange={(checked) =>
                                            setFormData({ ...formData, is_admin: checked as boolean })
                                        }
                                        disabled={isCurrentUser}
                                    />
                                    <div className="space-y-1">
                                        <Label htmlFor="is_admin">Administrator</Label>
                                        <p className="text-sm text-muted-foreground">
                                            {isCurrentUser
                                                ? 'You cannot change your own admin status'
                                                : 'Grant admin privileges to manage users and settings'}
                                        </p>
                                    </div>
                                </div>

                                <Button type="submit" disabled={processing}>
                                    {processing && <Spinner />}
                                    Save Changes
                                </Button>
                            </form>
                        </CardContent>
                    </Card>

                    <div className="space-y-6">
                        <Card>
                            <CardHeader>
                                <CardTitle>Security Status</CardTitle>
                                <CardDescription>
                                    Current security settings for this user
                                </CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div className="flex items-center justify-between">
                                    <span className="text-sm font-medium">Two-Factor Authentication</span>
                                    {user.two_factor_method ? (
                                        <Badge variant="outline" className="capitalize">
                                            {user.two_factor_method}
                                        </Badge>
                                    ) : (
                                        <Badge variant="secondary">Not enabled</Badge>
                                    )}
                                </div>
                                <div className="flex items-center justify-between">
                                    <span className="text-sm font-medium">Email Verified</span>
                                    {user.email_verified_at ? (
                                        <Badge variant="outline">Verified</Badge>
                                    ) : (
                                        <Badge variant="secondary">Not verified</Badge>
                                    )}
                                </div>
                                <div className="flex items-center justify-between">
                                    <span className="text-sm font-medium">Google Account</span>
                                    {user.google_id ? (
                                        <Badge variant="outline">Linked</Badge>
                                    ) : (
                                        <Badge variant="secondary">Not linked</Badge>
                                    )}
                                </div>
                                {user.creator && (
                                    <div className="flex items-center justify-between">
                                        <span className="text-sm font-medium">Created By</span>
                                        <span className="text-sm text-muted-foreground">{user.creator.name}</span>
                                    </div>
                                )}
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <CardTitle>Actions</CardTitle>
                                <CardDescription>
                                    Manage user account status and access
                                </CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-3">
                                <Button
                                    variant="outline"
                                    className="w-full justify-start"
                                    onClick={handleResetPassword}
                                >
                                    <Key className="mr-2 h-4 w-4" />
                                    Reset Password
                                </Button>
                                <Button
                                    variant="outline"
                                    className="w-full justify-start"
                                    onClick={handleResendWelcome}
                                >
                                    <Mail className="mr-2 h-4 w-4" />
                                    Resend Welcome Email
                                </Button>
                                {!isCurrentUser && (
                                    <>
                                        <Button
                                            variant="outline"
                                            className="w-full justify-start"
                                            onClick={handleToggleStatus}
                                        >
                                            {user.is_active ? 'Deactivate User' : 'Activate User'}
                                        </Button>
                                        <Button
                                            variant="destructive"
                                            className="w-full justify-start"
                                            onClick={handleDelete}
                                        >
                                            <Trash2 className="mr-2 h-4 w-4" />
                                            Delete User
                                        </Button>
                                    </>
                                )}
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
