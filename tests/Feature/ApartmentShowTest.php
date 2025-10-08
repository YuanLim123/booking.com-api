<?php

namespace Tests\Feature;

use App\Models\Apartment;
use App\Models\City;
use App\Models\Facility;
use App\Models\FacilityCategory;
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

class ApartmentShowTest extends TestCase
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

    public function test_apartment_show_loads_apartment_with_facilities()
    {
        $owner = User::factory()->owner()->create();
        $cityId = City::value('id');
        $property = Property::factory()->create([
            'owner_id' => $owner->id,
            'city_id' => $cityId,
        ]);
        $apartment = Apartment::factory()->create([
            'name' => 'Large apartment',
            'property_id' => $property->id,
            'capacity_adults' => 3,
            'capacity_children' => 2,
        ]);

        $firstCategory = FacilityCategory::create([
            'name' => 'First category'
        ]);
        $secondCategory = FacilityCategory::create([
            'name' => 'Second category'
        ]);
        $firstFacility = Facility::create([
            'category_id' => $firstCategory->id,
            'name' => 'First facility'
        ]);
        $secondFacility = Facility::create([
            'category_id' => $firstCategory->id,
            'name' => 'Second facility'
        ]);
        $thirdFacility = Facility::create([
            'category_id' => $secondCategory->id,
            'name' => 'Third facility'
        ]);
        $apartment->facilities()->attach([
            $firstFacility->id,
            $secondFacility->id,
            $thirdFacility->id
        ]);

        $response = $this->getJson('/api/apartments/' . $apartment->id);
        $response->assertStatus(200);
        $response->assertJsonPath('name', $apartment->name);
        $response->assertJsonCount(2, 'facility_categories');

        $expectedFacilityArray = [
            'facility_categories' => [
                $firstCategory->name => [
                    $firstFacility->name,
                    $secondFacility->name
                ],
                $secondCategory->name => [
                    $thirdFacility->name
                ]
            ]

        ];
        $response->assertJsonFragment($expectedFacilityArray);
    }
}
