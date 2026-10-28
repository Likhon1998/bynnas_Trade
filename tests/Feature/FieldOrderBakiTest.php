<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Shop;
use App\Models\ShopVisit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FieldOrderBakiTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    public function test_visit_shows_category_first_browse_and_price_edit(): void
    {
        $salesman = User::where('email', 'karim@field.local')->firstOrFail();
        $shop = Shop::query()->where('status', Shop::STATUS_ACTIVE)->firstOrFail();
        $shop->update(['assigned_salesman_id' => $salesman->id]);

        ShopVisit::query()
            ->where('salesman_id', $salesman->id)
            ->whereNull('checked_out_at')
            ->update(['checked_out_at' => now()]);

        $visit = ShopVisit::query()->create([
            'shop_id' => $shop->id,
            'salesman_id' => $salesman->id,
            'checked_in_at' => now(),
            'purpose' => 'Order collection',
            'outcome' => ShopVisit::OUTCOME_IN_PROGRESS,
        ]);

        $product = Product::query()
            ->where('status', Product::STATUS_ACTIVE)
            ->where('is_published', true)
            ->with('category')
            ->firstOrFail();

        $this->actingAs($salesman)
            ->get(route('field.visit.show', $visit))
            ->assertOk()
            ->assertSee('All products', false)
            ->assertSee('fieldOrder(', false)
            ->assertSee($product->name)
            ->assertSee('f-price-edit', false)
            ->assertSee('paid_now', false)
            ->assertSee('baki', false);
    }

    public function test_salesman_can_collect_partial_payment_and_leave_baki(): void
    {
        $salesman = User::where('email', 'karim@field.local')->firstOrFail();
        $shop = Shop::query()->where('status', Shop::STATUS_ACTIVE)->firstOrFail();
        $shop->update(['assigned_salesman_id' => $salesman->id, 'credit_limit' => 500000]);
        $product = Product::query()
            ->where('status', Product::STATUS_ACTIVE)
            ->where('is_published', true)
            ->firstOrFail();

        ShopVisit::query()
            ->where('salesman_id', $salesman->id)
            ->whereNull('checked_out_at')
            ->update(['checked_out_at' => now()]);

        $visit = ShopVisit::query()->create([
            'shop_id' => $shop->id,
            'salesman_id' => $salesman->id,
            'checked_in_at' => now(),
        ]);

        $unit = (float) $product->priceForGroup($shop->priceGroup);
        $qty = 2;
        $total = round($unit * $qty, 2);
        $paidNow = round($total / 2, 2);
        $baki = round($total - $paidNow, 2);

        $this->actingAs($salesman)
            ->post(route('field.visit.order', $visit), [
                'items' => [[
                    'product_id' => $product->id,
                    'quantity' => $qty,
                    'unit_price' => $unit,
                ]],
                'paid_now' => $paidNow,
                'payment_method' => Payment::METHOD_CASH,
                'notes' => 'Owner paid half, rest baki',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $order = Order::query()->where('visit_id', $visit->id)->firstOrFail();

        $this->assertTrue($order->advance_required);
        $this->assertEqualsWithDelta($paidNow, (float) $order->advance_amount, 0.01);
        $this->assertSame(Order::STATUS_AWAITING_ADVANCE, $order->status);
        $this->assertNotNull($order->advance_invoice_id);

        $this->assertEqualsWithDelta(
            $baki,
            round((float) $order->total - (float) $order->advance_amount, 2),
            0.01
        );

        $this->assertTrue(
            Payment::query()
                ->where('shop_id', $shop->id)
                ->where('invoice_id', $order->advance_invoice_id)
                ->where('status', Payment::STATUS_PENDING)
                ->where('amount', $paidNow)
                ->exists()
        );

        $this->actingAs($salesman)
            ->get(route('field.orders.show', $order))
            ->assertOk()
            ->assertSee('Baki', false);
    }

    public function test_full_baki_order_stays_pending_audit_without_advance(): void
    {
        $salesman = User::where('email', 'karim@field.local')->firstOrFail();
        $shop = Shop::query()->where('status', Shop::STATUS_ACTIVE)->firstOrFail();
        $shop->update(['assigned_salesman_id' => $salesman->id]);
        $product = Product::query()
            ->where('status', Product::STATUS_ACTIVE)
            ->where('is_published', true)
            ->firstOrFail();

        ShopVisit::query()
            ->where('salesman_id', $salesman->id)
            ->whereNull('checked_out_at')
            ->update(['checked_out_at' => now()]);

        $visit = ShopVisit::query()->create([
            'shop_id' => $shop->id,
            'salesman_id' => $salesman->id,
            'checked_in_at' => now(),
        ]);

        $this->actingAs($salesman)
            ->post(route('field.visit.order', $visit), [
                'items' => [[
                    'product_id' => $product->id,
                    'quantity' => 1,
                    'unit_price' => (float) $product->priceForGroup($shop->priceGroup),
                ]],
                'paid_now' => 0,
            ])
            ->assertSessionHasNoErrors();

        $order = Order::query()->where('visit_id', $visit->id)->firstOrFail();
        $this->assertSame(Order::STATUS_PENDING_AUDIT, $order->status);
        $this->assertFalse((bool) $order->advance_required);
    }
}
