<?php

namespace App\Services;

use App\Models\Shop;
use App\Models\ShopVisit;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class VisitService
{
    public function __construct(private AuditLogger $auditLogger) {}

    public function checkIn(User $salesman, Shop $shop, array $data = []): ShopVisit
    {
        if ($shop->assigned_salesman_id && $shop->assigned_salesman_id !== $salesman->id
            && ! $salesman->isSuperAdmin() && ! $salesman->hasGlobalAccessScope()) {
            throw ValidationException::withMessages([
                'shop_id' => 'This shop is not assigned to you.',
            ]);
        }

        $open = ShopVisit::query()
            ->where('salesman_id', $salesman->id)
            ->whereNull('checked_out_at')
            ->first();

        if ($open) {
            throw ValidationException::withMessages([
                'shop_id' => 'Check out of your current visit before starting another.',
            ]);
        }

        $visit = ShopVisit::query()->create([
            'salesman_id' => $salesman->id,
            'shop_id' => $shop->id,
            'checked_in_at' => now(),
            'purpose' => $data['purpose'] ?? 'Order collection',
            'outcome' => ShopVisit::OUTCOME_IN_PROGRESS,
            'notes' => $data['notes'] ?? null,
            'latitude' => $data['latitude'] ?? null,
            'longitude' => $data['longitude'] ?? null,
        ]);

        $this->auditLogger->log(
            'visits',
            'checked_in',
            "Checked in at {$shop->code}",
            $visit,
            null,
            ['shop_id' => $shop->id],
            $salesman,
        );

        return $visit->fresh(['shop', 'salesman']);
    }

    public function checkOut(ShopVisit $visit, array $data = [], ?User $actor = null): ShopVisit
    {
        if (! $visit->isOpen()) {
            throw ValidationException::withMessages([
                'visit' => 'This visit is already closed.',
            ]);
        }

        $visit->update([
            'checked_out_at' => now(),
            'outcome' => $data['outcome'] ?? ($visit->order_id ? ShopVisit::OUTCOME_ORDER_TAKEN : ShopVisit::OUTCOME_NO_ORDER),
            'notes' => $data['notes'] ?? $visit->notes,
        ]);

        $this->auditLogger->log(
            'visits',
            'checked_out',
            "Checked out from visit #{$visit->id}",
            $visit,
            null,
            ['outcome' => $visit->outcome],
            $actor,
        );

        return $visit->fresh(['shop', 'order']);
    }
}
