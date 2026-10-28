<?php

namespace Tests\Feature;

use App\Models\Shop;
use App\Models\ShopVisit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FieldVisitLeaveTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    public function test_back_without_order_stores_and_closes_the_visit(): void
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

        $this->actingAs($salesman)
            ->get(route('field.visit.show', $visit))
            ->assertOk()
            ->assertSee(route('field.visit.leave', $visit), false);

        $this->actingAs($salesman)
            ->post(route('field.visit.leave', $visit), [
                'outcome' => ShopVisit::OUTCOME_NO_ORDER,
                'redirect' => 'shops',
            ])
            ->assertRedirect(route('field.shops'))
            ->assertSessionHas('success');

        $visit->refresh();
        $this->assertNotNull($visit->checked_out_at);
        $this->assertSame(ShopVisit::OUTCOME_NO_ORDER, $visit->outcome);
        $this->assertFalse(
            ShopVisit::query()
                ->where('salesman_id', $salesman->id)
                ->whereNull('checked_out_at')
                ->exists()
        );
    }

    public function test_leave_is_safe_when_visit_already_closed(): void
    {
        $salesman = User::where('email', 'karim@field.local')->firstOrFail();
        $shop = Shop::query()->where('status', Shop::STATUS_ACTIVE)->firstOrFail();

        $visit = ShopVisit::query()->create([
            'shop_id' => $shop->id,
            'salesman_id' => $salesman->id,
            'checked_in_at' => now()->subHour(),
            'checked_out_at' => now()->subMinutes(10),
            'outcome' => ShopVisit::OUTCOME_FOLLOW_UP,
        ]);

        $this->actingAs($salesman)
            ->post(route('field.visit.leave', $visit), ['redirect' => 'dashboard'])
            ->assertRedirect(route('field.dashboard'));

        $this->assertSame(ShopVisit::OUTCOME_FOLLOW_UP, $visit->fresh()->outcome);
    }
}
