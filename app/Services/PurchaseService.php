<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PurchaseService
{
    public function __construct(private AuditLogger $auditLogger) {}

    /**
     * @param  list<array{product_id:int, quantity:int, unit_cost_foreign:float|int|string}>  $lines
     */
    public function create(array $data, array $lines, ?User $actor = null): Purchase
    {
        $lines = collect($lines)->filter(fn ($l) => (int) ($l['quantity'] ?? 0) > 0)->values();
        if ($lines->isEmpty()) {
            throw ValidationException::withMessages(['items' => 'Add at least one purchase line.']);
        }

        return DB::transaction(function () use ($data, $lines, $actor) {
            $rate = (float) ($data['exchange_rate'] ?? 1);
            if ($rate <= 0) {
                $rate = 1;
            }

            $purchase = Purchase::query()->create([
                'number' => $data['number'] ?? $this->nextNumber(),
                'supplier_id' => $data['supplier_id'],
                'status' => $data['status'] ?? Purchase::STATUS_ORDERED,
                'currency' => $data['currency'] ?? 'CNY',
                'exchange_rate' => $rate,
                'ordered_at' => $data['ordered_at'] ?? now()->toDateString(),
                'expected_at' => $data['expected_at'] ?? null,
                'notes' => $data['notes'] ?? null,
                'created_by' => $actor?->id,
            ]);

            $subForeign = 0;
            $subBdt = 0;

            foreach ($lines as $line) {
                $qty = (int) $line['quantity'];
                $unitForeign = (float) $line['unit_cost_foreign'];
                $unitBdt = round($unitForeign * $rate, 2);
                $lineBdt = round($unitBdt * $qty, 2);

                PurchaseItem::query()->create([
                    'purchase_id' => $purchase->id,
                    'product_id' => $line['product_id'],
                    'quantity' => $qty,
                    'unit_cost_foreign' => $unitForeign,
                    'unit_cost_bdt' => $unitBdt,
                    'line_total_bdt' => $lineBdt,
                ]);

                $subForeign += $unitForeign * $qty;
                $subBdt += $lineBdt;
            }

            $purchase->update([
                'subtotal_foreign' => round($subForeign, 2),
                'subtotal_bdt' => round($subBdt, 2),
            ]);

            $this->auditLogger->log(
                'purchases',
                'created',
                "Created purchase {$purchase->number}",
                $purchase,
                null,
                ['supplier_id' => $purchase->supplier_id, 'subtotal_bdt' => $purchase->subtotal_bdt],
                $actor,
            );

            return $purchase->fresh(['items.product', 'supplier']);
        });
    }

    public function nextNumber(): string
    {
        $seq = Purchase::withTrashed()->count() + 1;

        return 'PO-'.now()->format('ymd').'-'.str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }

    public function nextSupplierCode(): string
    {
        $seq = Supplier::withTrashed()->count() + 1;

        return 'SUP-'.str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }
}
