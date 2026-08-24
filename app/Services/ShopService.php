<?php

namespace App\Services;

use App\Models\Shop;
use App\Models\User;
use App\Models\UserAccessScope;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ShopService
{
    public function __construct(private AuditLogger $auditLogger) {}

    public function create(array $data, ?User $actor = null, ?array $credential = null): Shop
    {
        return DB::transaction(function () use ($data, $actor, $credential) {
            $shop = Shop::query()->create([
                'code' => $data['code'] ?? $this->nextCode(),
                'name' => $data['name'],
                'owner_name' => $data['owner_name'],
                'phone' => $data['phone'] ?? null,
                'email' => $data['email'] ?? null,
                'address' => $data['address'] ?? null,
                'city' => $data['city'] ?? null,
                'territory_id' => $data['territory_id'] ?? null,
                'price_group_id' => $data['price_group_id'] ?? null,
                'assigned_salesman_id' => $data['assigned_salesman_id'] ?? null,
                'credit_limit' => $data['credit_limit'] ?? 0,
                'payment_terms_days' => $data['payment_terms_days'] ?? 21,
                'status' => $data['status'] ?? Shop::STATUS_PENDING,
                'notes' => $data['notes'] ?? null,
                'created_by' => $actor?->id,
                'approved_at' => ($data['status'] ?? null) === Shop::STATUS_ACTIVE ? now() : null,
            ]);

            if ($credential && ! empty($credential['email']) && ! empty($credential['password'])) {
                $this->attachShopUser($shop, $credential, $actor);
            }

            $this->auditLogger->log(
                'shops',
                'created',
                "Created shop {$shop->code}",
                $shop,
                null,
                $shop->only(['code', 'name', 'status', 'credit_limit']),
                $actor,
            );

            return $shop->fresh(['territory', 'priceGroup', 'assignedSalesman', 'users']);
        });
    }

    public function update(Shop $shop, array $data, ?User $actor = null): Shop
    {
        return DB::transaction(function () use ($shop, $data, $actor) {
            $old = $shop->only(['name', 'status', 'credit_limit', 'price_group_id', 'assigned_salesman_id']);

            $shop->update(collect($data)->only([
                'name', 'owner_name', 'phone', 'email', 'address', 'city',
                'territory_id', 'price_group_id', 'assigned_salesman_id',
                'credit_limit', 'payment_terms_days', 'status', 'notes',
            ])->all());

            if (($data['status'] ?? null) === Shop::STATUS_ACTIVE && ! $shop->approved_at) {
                $shop->forceFill(['approved_at' => now()])->save();
            }

            $this->auditLogger->log(
                'shops',
                'updated',
                "Updated shop {$shop->code}",
                $shop,
                $old,
                $shop->fresh()->only(['name', 'status', 'credit_limit', 'price_group_id', 'assigned_salesman_id']),
                $actor,
            );

            return $shop->fresh(['territory', 'priceGroup', 'assignedSalesman', 'users']);
        });
    }

    public function approve(Shop $shop, ?User $actor = null): Shop
    {
        $shop->update([
            'status' => Shop::STATUS_ACTIVE,
            'approved_at' => now(),
        ]);

        $this->auditLogger->log('shops', 'approved', "Approved shop {$shop->code}", $shop, null, null, $actor);

        return $shop;
    }

    public function attachShopUser(Shop $shop, array $credential, ?User $actor = null): User
    {
        $user = User::query()->updateOrCreate(
            ['email' => $credential['email']],
            [
                'name' => $credential['name'] ?? $shop->owner_name,
                'phone' => $credential['phone'] ?? $shop->phone,
                'password' => $credential['password'],
                'is_active' => true,
                'portal' => User::PORTAL_SHOP,
                'created_by' => $actor?->id,
                'email_verified_at' => now(),
            ],
        );

        $user->syncRoles(['Shop Owner']);
        $user->accessScopes()->delete();
        $user->accessScopes()->create([
            'scope_type' => UserAccessScope::TYPE_SHOP,
            'scope_id' => $shop->id,
            'label' => $shop->name,
        ]);

        $shop->users()->syncWithoutDetaching([
            $user->id => ['is_primary' => true],
        ]);

        $this->auditLogger->log(
            'shops',
            'credentials_issued',
            "Issued portal credentials for shop {$shop->code}",
            $shop,
            null,
            ['email' => $user->email],
            $actor,
        );

        return $user;
    }

    public function nextCode(): string
    {
        $seq = Shop::withTrashed()->count() + 1;

        return 'SHP-'.str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }
}
