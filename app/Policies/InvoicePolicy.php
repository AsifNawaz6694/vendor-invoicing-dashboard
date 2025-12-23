<?php

namespace App\Policies;

use App\Models\Invoice;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class InvoicePolicy
{
    /**
     * Perform pre-authorization check.
     * Super admin can bypass all checks.
     */
    public function before(User $user, string $ability): ?bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return null;
    }

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // Check permission
        if (!$user->hasPermission('invoices.view')) {
            return false;
        }

        // Vendors can view their own invoices
        // Accountants can view all invoices
        return $user->isVendor() || $user->isAccountant();
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Invoice $invoice): bool
    {
        // Check permission
        if (!$user->hasPermission('invoices.view')) {
            return false;
        }

        // Accountants can view all invoices
        if ($user->isAccountant()) {
            return true;
        }

        // Vendors can only view their own invoices
        if ($user->isVendor()) {
            return $invoice->vendor_id === $user->id;
        }

        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Only vendors can create invoices
        return $user->isVendor() && $user->hasPermission('invoices.create');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Invoice $invoice): bool
    {
        // Check permission
        if (!$user->hasPermission('invoices.update')) {
            return false;
        }

        // Accountants can update any invoice
        if ($user->isAccountant()) {
            return true;
        }

        // Vendors can only update their own pending invoices
        if ($user->isVendor()) {
            return $invoice->vendor_id === $user->id && $invoice->isPending();
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Invoice $invoice): bool
    {
        // Check permission
        if (!$user->hasPermission('invoices.delete')) {
            return false;
        }

        // Accountants can delete any invoice
        if ($user->isAccountant()) {
            return true;
        }

        // Vendors can only delete their own pending invoices
        if ($user->isVendor()) {
            return $invoice->vendor_id === $user->id && $invoice->isPending();
        }

        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Invoice $invoice): bool
    {
        // Check permission
        if (!$user->hasPermission('invoices.restore')) {
            return false;
        }

        // Only accountants can restore invoices
        return $user->isAccountant();
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Invoice $invoice): bool
    {
        // Check permission
        if (!$user->hasPermission('invoices.force-delete')) {
            return false;
        }

        // Only accountants can permanently delete invoices
        return $user->isAccountant();
    }

    /**
     * Determine whether the user can approve the invoice.
     */
    public function approve(User $user, Invoice $invoice): bool
    {
        // Check permission
        if (!$user->hasPermission('invoices.approve')) {
            return false;
        }

        // Only accountants can approve invoices
        // And only pending invoices can be approved
        return $user->isAccountant() && $invoice->isPending();
    }

    /**
     * Determine whether the user can reject the invoice.
     */
    public function reject(User $user, Invoice $invoice): bool
    {
        // Check permission
        if (!$user->hasPermission('invoices.reject')) {
            return false;
        }

        // Only accountants can reject invoices
        // And only pending invoices can be rejected
        return $user->isAccountant() && $invoice->isPending();
    }

    /**
     * Determine whether the user can mark the invoice as paid.
     */
    public function markAsPaid(User $user, Invoice $invoice): bool
    {
        // Check permission
        if (!$user->hasPermission('invoices.mark-paid')) {
            return false;
        }

        // Only accountants can mark invoices as paid
        // And only approved invoices can be marked as paid
        return $user->isAccountant() && $invoice->isApproved();
    }

    /**
     * Determine whether the user can export invoices.
     */
    public function export(User $user): bool
    {
        // Check permission
        return $user->hasPermission('invoices.export');
    }

    /**
     * Determine whether the user can generate reports.
     */
    public function report(User $user): bool
    {
        // Check permission
        return $user->hasPermission('invoices.report');
    }
}