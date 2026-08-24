<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\Shop;
use App\Models\Territory;
use App\Models\User;
use App\Services\OrderService;
use App\Services\SalesmanService;
use App\Services\VisitService;
use Illuminate\Database\Seeder;

class Phase3Seeder extends Seeder
{
    public function run(): void
    {
        $salesmanService = app(SalesmanService::class);
        $visitService = app(VisitService::class);
        $orderService = app(OrderService::class);

        $dhaka = Territory::query()->where('code', 'DHK-N')->first();
        $ctg = Territory::query()->where('code', 'CTG')->first();

        $techZone = Shop::query()->where('code', 'SHP-1042')->first();
        $gadgetHub = Shop::query()->where('code', 'SHP-1043')->first();

        $karim = User::query()->where('email', 'karim@field.local')->first();
        if (! $karim) {
            $karim = $salesmanService->create([
                'name' => 'Karim Hossain',
                'email' => 'karim@field.local',
                'phone' => '01720001122',
                'password' => '12345678',
                'employee_code' => 'SM-1001',
                'territory_id' => $dhaka?->id,
                'monthly_target' => 500000,
                'joined_at' => now()->subMonths(4)->toDateString(),
                'is_active' => true,
            ], array_filter([$techZone?->id]));
        } else {
            $salesmanService->update($karim, [
                'name' => 'Karim Hossain',
                'email' => 'karim@field.local',
                'phone' => '01720001122',
                'territory_id' => $dhaka?->id,
                'monthly_target' => 500000,
                'is_active' => true,
            ], array_filter([$techZone?->id]));
        }

        $sabrina = User::query()->where('email', 'sabrina@field.local')->first();
        if (! $sabrina) {
            $sabrina = $salesmanService->create([
                'name' => 'Sabrina Akter',
                'email' => 'sabrina@field.local',
                'phone' => '01830002233',
                'password' => '12345678',
                'employee_code' => 'SM-1002',
                'territory_id' => $ctg?->id,
                'monthly_target' => 350000,
                'joined_at' => now()->subMonths(2)->toDateString(),
                'is_active' => true,
            ], array_filter([$gadgetHub?->id]));
        } else {
            $salesmanService->update($sabrina, [
                'name' => 'Sabrina Akter',
                'email' => 'sabrina@field.local',
                'phone' => '01830002233',
                'territory_id' => $ctg?->id,
                'monthly_target' => 350000,
                'is_active' => true,
            ], array_filter([$gadgetHub?->id]));
        }

        if ($techZone && ! Order::query()->where('shop_id', $techZone->id)->exists()) {
            $visit = $visitService->checkIn($karim->fresh(), $techZone, [
                'purpose' => 'Weekly replenishment',
                'notes' => 'Seed visit',
            ]);

            $products = \App\Models\Product::query()->where('is_published', true)->take(3)->get();
            $lines = $products->map(fn ($p, $i) => [
                'product_id' => $p->id,
                'quantity' => [10, 5, 8][$i] ?? 4,
            ])->all();

            $orderService->collectFromSalesman($karim, $techZone, $lines, $visit, 'Seeded Phase 3 sample order');
            $visitService->checkOut($visit->fresh(), ['outcome' => 'order_taken'], $karim);
        }
    }
}
