<?php

namespace Tests\Feature;

use App\Models\Shop;
use App\Models\User;
use App\Services\BangladeshGeoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class BangladeshGeoTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        Http::fake([
            'huggingface.co/*' => Http::response($this->csv(), 200, ['Content-Type' => 'text/plain']),
            BangladeshGeoService::SOURCE_URL => Http::response($this->csv(), 200, ['Content-Type' => 'text/plain']),
            BangladeshGeoService::POSTAL_URL => Http::response($this->postalJson()),
            BangladeshGeoService::POSTAL_FALLBACK_URL => Http::response($this->postalJson()),
        ]);
    }

    public function test_geo_endpoints_cascade_division_district_thana_post_office_and_location(): void
    {
        $salesman = User::where('email', 'karim@field.local')->firstOrFail();
        Shop::query()->create([
            'code' => 'GEO-1',
            'name' => 'Mirpur 10 Mart',
            'owner_name' => 'Owner',
            'phone' => '01811000001',
            'division' => 'Dhaka',
            'district' => 'Dhaka',
            'thana' => 'Mirpur',
            'post_office' => 'Mirpur',
            'location' => 'Mirpur 10',
            'city' => 'Mirpur',
            'status' => Shop::STATUS_ACTIVE,
        ]);

        $this->actingAs($salesman)
            ->getJson(route('field.geo.divisions'))
            ->assertOk()
            ->assertJsonPath('data.0', 'Chattogram')
            ->assertJsonPath('data.1', 'Dhaka');

        $this->actingAs($salesman)
            ->getJson(route('field.geo.districts', ['division' => 'Dhaka']))
            ->assertOk()
            ->assertJsonPath('data', ['Dhaka']);

        $thanas = $this->actingAs($salesman)
            ->getJson(route('field.geo.thanas', ['district' => 'Dhaka']))
            ->assertOk()
            ->json('data');

        $this->assertContains('Dakshin Khan', $thanas);
        $this->assertContains('Uttarkhan', $thanas);
        $this->assertContains('Mirpur', $thanas);
        $this->assertContains('Gulshan', $thanas);

        $this->actingAs($salesman)
            ->getJson(route('field.geo.post-offices', ['district' => 'Dhaka', 'thana' => 'Uttarkhan']))
            ->assertOk()
            ->assertJsonFragment(['name' => 'Uttarkhan', 'postcode' => '1230']);

        $this->actingAs($salesman)
            ->getJson(route('field.geo.post-offices', ['district' => 'Dhaka', 'thana' => 'Dakshin Khan']))
            ->assertOk()
            ->assertJsonFragment(['name' => 'Dakshin Khan', 'postcode' => '1230']);

        $locations = $this->actingAs($salesman)
            ->getJson(route('field.geo.locations', ['thana' => 'Mirpur', 'post_office' => 'Mirpur']))
            ->assertOk()
            ->json('data');

        $this->assertContains('Mirpur', $locations);
        $this->assertContains('Mirpur 10', $locations);
    }

    public function test_districts_require_a_division(): void
    {
        $salesman = User::where('email', 'karim@field.local')->firstOrFail();

        $this->actingAs($salesman)
            ->getJson(route('field.geo.districts'))
            ->assertOk()
            ->assertExactJson(['data' => []]);
    }

    public function test_thanas_require_a_district(): void
    {
        $salesman = User::where('email', 'karim@field.local')->firstOrFail();

        $this->actingAs($salesman)
            ->getJson(route('field.geo.thanas'))
            ->assertOk()
            ->assertExactJson(['data' => []]);
    }

    private function csv(): string
    {
        $header = 'Division_ID,Division_Name,District_ID,District_Name,District_Name_BN,Thana_ID,Thana_Name,Thana_Name_BN,PostOffice_Name,PostOffice_Name_BN,Office_Type,PostCode,Slug';

        return implode("\n", [
            $header,
            '1,Dhaka,1,Dhaka,Dhaka,25,Mirpur,Mirpur,Mirpur,Mirpur,TSO,1216,mirpur-tso-1216',
            '1,Dhaka,1,Dhaka,Dhaka,26,Gulshan,Gulshan,Gulshan SO,Gulshan,SO,1212,gulshan-so-1212',
            '1,Dhaka,1,Dhaka,Dhaka,36,Uttarkhan,Uttarkhan,Uttarkhan,Uttarkhan,EDBO,1230,uttarkhan-edbo-1230',
            '1,Dhaka,1,Dhaka,Dhaka,36,Uttarkhan,Uttarkhan,Uttarkhan Mazar,Uttarkhan Mazar,EDBO,1230,uttarkhan-mazar-edbo-1230',
            '1,Dhaka,1,Dhaka,Dhaka,39,Dakshin Khan,Dakshin Khan,Dakshin Khan,Dakshin Khan,EDBO,1230,dakshin-khan-edbo-1230',
            '2,Chattogram,15,Chattogram,Chattogram,1,Kotwali,Kotwali,Chittagong GPO,GPO,HO,4000,ctg-gpo-4000',
        ]);
    }

    /** @return array<string, array<string, mixed>> */
    private function postalJson(): array
    {
        return [
            '1216' => ['division' => 'Dhaka', 'district' => 'Dhaka', 'thana' => 'Mirpur', 'suboffice' => 'Mirpur TSO', 'postcode' => 1216],
        ];
    }
}
