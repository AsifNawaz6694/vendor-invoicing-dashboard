import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import AuthLayout from '@/layouts/auth-layout';
import { Head, router } from '@inertiajs/react';
import { Mail, Smartphone } from 'lucide-react';
import { useState } from 'react';

interface TwoFactorSetupProps {
    defaultMethod: 'email' | 'totp';
}

export default function TwoFactorSetup({ defaultMethod }: TwoFactorSetupProps) {
    const [selectedMethod, setSelectedMethod] = useState<'email' | 'totp'>(defaultMethod);
    const [processing, setProcessing] = useState(false);

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        setProcessing(true);

        router.post('/auth/two-factor-setup', {
            method: selectedMethod,
        }, {
            onFinish: () => setProcessing(false),
        });
    };

    return (
        <AuthLayout
            title="Set Up Two-Factor Authentication"
            description="Protect your account with an additional layer of security"
        >
            <Head title="Set Up 2FA" />

            <form onSubmit={handleSubmit} className="flex flex-col gap-6">
                <div className="grid gap-4">
                    <Label className="text-base">Choose your preferred method</Label>

                    <Card
                        className={`cursor-pointer transition-colors ${
                            selectedMethod === 'email'
                                ? 'border-primary ring-2 ring-primary'
                                : 'hover:border-muted-foreground/50'
                        }`}
                        onClick={() => setSelectedMethod('email')}
                    >
                        <CardHeader className="pb-2">
                            <div className="flex items-center gap-3">
                                <div className="rounded-full bg-primary/10 p-2">
                                    <Mail className="h-5 w-5 text-primary" />
                                </div>
                                <div>
                                    <CardTitle className="text-base">Email Verification</CardTitle>
                                    <CardDescription>
                                        Receive a 6-digit code via email
                                    </CardDescription>
                                </div>
                            </div>
                        </CardHeader>
                        <CardContent className="pt-0">
                            <p className="text-sm text-muted-foreground">
                                A verification code will be sent to your email address each time you log in.
                                This is the simplest option and requires no additional apps.
                            </p>
                        </CardContent>
                    </Card>

                    <Card
                        className={`cursor-pointer transition-colors ${
                            selectedMethod === 'totp'
                                ? 'border-primary ring-2 ring-primary'
                                : 'hover:border-muted-foreground/50'
                        }`}
                        onClick={() => setSelectedMethod('totp')}
                    >
                        <CardHeader className="pb-2">
                            <div className="flex items-center gap-3">
                                <div className="rounded-full bg-primary/10 p-2">
                                    <Smartphone className="h-5 w-5 text-primary" />
                                </div>
                                <div>
                                    <CardTitle className="text-base">Authenticator App</CardTitle>
                                    <CardDescription>
                                        Use Google Authenticator or similar app
                                    </CardDescription>
                                </div>
                            </div>
                        </CardHeader>
                        <CardContent className="pt-0">
                            <p className="text-sm text-muted-foreground">
                                Use an authenticator app like Google Authenticator, Authy, or 1Password
                                to generate time-based codes. More secure but requires app setup.
                            </p>
                        </CardContent>
                    </Card>
                </div>

                <Button type="submit" className="w-full" disabled={processing}>
                    {processing && <Spinner />}
                    {selectedMethod === 'email' ? 'Enable Email 2FA' : 'Continue to App Setup'}
                </Button>

                <p className="text-center text-xs text-muted-foreground">
                    Two-factor authentication is required for all accounts to ensure security.
                </p>
            </form>
        </AuthLayout>
    );
}
