<?php

namespace App\Policies;

use App\Models\ShopVisit;
use App\Models\User;

class ShopVisitPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('visits.view');
    }

    public function view(User $user, ShopVisit $visit): bool
    {
        return $user->can('visits.view') && $visit->isAccessibleBy($user);
    }

    public function create(User $user): bool
    {
        return $user->can('visits.create');
    }

    public function update(User $user, ShopVisit $visit): bool
    {
        return $user->can('visits.edit') && $visit->isAccessibleBy($user);
    }
}
