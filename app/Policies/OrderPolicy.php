<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;

class OrderPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('orders.view');
    }

    public function view(User $user, Order $order): bool
    {
        return $user->can('orders.view') && $order->isAccessibleBy($user);
    }

    public function create(User $user): bool
    {
        return $user->can('orders.create');
    }

    public function update(User $user, Order $order): bool
    {
        return $user->can('orders.edit') && $order->isAccessibleBy($user);
    }

    public function approve(User $user, Order $order): bool
    {
        return $user->can('orders.approve') && $order->isPendingAudit();
    }

    public function reject(User $user, Order $order): bool
    {
        return $user->can('orders.reject') && $order->isPendingAudit();
    }

    public function cancel(User $user, Order $order): bool
    {
        return $user->can('orders.cancel')
            && in_array($order->status, [Order::STATUS_PENDING_AUDIT, Order::STATUS_APPROVED], true);
    }

    public function delete(User $user, Order $order): bool
    {
        if (! $order->canDeleteBeforeApproval()) {
            return false;
        }

        if ($user->portal === User::PORTAL_SHOP) {
            return $order->isAccessibleBy($user);
        }

        return $user->can('orders.cancel') || $user->can('orders.edit');
    }
}
