import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';
import { ArrowUpIcon, ArrowDownIcon, DollarSign, FileText, Clock, AlertCircle, Users, TrendingUp, Calendar, Building2 } from 'lucide-react';
import { LineChart, Line, BarChart, Bar, PieChart, Pie, Cell, XAxis, YAxis, CartesianGrid, Tooltip, Legend, ResponsiveContainer } from 'recharts';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
];

interface DashboardProps {
    metrics: any;
    userRole: string | null;
    userName?: string;
}

const COLORS = ['#10b981', '#f59e0b', '#ef4444', '#3b82f6'];

function formatCurrency(amount: number) {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
    }).format(amount);
}

function formatDate(date: string) {
    return new Date(date).toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
    });
}

function MetricCard({ title, value, icon: Icon, description, trend }: any) {
    return (
        <Card>
            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                <CardTitle className="text-sm font-medium">{title}</CardTitle>
                <Icon className="h-4 w-4 text-muted-foreground" />
            </CardHeader>
            <CardContent>
                <div className="text-2xl font-bold">{value}</div>
                {description && (
                    <p className="text-xs text-muted-foreground">
                        {trend && (
                            <span className={`inline-flex items-center ${trend > 0 ? 'text-green-600' : 'text-red-600'}`}>
                                {trend > 0 ? <ArrowUpIcon className="mr-1 h-3 w-3" /> : <ArrowDownIcon className="mr-1 h-3 w-3" />}
                                {Math.abs(trend)}%
                            </span>
                        )}
                        {description}
                    </p>
                )}
            </CardContent>
        </Card>
    );
}

function StatusPieChart({ data }: { data: any }) {
    const chartData = Object.entries(data).map(([status, info]: any) => ({
        name: status.charAt(0).toUpperCase() + status.slice(1),
        value: info.count,
        amount: info.amount,
    }));

    return (
        <ResponsiveContainer width="100%" height={300}>
            <PieChart>
                <Pie
                    data={chartData}
                    cx="50%"
                    cy="50%"
                    labelLine={false}
                    label={({ name, value }) => `${name}: ${value}`}
                    outerRadius={80}
                    fill="#8884d8"
                    dataKey="value"
                >
                    {chartData.map((entry, index) => (
                        <Cell key={`cell-${index}`} fill={COLORS[index % COLORS.length]} />
                    ))}
                </Pie>
                <Tooltip formatter={(value, name) => [`${value} invoices`, name]} />
            </PieChart>
        </ResponsiveContainer>
    );
}

