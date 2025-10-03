<?php

namespace Tests\Feature;

use App\Models\Apartment;
use App\Models\ApartmentPrice;
use App\Models\City;
use App\Models\Property;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\AdminUserSeeder;
use Database\Seeders\CountrySeeder;
use Database\Seeders\CitySeeder;
use Database\Seeders\GeoobjectSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class ApartmentPriceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([
            RoleSeeder::class,
            PermissionSeeder::class,
            AdminUserSeeder::class,
            CountrySeeder::class,
            CitySeeder::class,
            GeoobjectSeeder::class,
        ]);
    }
    private function create_apartment(): Apartment
    {
        $owner = User::factory()->create(['role_id' => Role::ROLE_OWNER]);
        $cityId = City::value('id');
        $property = Property::factory()->create([
            'owner_id' => $owner->id,
            'city_id' => $cityId,
        ]);

        return Apartment::create([
            'name' => 'Apartment',
            'property_id' => $property->id,
            'capacity_adults' => 3,
            'capacity_children' => 2,
        ]);
    }

    public function test_apartment_calculate_price_1_day_correctly()
    {
        $apartment = $this->create_apartment();
        ApartmentPrice::create([
            'apartment_id' => $apartment->id,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(10)->toDateString(),
            'price' => 100
        ]);

        $totalPrice = $apartment->calculatePriceForDates(
            now()->toDateString(),
            now()->toDateString()
        );
        $this->assertEquals(100, $totalPrice);
    }

    public function test_apartment_calculate_price_2_days_correctly()
    {
        $apartment = $this->create_apartment();
        $apt = ApartmentPrice::create([
            'apartment_id' => $apartment->id,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(10)->toDateString(),
            'price' => 100
        ]);

        $totalPrice = $apartment->calculatePriceForDates(
            now()->toDateString(),
            now()->addDay()->toDateString()
        );
        $this->assertEquals(200, $totalPrice);
    }

    public function test_apartment_calculate_price_multiple_ranges_correctly()
    {
        $apartment = $this->create_apartment();
        ApartmentPrice::create([
            'apartment_id' => $apartment->id,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(2)->toDateString(),
            'price' => 100
        ]);
        ApartmentPrice::create([
            'apartment_id' => $apartment->id,
            'start_date' => now()->addDays(3)->toDateString(),
            'end_date' => now()->addDays(10)->toDateString(),
            'price' => 90
        ]);

        $totalPrice = $apartment->calculatePriceForDates(
            now()->toDateString(),
            now()->addDays(4)->toDateString()
        );
        $this->assertEquals(3 * 100 + 2 * 90, $totalPrice);
    }

    public function test_apartment_calculate_price_multiple_ranges_correctly_more_complex_version()
    {
        $apartment = $this->create_apartment();
        ApartmentPrice::create([
            'apartment_id' => $apartment->id,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(2)->toDateString(),
            'price' => 100
        ]);
        ApartmentPrice::create([
            'apartment_id' => $apartment->id,
            'start_date' => now()->addDays(3)->toDateString(),
            'end_date' => now()->addDays(6)->toDateString(),
            'price' => 90
        ]);
        ApartmentPrice::create([
            'apartment_id' => $apartment->id,
            'start_date' => now()->addDays(7)->toDateString(),
            'end_date' => now()->addDays(10)->toDateString(),
            'price' => 120
        ]);

        $totalPrice = $apartment->calculatePriceForDates(
            now()->addDays(2)->toDateString(),
            now()->addDays(8)->toDateString()
        );

        $this->assertEquals(1 * 100 + 4 * 90 + 2 * 120, $totalPrice);
    }


    public function test_property_search_filters_by_price()
    {
        $owner = User::factory()->create(['role_id' => Role::ROLE_OWNER]);
        $cityId = City::value('id');
        $property = Property::factory()->create([
            'owner_id' => $owner->id,
            'city_id' => $cityId,
        ]);
        $cheapApartment = Apartment::factory()->create([
            'name' => 'Cheap apartment',
            'property_id' => $property->id,
            'capacity_adults' => 2,
            'capacity_children' => 1,
        ]);
        $cheapApartment->prices()->create([
            'start_date' => now(),
            'end_date' => now()->addMonth(),
            'price' => 70,
        ]);
        $property2 = Property::factory()->create([
            'owner_id' => $owner->id,
            'city_id' => $cityId,
        ]);
        $expensiveApartment = Apartment::factory()->create([
            'name' => 'Mid size apartment',
            'property_id' => $property2->id,
            'capacity_adults' => 2,
            'capacity_children' => 1,
        ]);
        $expensiveApartment->prices()->create([
            'start_date' => now(),
            'end_date' => now()->addMonth(),
            'price' => 130,
        ]);

        // First case - no price range: both returned
        $response = $this->getJson('/api/search?city=' . $cityId . '&adults=2&children=1');
        $response->assertStatus(200);
        $response->assertJsonCount(2, 'properties');

        // First case - min price set: 1 returned
        $response = $this->getJson('/api/search?city=' . $cityId . '&adults=2&children=1&price_from=100');
        $response->assertStatus(200);
        $response->assertJsonCount(1, 'properties');
        $response->assertJsonFragment(['id' => $property2->id]);

        // Second case - max price set: 1 returned
        $response = $this->getJson('/api/search?city=' . $cityId . '&adults=2&children=1&price_to=100');
        $response->assertStatus(200);
        $response->assertJsonCount(1, 'properties');
        $response->assertJsonFragment(['id' => $property->id]);

        // Third case - both min and max price set: 2 returned
        $response = $this->getJson('/api/search?city=' . $cityId . '&adults=2&children=1&price_from=50&price_to=150');
        $response->assertStatus(200);
        $response->assertJsonCount(2, 'properties');

        // Fourth case - both min and max price set narrow: 0 returned
        $response = $this->getJson('/api/search?city=' . $cityId . '&adults=2&children=1&price_from=80&price_to=100');
        $response->assertStatus(200);
        $response->assertJsonCount(0, 'properties');
    }
}
