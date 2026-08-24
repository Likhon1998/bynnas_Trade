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
        return $user->can('shops.approve');
    }

    public function manageCredentials(User $user, Shop $shop): bool
    {
        return $user->can('shops.manage_credentials');
    }
}
