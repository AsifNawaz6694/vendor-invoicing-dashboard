# Dashboard Setup & Usage Guide

## ✅ Implementation Complete!

The Vendor Invoicing Dashboard has been successfully updated to support role-based dynamic content. The dashboard now adapts based on the logged-in user's role (Super Admin, Accountant, or Vendor).

## 🚀 Setup Instructions

### 1. Register the Service Provider (if not already done)
Add to `bootstrap/providers.php`:
```php
App\Providers\AuthorizationServiceProvider::class,
```

### 2. Run Database Migrations
```bash
php artisan migrate
```

### 3. Seed Initial Data
Run these commands in order:
```bash
# Create permissions
php artisan db:seed --class=PermissionSeeder

# Create roles with permissions
php artisan db:seed --class=RoleSeeder

# Create admin user
php artisan db:seed --class=AdminUserSeeder
```

### 4. Build Frontend Assets
```bash
npm install
npm run build
```

Or for development:
```bash
npm run dev
```

## 👤 Admin User Created

**Email:** asif@bargoventures.com
**Password:** 123456789
**Role:** Super Admin

## 📊 Dashboard Features by Role

### Super Admin Dashboard
- **Complete System Overview**
  - Total invoices across all vendors
  - Total users and role distribution
  - System-wide financial metrics
  - Pending approvals and overdue invoices
  - Top vendors by invoice amount
  - Invoice trends over time
  - Role distribution statistics

### Accountant Dashboard
- **Invoice Management Focus**
  - All invoices requiring approval
  - Overdue invoices needing attention
  - Payment processing metrics
  - Company-wide invoice status breakdown
  - Recently processed payments
  - Financial trends and reports

### Vendor Dashboard
- **Personal Invoice Tracking**
  - Own invoice submissions
  - Payment status tracking
  - Monthly performance summary
  - Pending and overdue invoices
  - Personal financial metrics
  - Invoice submission trends

## 🎯 Key Metrics Displayed

1. **Total Invoices** - Count of all invoices
2. **Invoice Status Breakdown** - Pending, In Process (Approved), Rejected, Paid
3. **Total Amount Due vs Paid** - Financial overview
4. **Upcoming Due Invoices** - Next 7-14 days
5. **Recently Paid Invoices** - Latest payments
6. **Invoice Uploads Over Time** - 30-day trend chart
7. **Company/Vendor Breakdown** - Top contributors
8. **Currency Breakdown** - Currently USD

## 🔧 Technical Implementation

### Backend Components
- **DashboardController** (`app/Http/Controllers/DashboardController.php`)
  - Role-based metrics calculation
  - Optimized queries with eager loading
  - Separate methods for each role

### Frontend Components
- **Dashboard TSX** (`resources/js/pages/dashboard.tsx`)
  - Responsive design maintained
  - Chart visualizations using Recharts
  - Role-specific UI components
  - Real-time data display

### Database Structure
- Extended `users` table with `role_id`
- `roles` table with soft deletes
- `permissions` table with module grouping
- `role_permission` pivot table
- `invoices` table for demo data

## 🔒 Security Features

1. **Role-Based Access Control**
   - Super Admin bypasses all checks
   - Vendors see only their own data
   - Accountants see all invoices

2. **Permission Caching**
   - Reduces database queries
   - Automatic cache invalidation on role changes

3. **Middleware Protection**
   - `CheckRole` middleware for role verification
   - `CheckPermission` for granular access control

## 📝 Creating Additional Users

### Create a Vendor User
```php
$user = User::create([
    'name' => 'Vendor Name',
    'email' => 'vendor@example.com',
    'password' => Hash::make('password'),
]);
$user->assignRole('vendor');
```

### Create an Accountant User
```php
$user = User::create([
    'name' => 'Accountant Name',
    'email' => 'accountant@example.com',
    'password' => Hash::make('password'),
]);
$user->assignRole('accountant');
```

## 🎨 Customization

### Adding New Metrics
Edit `DashboardController.php` methods:
- `getSuperAdminMetrics()` - For super admin metrics
- `getAccountantMetrics()` - For accountant metrics
- `getVendorMetrics()` - For vendor metrics

### Modifying Chart Colors
Edit the `COLORS` array in `dashboard.tsx`:
```javascript
const COLORS = ['#10b981', '#f59e0b', '#ef4444', '#3b82f6'];
```

### Adding New Status Types
Update the invoice status enum in the migration and corresponding UI components.

## 🐛 Troubleshooting

### Dashboard Shows "No role assigned"
- Ensure the user has a role assigned
- Run: `User::find($userId)->assignRole('role_slug');`

### Charts Not Displaying
- Ensure `recharts` is installed: `npm install recharts`
- Rebuild assets: `npm run build`

### Permission Denied Errors
- Clear permission cache: `php artisan cache:clear`
- Verify role has necessary permissions

## 📚 API Usage

### Get Current User's Dashboard Data
```javascript
// The dashboard automatically fetches data via Inertia props
// Data is available in the component as: metrics, userRole, userName
```

### Refresh Dashboard Data
```javascript
// Use Inertia's router to refresh
import { router } from '@inertiajs/react';
router.reload();
```

## ✨ Features Summary

✅ **Dynamic Role-Based Content** - Dashboard adapts to user role
✅ **Comprehensive Metrics** - All requested metrics implemented
✅ **Visual Charts** - Line, Bar, and Pie charts for data visualization
✅ **Responsive Design** - Maintains existing design system
✅ **Performance Optimized** - Eager loading and query optimization
✅ **Secure** - Role and permission-based access control
✅ **Admin User Created** - asif@bargoventures.com with password 123456789

The dashboard is now fully functional and ready for use!