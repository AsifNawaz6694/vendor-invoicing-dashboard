import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import AuthLayout from '@/layouts/auth-layout';
import { Head, router, usePage } from '@inertiajs/react';
import { useState } from 'react';

interface PasswordSetupProps {
    token: string;
    email: string;
}

export default function PasswordSetup({ token, email }: PasswordSetupProps) {
    const [processing, setProcessing] = useState(false);
    const [errors, setErrors] = useState<Record<string, string>>({});
    const { flash } = usePage().props as any;

    const handleSubmit = (e: React.FormEvent<HTMLFormElement>) => {
        e.preventDefault();
        setProcessing(true);

        const formData = new FormData(e.currentTarget);

        router.post(`/password/setup/${token}`, {
            password: formData.get('password'),
            password_confirmation: formData.get('password_confirmation'),
        }, {
            onError: (errors) => setErrors(errors),
            onFinish: () => setProcessing(false),
        });
    };

    return (
        <AuthLayout
            title="Set Up Your Password"
            description="Create a secure password for your account"
        >
            <Head title="Set Up Password" />

            {flash?.error && (
                <div className="mb-4 rounded-md bg-red-50 p-3 text-center text-sm font-medium text-red-600">
                    {flash.error}
                </div>
            )}

            <form onSubmit={handleSubmit} className="flex flex-col gap-6">
                <div className="grid gap-6">
                    <div className="grid gap-2">
                        <Label htmlFor="email">Email address</Label>
                        <Input
                            id="email"
                            type="email"
                            value={email}
                            disabled
                            className="bg-muted"
                        />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="password">Password</Label>
                        <Input
                            id="password"
                            type="password"
                            name="password"
                            required
                            autoFocus
                            autoComplete="new-password"
                            placeholder="Enter your new password"
                        />
                        <InputError message={errors.password} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="password_confirmation">Confirm Password</Label>
                        <Input
                            id="password_confirmation"
                            type="password"
                            name="password_confirmation"
                            required
                            autoComplete="new-password"
                            placeholder="Confirm your new password"
                        />
                        <InputError message={errors.password_confirmation} />
                    </div>

                    <Button type="submit" className="w-full" disabled={processing}>
                        {processing && <Spinner />}
                        Set Password & Continue
                    </Button>
                </div>
            </form>

            <p className="mt-4 text-center text-xs text-muted-foreground">
                Your password must be at least 8 characters and include uppercase, lowercase, numbers, and special characters.
            </p>
        </AuthLayout>
    );
}