function InvoiceTable({ invoices, title }: { invoices: any[]; title: string }) {
    if (!invoices || invoices.length === 0) {
        return (
            <Card>
                <CardHeader>
                    <CardTitle>{title}</CardTitle>
                </CardHeader>
                <CardContent>
                    <p className="text-sm text-muted-foreground">No invoices to display</p>
                </CardContent>
            </Card>
        );
    }

    return (
        <Card>
            <CardHeader>
                <CardTitle>{title}</CardTitle>
            </CardHeader>
            <CardContent>
                <div className="overflow-x-auto">
                    <table className="w-full text-sm">
                        <thead>
                            <tr className="border-b">
                                <th className="text-left py-2">Invoice #</th>
                                <th className="text-left py-2">Vendor</th>
                                <th className="text-right py-2">Amount</th>
                                <th className="text-left py-2">Due Date</th>
                                <th className="text-left py-2">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            {invoices.map((invoice) => (
                                <tr key={invoice.id} className="border-b">
                                    <td className="py-2">{invoice.invoice_number}</td>
                                    <td className="py-2">{invoice.vendor_name || 'N/A'}</td>
                                    <td className="text-right py-2">{formatCurrency(invoice.amount)}</td>
                                    <td className="py-2">{invoice.due_date ? formatDate(invoice.due_date) : 'N/A'}</td>
                                    <td className="py-2">
                                        <span
                                            className={`inline-flex items-center px-2 py-1 rounded-full text-xs font-medium ${
                                                invoice.status === 'paid'
                                                    ? 'bg-green-100 text-green-800'
                                                    : invoice.status === 'approved'
                                                    ? 'bg-blue-100 text-blue-800'
                                                    : invoice.status === 'rejected'
                                                    ? 'bg-red-100 text-red-800'
                                                    : 'bg-yellow-100 text-yellow-800'
                                            }`}
                                        >
                                            {invoice.status}
                                        </span>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </CardContent>
        </Card>
    );
}

function SuperAdminDashboard({ metrics }: { metrics: any }) {
    return (
        <div className="space-y-6">
            {/* Key Metrics */}
            <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                <MetricCard
                    title="Total Invoices"
                    value={metrics.totalInvoices}
                    icon={FileText}
                    description="All time"
                />
                <MetricCard
                    title="Total Users"
                    value={metrics.totalUsers}
                    icon={Users}
                    description={`${metrics.totalVendors} vendors`}
                />
                <MetricCard
                    title="Amount Due"
                    value={formatCurrency(metrics.totalAmountDue)}
                    icon={DollarSign}
                    description="Pending + Approved"
                />
                <MetricCard
                    title="Amount Paid"
                    value={formatCurrency(metrics.totalAmountPaid)}
                    icon={DollarSign}
                    description="All time"
                />
            </div>

            <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                <MetricCard
                    title="Pending Approvals"
                    value={metrics.pendingApprovals}
                    icon={Clock}
                    description="Requires action"
                />
                <MetricCard
                    title="Overdue Invoices"
                    value={metrics.overdueInvoices}
                    icon={AlertCircle}
                    description="Past due date"
                />
                <Card>
                    <CardHeader>
                        <CardTitle>Role Distribution</CardTitle>
                    </CardHeader>
                    <CardContent>
                        {metrics.roleDistribution?.map((role: any) => (
                            <div key={role.role} className="flex justify-between py-1">
                                <span className="text-sm">{role.role}:</span>
                                <span className="text-sm font-medium">{role.count}</span>
                            </div>
                        ))}
                    </CardContent>
                </Card>
            </div>

            {/* Charts Row */}
            <div className="grid gap-4 md:grid-cols-2">
                <Card>
                    <CardHeader>
                        <CardTitle>Invoice Status Distribution</CardTitle>
                        <CardDescription>Current status of all invoices</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <StatusPieChart data={metrics.statusBreakdown} />
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Invoice Uploads Over Time</CardTitle>
                        <CardDescription>Last 30 days</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <ResponsiveContainer width="100%" height={300}>
                            <LineChart data={metrics.invoiceUploadsOverTime}>
                                <CartesianGrid strokeDasharray="3 3" />
                                <XAxis dataKey="date" />
                                <YAxis />
                                <Tooltip />
                                <Line type="monotone" dataKey="count" stroke="#3b82f6" strokeWidth={2} />
                            </LineChart>
                        </ResponsiveContainer>
                    </CardContent>
                </Card>
            </div>

            {/* Company Breakdown */}
            <Card>
                <CardHeader>
                    <CardTitle>Top Vendors by Amount</CardTitle>
                    <CardDescription>Top 5 vendors by total invoice amount</CardDescription>
                </CardHeader>
                <CardContent>
                    <ResponsiveContainer width="100%" height={300}>
                        <BarChart data={metrics.companyBreakdown}>
                            <CartesianGrid strokeDasharray="3 3" />
                            <XAxis dataKey="vendor_name" />
                            <YAxis />
                            <Tooltip formatter={(value: any) => formatCurrency(value)} />
                            <Bar dataKey="total_amount" fill="#3b82f6" />
                        </BarChart>
                    </ResponsiveContainer>
                </CardContent>
            </Card>

            {/* Tables */}
            <div className="grid gap-4 md:grid-cols-2">
                <InvoiceTable invoices={metrics.upcomingDueInvoices} title="Upcoming Due Invoices" />
                <InvoiceTable invoices={metrics.recentlyPaidInvoices} title="Recently Paid Invoices" />
            </div>
        </div>
    );
}

function AccountantDashboard({ metrics }: { metrics: any }) {
    return (
        <div className="space-y-6">
            {/* Key Metrics */}
            <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                <MetricCard
                    title="Total Invoices"
                    value={metrics.totalInvoices}
                    icon={FileText}
                    description="All invoices"
                />
                <MetricCard
                    title="Pending Approvals"
                    value={metrics.pendingApprovalsCount || 0}
                    icon={Clock}
                    description="Requires review"
                />
                <MetricCard
                    title="Amount Due"
                    value={formatCurrency(metrics.totalAmountDue)}
                    icon={DollarSign}
                    description="To be paid"
                />
                <MetricCard
                    title="Amount Paid"
                    value={formatCurrency(metrics.totalAmountPaid)}
                    icon={DollarSign}
                    description="Completed"
                />
            </div>

            {/* Charts */}
            <div className="grid gap-4 md:grid-cols-2">
                <Card>
                    <CardHeader>
                        <CardTitle>Invoice Status</CardTitle>
                        <CardDescription>Current distribution</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <StatusPieChart data={metrics.statusBreakdown} />
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Invoice Trends</CardTitle>
                        <CardDescription>Last 30 days</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <ResponsiveContainer width="100%" height={300}>
                            <LineChart data={metrics.invoiceUploadsOverTime}>
                                <CartesianGrid strokeDasharray="3 3" />
                                <XAxis dataKey="date" />
                                <YAxis />
                                <Tooltip />
                                <Line type="monotone" dataKey="count" stroke="#10b981" strokeWidth={2} />
                            </LineChart>
                        </ResponsiveContainer>
                    </CardContent>
                </Card>
            </div>

            {/* Action Required Section */}
            {metrics.pendingApprovals && metrics.pendingApprovals.length > 0 && (
                <Card>
                    <CardHeader>
                        <CardTitle>Invoices Pending Approval</CardTitle>
                        <CardDescription>Requires your action</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b">
                                        <th className="text-left py-2">Invoice #</th>
                                        <th className="text-left py-2">Vendor</th>
                                        <th className="text-right py-2">Amount</th>
                                        <th className="text-left py-2">Submitted</th>
                                        <th className="text-left py-2">Due Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {metrics.pendingApprovals.map((invoice: any) => (
                                        <tr key={invoice.id} className="border-b">
                                            <td className="py-2">{invoice.invoice_number}</td>
                                            <td className="py-2">{invoice.vendor_name}</td>
                                            <td className="text-right py-2">{formatCurrency(invoice.amount)}</td>
                                            <td className="py-2">{formatDate(invoice.created_at)}</td>
                                            <td className="py-2">{formatDate(invoice.due_date)}</td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </CardContent>
                </Card>
            )}

            {/* Tables */}
            <div className="grid gap-4 md:grid-cols-2">
                <InvoiceTable invoices={metrics.overdueInvoices} title="Overdue Invoices" />
                <InvoiceTable invoices={metrics.recentlyPaidInvoices} title="Recently Paid" />
            </div>
        </div>
    );
}

function VendorDashboard({ metrics }: { metrics: any }) {
    return (
        <div className="space-y-6">
            {/* Key Metrics */}
            <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                <MetricCard
                    title="Total Invoices"
                    value={metrics.totalInvoices}
                    icon={FileText}
                    description="Submitted"
                />
                <MetricCard
                    title="Pending Review"
                    value={metrics.pendingCount || 0}
                    icon={Clock}
                    description="Awaiting approval"
                />
                <MetricCard
                    title="Amount Due"
                    value={formatCurrency(metrics.totalAmountDue)}
                    icon={DollarSign}
                    description="To be received"
                />
                <MetricCard
                    title="Amount Paid"
                    value={formatCurrency(metrics.totalAmountPaid)}
                    icon={DollarSign}
                    description="Received"
                />
            </div>

            {/* Status Overview */}
            <div className="grid gap-4 md:grid-cols-2">
                <Card>
                    <CardHeader>
                        <CardTitle>Invoice Status</CardTitle>
                        <CardDescription>Your invoices by status</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <StatusPieChart data={metrics.statusBreakdown} />
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Submission Trends</CardTitle>
                        <CardDescription>Last 30 days</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <ResponsiveContainer width="100%" height={300}>
                            <LineChart data={metrics.invoiceUploadsOverTime}>
                                <CartesianGrid strokeDasharray="3 3" />
                                <XAxis dataKey="date" />
                                <YAxis />
                                <Tooltip />
                                <Line type="monotone" dataKey="count" stroke="#f59e0b" strokeWidth={2} />
                            </LineChart>
                        </ResponsiveContainer>
                    </CardContent>
                </Card>
            </div>

            {/* Monthly Summary */}
            {metrics.monthlySummary && metrics.monthlySummary.length > 0 && (
                <Card>
                    <CardHeader>
                        <CardTitle>Monthly Summary</CardTitle>
                        <CardDescription>Last 6 months performance</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <ResponsiveContainer width="100%" height={300}>
                            <BarChart data={metrics.monthlySummary}>
                                <CartesianGrid strokeDasharray="3 3" />
                                <XAxis dataKey="month" />
                                <YAxis yAxisId="left" />
                                <YAxis yAxisId="right" orientation="right" />
                                <Tooltip formatter={(value: any, name: string) => name === 'amount' ? formatCurrency(value) : value} />
                                <Bar yAxisId="left" dataKey="count" fill="#3b82f6" name="Invoices" />
                                <Bar yAxisId="right" dataKey="amount" fill="#10b981" name="Amount" />
                            </BarChart>
                        </ResponsiveContainer>
                    </CardContent>
                </Card>
            )}

            {/* Recent Activity */}
            <div className="grid gap-4 md:grid-cols-2">
                {metrics.recentInvoices && metrics.recentInvoices.length > 0 && (
                    <Card>
                        <CardHeader>
                            <CardTitle>Recent Invoices</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="space-y-2">
                                {metrics.recentInvoices.map((invoice: any) => (
                                    <div key={invoice.id} className="flex justify-between items-center py-2 border-b">
                                        <div>
                                            <p className="font-medium text-sm">{invoice.invoice_number}</p>
                                            <p className="text-xs text-muted-foreground">{formatDate(invoice.created_at)}</p>
                                        </div>
                                        <div className="text-right">
                                            <p className="font-medium text-sm">{formatCurrency(invoice.amount)}</p>
                                            <span
                                                className={`inline-flex items-center px-2 py-1 rounded-full text-xs font-medium ${
                                                    invoice.status === 'paid'
                                                        ? 'bg-green-100 text-green-800'
                                                        : invoice.status === 'approved'
                                                        ? 'bg-blue-100 text-blue-800'
                                                        : invoice.status === 'rejected'
                                                        ? 'bg-red-100 text-red-800'
                                                        : 'bg-yellow-100 text-yellow-800'
                                                }`}
                                            >
                                                {invoice.status}
                                            </span>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </CardContent>
                    </Card>
                )}

                {metrics.overdueInvoices && metrics.overdueInvoices.length > 0 && (
                    <Card>
                        <CardHeader>
                            <CardTitle>Overdue Invoices</CardTitle>
                            <CardDescription>{metrics.overdueCount} invoices past due date</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div className="space-y-2">
                                {metrics.overdueInvoices.map((invoice: any) => (
                                    <div key={invoice.id} className="flex justify-between items-center py-2 border-b">
                                        <div>
                                            <p className="font-medium text-sm">{invoice.invoice_number}</p>
                                            <p className="text-xs text-red-600">Due: {formatDate(invoice.due_date)}</p>
                                        </div>
                                        <div className="text-right">
                                            <p className="font-medium text-sm">{formatCurrency(invoice.amount)}</p>
                                            <p className="text-xs text-muted-foreground">{invoice.status}</p>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </CardContent>
                    </Card>
                )}
            </div>
        </div>
    );
}

export default function Dashboard({ metrics, userRole, userName }: DashboardProps) {
    const getRoleTitle = () => {
        switch (userRole) {
            case 'super_admin':
                return 'Super Admin Dashboard';
            case 'accountant':
                return 'Accountant Dashboard';
            case 'vendor':
                return 'Vendor Dashboard';
            default:
                return 'Dashboard';
        }
    };

    const getRoleDescription = () => {
        switch (userRole) {
            case 'super_admin':
                return 'Complete system overview and management';
            case 'accountant':
                return 'Invoice processing and payment management';
            case 'vendor':
                return 'Your invoices and payment status';
            default:
                return 'Welcome to Vendor Invoicing Dashboard';
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={getRoleTitle()} />
            <div className="flex h-full flex-1 flex-col gap-4 p-4 md:p-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-3xl font-bold tracking-tight">{getRoleTitle()}</h1>
                        <p className="text-muted-foreground">{getRoleDescription()}</p>
                        {userName && <p className="text-sm text-muted-foreground mt-1">Welcome back, {userName}</p>}
                    </div>
                </div>

                {userRole === 'super_admin' && <SuperAdminDashboard metrics={metrics} />}
                {userRole === 'accountant' && <AccountantDashboard metrics={metrics} />}
                {userRole === 'vendor' && <VendorDashboard metrics={metrics} />}
                {!userRole && (
                    <Card>
                        <CardContent className="p-6">
                            <p className="text-muted-foreground">No role assigned. Please contact your administrator.</p>
                        </CardContent>
                    </Card>
                )}
            </div>
        </AppLayout>
    );
}