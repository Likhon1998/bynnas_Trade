<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Payment;
use App\Models\ProductReturn;
use App\Models\User;
use App\Notifications\SystemAlert;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;

class AppNotificationService
{
    public function adminRecipients(): Collection
    {
        return User::query()
            ->where('portal', User::PORTAL_ADMIN)
            ->where('is_active', true)
            ->get();
    }

    public function notifyAdmins(string $title, string $body, ?string $url = null, string $category = 'system', string $level = 'info'): void
    {
        $recipients = $this->adminRecipients();
        if ($recipients->isEmpty()) {
            return;
        }

        Notification::send($recipients, new SystemAlert($title, $body, $url, $category, $level));
    }

    public function orderPendingAudit(Order $order): void
    {
        $this->notifyAdmins(
            "Order {$order->number} awaiting audit",
            ($order->shop?->name ?: 'Shop').' · ৳ '.number_format((float) $order->total, 2).' · '.str_replace('_', ' ', $order->source),
            '/admin/orders?audit_queue=1&view='.$order->id,
            'orders',
            'warning',
        );
    }

    public function paymentPending(Payment $payment): void
    {
        $this->notifyAdmins(
            "Payment {$payment->number} pending verification",
            ($payment->shop?->name ?: 'Shop').' · ৳ '.number_format((float) $payment->amount, 2),
            '/admin/payments?status=pending',
            'payments',
            'info',
        );
    }

    public function returnSubmitted(ProductReturn $return): void
    {
        $this->notifyAdmins(
            "Return {$return->number} submitted",
            ($return->shop?->name ?: 'Shop').' · '.$return->reasonTypeLabel().' · ৳ '.number_format((float) $return->total, 2),
            '/admin/returns/'.$return->id,
            'returns',
            'warning',
        );
    }

    public function seedOperationalAlerts(): int
    {
        $count = 0;

        $pendingOrders = Order::query()->where('status', Order::STATUS_PENDING_AUDIT)->count();
        if ($pendingOrders > 0) {
            $this->notifyAdmins(
                "{$pendingOrders} order(s) awaiting Super Admin audit",
                'Open the audit queue to approve and reserve stock.',
                '/admin/orders?audit_queue=1',
                'orders',
                'warning',
            );
            $count++;
        }

        $pendingPayments = Payment::query()->where('status', Payment::STATUS_PENDING)->count();
        if ($pendingPayments > 0) {
            $this->notifyAdmins(
                "{$pendingPayments} payment(s) pending verification",
                'Verify collections to update credit and commissions.',
                '/admin/payments?status=pending',
                'payments',
                'info',
            );
            $count++;
        }

        $pendingReturns = ProductReturn::query()->where('status', ProductReturn::STATUS_PENDING)->count();
        if ($pendingReturns > 0) {
            $this->notifyAdmins(
                "{$pendingReturns} return(s) awaiting review",
                'Approve to issue credit and optional restock.',
                '/admin/returns?status=pending',
                'returns',
                'warning',
            );
            $count++;
        }

        $this->notifyAdmins(
            'Phase 9 analytics & reports are live',
            'Dashboard KPIs, report packs and this notification centre now read from live data.',
            '/admin/analytics',
            'system',
            'info',
        );
        $count++;

        return $count;
    }
}
