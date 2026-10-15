<?php

namespace App\Policies;

use App\Models\Shop;
use App\Models\User;

class ShopPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('shops.view');
    }

    public function view(User $user, Shop $shop): bool
    {
        return $user->can('shops.view') && $shop->isAccessibleBy($user);
    }

    public function create(User $user): bool
    {
        return $user->can('shops.create');
    }

    public function update(User $user, Shop $shop): bool
    {
        return $user->can('shops.edit') && $shop->isAccessibleBy($user);
    }

    public function delete(User $user, Shop $shop): bool
    {
        return $user->can('shops.delete') && $shop->isAccessibleBy($user);
    }

    public function approve(User $user, Shop $shop): bool
    {
        if ($user->can('shops.approve') && $shop->isAccessibleBy($user)) {
            return true;
        }

        // Field salesmen may approve shops they registered or are assigned to.
        return $user->portal === User::PORTAL_SALESMAN
            && (
                (int) $shop->assigned_salesman_id === (int) $user->id
                || (int) $shop->created_by === (int) $user->id
            );
    }

    public function manageCredentials(User $user, Shop $shop): bool
    {
        return $user->can('shops.manage_credentials') && $shop->isAccessibleBy($user);
    }
}
