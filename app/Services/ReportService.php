<?php

namespace App\Services;

use App\Models\Commission;
use App\Models\InventoryLedger;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\ProductReturn;
use App\Models\Purchase;
use App\Models\SalesTarget;
use App\Models\Shipment;
use App\Models\Shop;
use App\Models\WarehouseStock;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportService
{
    /**
     * @return array<string, array{title:string,description:string}>
     */
    public function catalog(): array
    {
        return [
            'sales' => [
                'title' => 'Sales register',
                'description' => 'Billed orders, shops and salesmen for the selected period.',
            ],
            'credit' => [
                'title' => 'Outstanding credit',
                'description' => 'Shop-wise receivables, ageing and credit-limit utilisation.',
            ],
            'inventory' => [
                'title' => 'Inventory movement',
                'description' => 'Reservations, picks, receipts and warehouse balances.',
            ],
            'shipments' => [
                'title' => 'Shipment costing',
                'description' => 'Freight, customs and landed cost by inbound container.',
            ],
            'commissions' => [
                'title' => 'Salesman commission',
                'description' => 'Approved collections against configurable commission rules.',
            ],
            'returns' => [
                'title' => 'Returns & warranty',
                'description' => 'Defects, replacements and credit notes awaiting audit.',
            ],
            'procurement' => [
                'title' => 'Procurement',
                'description' => 'Open POs, supplier exposure and shipment linkage.',
            ],
            'targets' => [
                'title' => 'Target vs achievement',
                'description' => 'Territory targets, visits and conversion for the field force.',
            ],
        ];
    }

    /**
     * @return array{title:string,description:string,columns:list<string>,rows:list<list<string|int|float>>,meta:array<string,mixed>}
     */
    public function generate(string $key, ?Carbon $from = null, ?Carbon $to = null): array
    {
        $from ??= now()->startOfMonth();
        $to ??= now()->endOfDay();
        $catalog = $this->catalog();
        abort_unless(isset($catalog[$key]), 404);

        return match ($key) {
            'sales' => $this->salesReport($from, $to, $catalog[$key]),
            'credit' => $this->creditReport($catalog[$key]),
            'inventory' => $this->inventoryReport($from, $to, $catalog[$key]),
            'shipments' => $this->shipmentsReport($catalog[$key]),
            'commissions' => $this->commissionsReport($from, $to, $catalog[$key]),
            'returns' => $this->returnsReport($from, $to, $catalog[$key]),
            'procurement' => $this->procurementReport($catalog[$key]),
            'targets' => $this->targetsReport($catalog[$key]),
            default => abort(404),
        };
    }

    public function csv(string $key, ?Carbon $from = null, ?Carbon $to = null): StreamedResponse
    {
        $report = $this->generate($key, $from, $to);

        return response()->streamDownload(function () use ($report) {
            $out = fopen('php://output', 'w');
            fputcsv($out, $report['columns']);
            foreach ($report['rows'] as $row) {
                fputcsv($out, $row);
            }
            fclose($out);
        }, 'bynnas-'.$key.'-'.now()->format('Ymd-His').'.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }

    private function salesReport(Carbon $from, Carbon $to, array $meta): array
    {
        $orders = Order::query()
            ->with(['shop', 'salesman'])
            ->whereBetween('submitted_at', [$from, $to])
            ->whereNotIn('status', [Order::STATUS_CANCELLED, Order::STATUS_DRAFT])
            ->latest('submitted_at')
            ->get();

        return [
            'title' => $meta['title'],
            'description' => $meta['description'],
            'columns' => ['Order', 'Shop', 'Salesman', 'Source', 'Status', 'Total', 'Submitted'],
            'rows' => $orders->map(fn (Order $o) => [
                $o->number,
                $o->shop?->name,
                $o->salesman?->name ?: '—',
                $o->source,
                $o->status,
                (float) $o->total,
                $o->submitted_at?->format('Y-m-d H:i'),
            ])->all(),
            'meta' => ['from' => $from->toDateString(), 'to' => $to->toDateString(), 'count' => $orders->count(), 'sum' => (float) $orders->sum('total')],
        ];
    }

    private function creditReport(array $meta): array
    {
        $shops = Shop::query()
            ->whereIn('status', [Shop::STATUS_ACTIVE, Shop::STATUS_ON_HOLD])
            ->orderByDesc('outstanding_balance')
            ->get();

        return [
            'title' => $meta['title'],
            'description' => $meta['description'],
            'columns' => ['Shop', 'Code', 'Status', 'Credit limit', 'Outstanding', 'Available', 'Utilisation %'],
            'rows' => $shops->map(function (Shop $s) {
                $limit = (float) $s->credit_limit;
                $out = (float) $s->outstanding_balance;
                $util = $limit > 0 ? round(($out / $limit) * 100, 1) : 0;

                return [
                    $s->name,
                    $s->code,
                    $s->status,
                    $limit,
                    $out,
                    max(0, $limit - $out),
                    $util,
                ];
            })->all(),
            'meta' => ['count' => $shops->count(), 'sum' => (float) $shops->sum('outstanding_balance')],
        ];
    }

    private function inventoryReport(Carbon $from, Carbon $to, array $meta): array
    {
        $stocks = WarehouseStock::query()->with(['warehouse', 'product'])->orderByDesc('qty_on_hand')->limit(200)->get();
        $movements = InventoryLedger::query()
            ->with(['product', 'warehouse'])
            ->whereBetween('created_at', [$from, $to])
            ->latest()
            ->limit(100)
            ->get();

        $rows = $stocks->map(fn (WarehouseStock $s) => [
            $s->warehouse?->name,
            $s->product?->sku,
            $s->product?->name,
            (int) $s->qty_on_hand,
            (int) $s->qty_reserved,
            (int) $s->qty_on_hand - (int) $s->qty_reserved,
            'balance',
        ])->all();

        foreach ($movements as $m) {
            $rows[] = [
                $m->warehouse?->name,
                $m->product?->sku,
                $m->product?->name,
                (int) $m->qty_delta,
                '—',
                '—',
                $m->type,
            ];
        }

        return [
            'title' => $meta['title'],
            'description' => $meta['description'],
            'columns' => ['Warehouse', 'SKU', 'Product', 'On hand / qty', 'Reserved', 'Available', 'Type'],
            'rows' => $rows,
            'meta' => ['from' => $from->toDateString(), 'to' => $to->toDateString()],
        ];
    }

    private function shipmentsReport(array $meta): array
    {
        $shipments = Shipment::query()->with('supplier')->latest()->limit(100)->get();

        return [
            'title' => $meta['title'],
            'description' => $meta['description'],
            'columns' => ['Shipment', 'Supplier', 'Origin', 'Status', 'Freight', 'Customs', 'Insurance', 'Other', 'Landed total'],
            'rows' => $shipments->map(fn (Shipment $s) => [
                $s->number,
                $s->supplier?->name,
                $s->origin,
                $s->status,
                (float) ($s->freight_cost ?? 0),
                (float) ($s->customs_duty ?? 0),
                (float) ($s->insurance_cost ?? 0),
                (float) ($s->other_cost ?? 0),
                (float) ($s->total_landed_cost ?? 0),
            ])->all(),
            'meta' => ['count' => $shipments->count(), 'sum' => (float) $shipments->sum('total_landed_cost')],
        ];
    }

    private function commissionsReport(Carbon $from, Carbon $to, array $meta): array
    {
        $rows = Commission::query()
            ->with('salesman')
            ->whereBetween('created_at', [$from, $to])
            ->latest()
            ->get();

        return [
            'title' => $meta['title'],
            'description' => $meta['description'],
            'columns' => ['Commission', 'Salesman', 'Type', 'Period', 'Base', 'Rate %', 'Amount', 'Status'],
            'rows' => $rows->map(fn (Commission $c) => [
                $c->number,
                $c->salesman?->name,
                $c->type,
                $c->periodLabel(),
                (float) $c->base_amount,
                (float) $c->rate_percent,
                (float) $c->commission_amount,
                $c->status,
            ])->all(),
            'meta' => ['from' => $from->toDateString(), 'to' => $to->toDateString(), 'sum' => (float) $rows->sum('commission_amount')],
        ];
    }

    private function returnsReport(Carbon $from, Carbon $to, array $meta): array
    {
        $rows = ProductReturn::query()
            ->with(['shop', 'order'])
            ->whereBetween('created_at', [$from, $to])
            ->latest()
            ->get();

        return [
            'title' => $meta['title'],
            'description' => $meta['description'],
            'columns' => ['Return', 'Shop', 'Order', 'Reason', 'Total', 'Restock', 'Credit', 'Status'],
            'rows' => $rows->map(fn (ProductReturn $r) => [
                $r->number,
                $r->shop?->name,
                $r->order?->number ?: '—',
                $r->reason_type,
                (float) $r->total,
                $r->restock ? 'Yes' : 'No',
                $r->credit_issued ? 'Yes' : 'No',
                $r->status,
            ])->all(),
            'meta' => ['from' => $from->toDateString(), 'to' => $to->toDateString(), 'sum' => (float) $rows->sum('total')],
        ];
    }

    private function procurementReport(array $meta): array
    {
        $purchases = Purchase::query()->with('supplier')->latest()->limit(100)->get();

        return [
            'title' => $meta['title'],
            'description' => $meta['description'],
            'columns' => ['PO', 'Supplier', 'Status', 'Currency', 'FX', 'Total foreign', 'Total BDT', 'Ordered'],
            'rows' => $purchases->map(fn (Purchase $p) => [
                $p->number,
                $p->supplier?->name,
                $p->status,
                $p->currency,
                (float) ($p->exchange_rate ?? 0),
                (float) ($p->subtotal_foreign ?? 0),
                (float) ($p->subtotal_bdt ?? 0),
                $p->ordered_at?->format('Y-m-d') ?: $p->created_at?->format('Y-m-d'),
            ])->all(),
            'meta' => ['count' => $purchases->count()],
        ];
    }

    private function targetsReport(array $meta): array
    {
        $rows = SalesTarget::query()
            ->with(['salesman', 'territory'])
            ->where('year', now()->year)
            ->where('month', now()->month)
            ->orderBy('salesman_id')
            ->get();

        return [
            'title' => $meta['title'],
            'description' => $meta['description'],
            'columns' => ['Period', 'Salesman', 'Territory', 'Target', 'Achieved', 'Collected', '%', 'Met'],
            'rows' => $rows->map(fn (SalesTarget $t) => [
                $t->periodLabel(),
                $t->salesman?->name,
                $t->territory?->name ?: '—',
                (float) $t->target_amount,
                (float) $t->achieved_amount,
                (float) $t->collected_amount,
                $t->achievementPercent(),
                $t->target_met ? 'Yes' : 'No',
            ])->all(),
            'meta' => ['period' => now()->format('Y-m'), 'hit' => $rows->where('target_met', true)->count()],
        ];
    }
}
