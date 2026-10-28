<?php

namespace Tests\Feature;

use App\Models\Shop;
use App\Models\ShopVisit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FieldOrderSelectTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    public function test_salesman_can_select_a_shop_and_start_an_order(): void
    {
        $salesman = User::where('email', 'karim@field.local')->firstOrFail();
        $shop = Shop::query()
            ->where('assigned_salesman_id', $salesman->id)
            ->where('status', Shop::STATUS_ACTIVE)
            ->firstOrFail();

        ShopVisit::query()
            ->where('salesman_id', $salesman->id)
            ->whereNull('checked_out_at')
            ->update(['checked_out_at' => now()]);

        $this->actingAs($salesman)
            ->get(route('field.orders.create'))
            ->assertOk()
            ->assertSee('Select any active shop from admin')
            ->assertSee($shop->name)
            ->assertSee('Order on behalf of shop');

        $this->actingAs($salesman)
            ->post(route('field.shops.order', $shop))
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $visit = ShopVisit::query()
            ->where('salesman_id', $salesman->id)
            ->where('shop_id', $shop->id)
            ->whereNull('checked_out_at')
            ->latest('id')
            ->first();

        $this->assertNotNull($visit);
        $this->assertSame('Order collection', $visit->purpose);

        $this->actingAs($salesman)
            ->get(route('field.visit.show', $visit))
            ->assertOk()
            ->assertSee($shop->name);
    }

    public function test_place_order_continues_open_visit_at_same_shop(): void
    {
        $salesman = User::where('email', 'karim@field.local')->firstOrFail();
        $shop = Shop::query()
            ->where('assigned_salesman_id', $salesman->id)
            ->where('status', Shop::STATUS_ACTIVE)
            ->firstOrFail();

        ShopVisit::query()
            ->where('salesman_id', $salesman->id)
            ->whereNull('checked_out_at')
            ->update(['checked_out_at' => now()]);

        $this->actingAs($salesman)->post(route('field.shops.order', $shop))->assertSessionHasNoErrors();
        $visit = ShopVisit::query()->where('salesman_id', $salesman->id)->whereNull('checked_out_at')->firstOrFail();

        $this->actingAs($salesman)
            ->post(route('field.shops.order', $shop))
            ->assertRedirect(route('field.visit.show', $visit));

        $this->assertSame(1, ShopVisit::query()->where('salesman_id', $salesman->id)->whereNull('checked_out_at')->count());
    }

    public function test_starting_order_at_another_shop_closes_the_open_visit(): void
    {
        $salesman = User::where('email', 'karim@field.local')->firstOrFail();
        $first = Shop::query()
            ->where('assigned_salesman_id', $salesman->id)
            ->where('status', Shop::STATUS_ACTIVE)
            ->firstOrFail();
        $second = Shop::query()->create([
            'code' => 'SHP-ORDER2',
            'name' => 'Second Order Shop',
            'owner_name' => 'Owner Two',
            'phone' => '01870001122',
            'status' => Shop::STATUS_ACTIVE,
            'assigned_salesman_id' => $salesman->id,
            'created_by' => $salesman->id,
            'credit_limit' => 50000,
            'approved_at' => now(),
        ]);

        ShopVisit::query()
            ->where('salesman_id', $salesman->id)
            ->whereNull('checked_out_at')
            ->update(['checked_out_at' => now()]);

        $this->actingAs($salesman)->post(route('field.shops.order', $first))->assertSessionHasNoErrors();
        $firstVisit = ShopVisit::query()
            ->where('salesman_id', $salesman->id)
            ->where('shop_id', $first->id)
            ->whereNull('checked_out_at')
            ->firstOrFail();

        $this->actingAs($salesman)
            ->post(route('field.shops.order', $second))
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $this->assertNotNull($firstVisit->fresh()->checked_out_at);
        $this->assertSame(ShopVisit::OUTCOME_FOLLOW_UP, $firstVisit->fresh()->outcome);
        $this->assertTrue(
            ShopVisit::query()
                ->where('salesman_id', $salesman->id)
                ->where('shop_id', $second->id)
                ->whereNull('checked_out_at')
                ->exists()
        );
    }

    public function test_salesman_can_order_on_behalf_of_unassigned_admin_shop(): void
    {
        $salesman = User::where('email', 'karim@field.local')->firstOrFail();
        $shop = Shop::query()->create([
            'code' => 'SHP-ADMIN1',
            'name' => 'Admin Created Shop',
            'owner_name' => 'Office Owner',
            'phone' => '01870009988',
            'status' => Shop::STATUS_ACTIVE,
            'assigned_salesman_id' => null,
            'credit_limit' => 100000,
            'approved_at' => now(),
        ]);

        ShopVisit::query()
            ->where('salesman_id', $salesman->id)
            ->whereNull('checked_out_at')
            ->update(['checked_out_at' => now()]);

        $this->actingAs($salesman)
            ->get(route('field.orders.create'))
            ->assertOk()
            ->assertSee('Admin Created Shop');

        $this->actingAs($salesman)
            ->post(route('field.shops.order', $shop))
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $this->assertSame($salesman->id, $shop->fresh()->assigned_salesman_id);
        $this->assertTrue(
            ShopVisit::query()
                ->where('salesman_id', $salesman->id)
                ->where('shop_id', $shop->id)
                ->whereNull('checked_out_at')
                ->exists()
        );
    }

    public function test_any_salesman_can_order_on_behalf_of_another_salesmans_shop(): void
    {
        $karim = User::where('email', 'karim@field.local')->firstOrFail();
        $sabrina = User::where('email', 'sabrina@field.local')->firstOrFail();
        $shop = Shop::query()
            ->where('assigned_salesman_id', $karim->id)
            ->where('status', Shop::STATUS_ACTIVE)
            ->firstOrFail();

        ShopVisit::query()
            ->where('salesman_id', $sabrina->id)
            ->whereNull('checked_out_at')
            ->update(['checked_out_at' => now()]);

        $this->actingAs($sabrina)
            ->get(route('field.orders.create'))
            ->assertOk()
            ->assertSee($shop->name);

        $this->actingAs($sabrina)
            ->post(route('field.shops.order', $shop))
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        // Shop stays on Karim's route; Sabrina still took the order visit.
        $this->assertSame($karim->id, $shop->fresh()->assigned_salesman_id);
        $this->assertTrue(
            ShopVisit::query()
                ->where('salesman_id', $sabrina->id)
                ->where('shop_id', $shop->id)
                ->whereNull('checked_out_at')
                ->exists()
        );
    }
}
