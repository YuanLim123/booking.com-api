<?php

namespace Tests\Feature;

use App\Models\Geoobject;
use App\Models\City;
use App\Models\Country;
use App\Models\Property;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PropertySearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_property_search_by_city_returns_correct_results(): void
    {
        $owner = User::factory()->create(['role_id' => Role::ROLE_OWNER]);
        $cities = City::take(2)->pluck('id');
        $propertyInCity = Property::factory()->create(['owner_id' => $owner->id, 'city_id' => $cities[0]]);
        $propertyInAnotherCity = Property::factory()->create(['owner_id' => $owner->id, 'city_id' => $cities[1]]);

        $response = $this->getJson('/api/search?city=' . $cities[0]);

        $response->assertStatus(200);
        $response->assertJsonCount(1);
        $response->assertJsonFragment(['id' => $propertyInCity->id]);
    }

    public function test_property_search_by_country_returns_correct_results(): void
    {
        $owner = User::factory()->create(['role_id' => Role::ROLE_OWNER]);
        $countries = Country::with('cities')->take(2)->get();
        $propertyInCountry = Property::factory()->create([
            'owner_id' => $owner->id,
            'city_id' => $countries[0]->cities()->value('id')
        ]);
        $propertyInAnotherCountry = Property::factory()->create([
            'owner_id' => $owner->id,
            'city_id' => $countries[1]->cities()->value('id')
        ]);

        $response = $this->getJson('/api/search?country=' . $countries[0]->id);

        $response->assertStatus(200);
        $response->assertJsonCount(1);
        $response->assertJsonFragment(['id' => $propertyInCountry->id]);
    }

    // sqlite does not support the acos function, so this test will not work with sqlite.
    // public function test_property_search_by_geoobject_returns_correct_results(): void
    // {
    //     $owner = User::factory()->create(['role_id' => Role::ROLE_OWNER]);
    //     $cityId = City::value('id');
    //     $geoobject = Geoobject::first();
    //     $propertyNear = Property::factory()->create([
    //         'owner_id' => $owner->id,
    //         'city_id' => $cityId,
    //         'lat' => $geoobject->lat,
    //         'long' => $geoobject->long,
    //     ]);
    //     $propertyFar = Property::factory()->create([
    //         'owner_id' => $owner->id,
    //         'city_id' => $cityId,
    //         'lat' => $geoobject->lat + 10,
    //         'long' => $geoobject->long - 10,
    //     ]);

    //     $response = $this->getJson('/api/search?geoobject=' . $geoobject->id);

    //     $response->assertStatus(200);
    //     $response->assertJsonCount(1);
    //     $response->assertJsonFragment(['id' => $propertyNear->id]);
    // }
}
