<?php

namespace App\Services;

use App\Models\SalesmanProfile;
use App\Models\Shop;
use App\Models\User;
use App\Models\UserAccessScope;
use Illuminate\Support\Facades\DB;

class SalesmanService
{
    public function __construct(
        private AuditLogger $auditLogger,
        private RbacService $rbac,
    ) {}

    public function create(array $data, array $shopIds = [], ?User $actor = null): User
    {
        return DB::transaction(function () use ($data, $shopIds, $actor) {
            $user = $this->rbac->createUser([
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'password' => $data['password'] ?? '12345678',
                'is_active' => $data['is_active'] ?? true,
                'portal' => User::PORTAL_SALESMAN,
            ], ['Salesman'], [
                'scope_type' => ! empty($data['territory_id'])
                    ? UserAccessScope::TYPE_TERRITORY
                    : UserAccessScope::TYPE_GLOBAL,
                'scope_id' => $data['territory_id'] ?? null,
                'label' => ! empty($data['territory_id']) ? 'Territory scope' : 'All territories',
            ], $actor);

            SalesmanProfile::query()->create([
                'user_id' => $user->id,
                'employee_code' => $data['employee_code'] ?? $this->nextCode(),
                'territory_id' => $data['territory_id'] ?? null,
                'monthly_target' => $data['monthly_target'] ?? 0,
                'joined_at' => $data['joined_at'] ?? now()->toDateString(),
                'notes' => $data['notes'] ?? null,
                'is_active' => $data['is_active'] ?? true,
            ]);

            $this->syncAssignedShops($user, $shopIds);

            $this->auditLogger->log(
                'salesmen',
                'created',
                "Created salesman {$user->email}",
                $user,
                null,
                ['employee_code' => $user->salesmanProfile?->employee_code, 'shops' => $shopIds],
                $actor,
            );

            return $user->fresh(['salesmanProfile.territory', 'assignedShops', 'roles']);
        });
    }

    public function update(User $salesman, array $data, ?array $shopIds = null, ?User $actor = null): User
    {
        return DB::transaction(function () use ($salesman, $data, $shopIds, $actor) {
            $this->rbac->updateUser($salesman, [
                'name' => $data['name'] ?? $salesman->name,
                'email' => $data['email'] ?? $salesman->email,
                'phone' => $data['phone'] ?? $salesman->phone,
                'password' => $data['password'] ?? null,
                'is_active' => $data['is_active'] ?? $salesman->is_active,
                'portal' => User::PORTAL_SALESMAN,
            ], ['Salesman'], [
                'scope_type' => ! empty($data['territory_id'])
                    ? UserAccessScope::TYPE_TERRITORY
                    : UserAccessScope::TYPE_GLOBAL,
                'scope_id' => $data['territory_id'] ?? null,
                'label' => ! empty($data['territory_id']) ? 'Territory scope' : 'All territories',
            ], $actor);

            $profile = $salesman->salesmanProfile;
            if (! $profile) {
                $profile = SalesmanProfile::query()->create([
                    'user_id' => $salesman->id,
                    'employee_code' => $data['employee_code'] ?? $this->nextCode(),
                ]);
            }

            $profile->update([
                'employee_code' => $data['employee_code'] ?? $profile->employee_code,
                'territory_id' => $data['territory_id'] ?? null,
                'monthly_target' => $data['monthly_target'] ?? $profile->monthly_target,
                'joined_at' => $data['joined_at'] ?? $profile->joined_at,
                'notes' => $data['notes'] ?? $profile->notes,
                'is_active' => $data['is_active'] ?? $profile->is_active,
            ]);

            if ($shopIds !== null) {
                $this->syncAssignedShops($salesman, $shopIds);
            }

            $this->auditLogger->log(
                'salesmen',
                'updated',
                "Updated salesman {$salesman->email}",
                $salesman,
                null,
                null,
                $actor,
            );

            return $salesman->fresh(['salesmanProfile.territory', 'assignedShops', 'roles']);
        });
    }

    public function syncAssignedShops(User $salesman, array $shopIds): void
    {
        Shop::query()
            ->where('assigned_salesman_id', $salesman->id)
            ->whereNotIn('id', $shopIds)
            ->update(['assigned_salesman_id' => null]);

        if ($shopIds !== []) {
            Shop::query()->whereIn('id', $shopIds)->update(['assigned_salesman_id' => $salesman->id]);
        }
    }

    public function nextCode(): string
    {
        $latest = SalesmanProfile::query()->orderByDesc('id')->value('employee_code');
        $num = 1001;

        if ($latest && preg_match('/(\d+)$/', $latest, $m)) {
            $num = ((int) $m[1]) + 1;
        }

        return 'SM-'.$num;
    }

    public function salesmanQuery()
    {
        return User::query()
            ->where('portal', User::PORTAL_SALESMAN)
            ->with(['salesmanProfile.territory', 'assignedShops', 'roles']);
    }
}
