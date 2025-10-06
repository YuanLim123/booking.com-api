<?php

namespace Tests\Feature;

use App\Models\Apartment;
use App\Models\Booking;
use App\Models\Bed;
use App\Models\BedType;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\Geoobject;
use App\Models\City;
use App\Models\Country;
use App\Models\Facility;
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
use Tests\TestCase;

class PropertySearchTest extends TestCase
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

    public function test_property_search_by_city_returns_correct_results(): void
    {
        $owner = User::factory()->create(['role_id' => Role::ROLE_OWNER]);
        $cities = City::take(2)->pluck('id');
        $propertyInCity = Property::factory()->create(['owner_id' => $owner->id, 'city_id' => $cities[0]]);
        $propertyInAnotherCity = Property::factory()->create(['owner_id' => $owner->id, 'city_id' => $cities[1]]);

        $response = $this->getJson('/api/search?city=' . $cities[0]);
        $response->assertStatus(200);
        $response->assertJsonCount(1, 'properties.data');
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
        $response->assertJsonCount(1, 'properties.data');
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

    public function test_property_search_beds_list_all_cases(): void
    {
        $owner = User::factory()->create(['role_id' => Role::ROLE_OWNER]);
        $cityId = City::value('id');
        $roomTypes = RoomType::all();
        $bedTypes = BedType::all();

        $property = Property::factory()->create([
            'owner_id' => $owner->id,
            'city_id' => $cityId,
        ]);
        $apartment = Apartment::factory()->create([
            'name' => 'Small apartment',
            'property_id' => $property->id,
            'capacity_adults' => 1,
            'capacity_children' => 0,
        ]);

        // ----------------------
        // FIRST: check that bed list if empty if no beds
        // ----------------------

        $response = $this->getJson('/api/search?city=' . $cityId);
        $response->assertStatus(200);
        $response->assertJsonCount(1, 'properties.data');
        $response->assertJsonCount(1, 'properties.data.0.apartments');
        $response->assertJsonPath('properties.data.0.apartments.0.beds_list', '');

        // ----------------------
        // SECOND: create 1 room with 1 bed
        // ----------------------

        $room = Room::create([
            'apartment_id' => $apartment->id,
            'room_type_id' => $roomTypes[0]->id,
            'name' => 'Bedroom',
        ]);
        Bed::create([
            'room_id' => $room->id,
            'bed_type_id' => $bedTypes[0]->id,
        ]);

        $response = $this->getJson('/api/search?city=' . $cityId);
        $response->assertStatus(200);
        $response->assertJsonPath('properties.data.0.apartments.0.beds_list', '1 ' . $bedTypes[0]->name);

        // ----------------------
        // THIRD: add another bed to the same room
        // ----------------------

        Bed::create([
            'room_id' => $room->id,
            'bed_type_id' => $bedTypes[0]->id,
        ]);
        $response = $this->getJson('/api/search?city=' . $cityId);
        $response->assertStatus(200);
        $response->assertJsonPath('properties.data.0.apartments.0.beds_list', '2 ' . str($bedTypes[0]->name)->plural());

        // ----------------------
        // FOURTH: add a second room with no beds
        // ----------------------

        $secondRoom = Room::create([
            'apartment_id' => $apartment->id,
            'room_type_id' => $roomTypes[0]->id,
            'name' => 'Living room',
        ]);
        $response = $this->getJson('/api/search?city=' . $cityId);
        $response->assertStatus(200);
        $response->assertJsonPath('properties.data.0.apartments.0.beds_list', '2 ' . str($bedTypes[0]->name)->plural());

        // ----------------------
        // FIFTH: add one bed to that second room
        // ----------------------

        Bed::create([
            'room_id' => $secondRoom->id,
            'bed_type_id' => $bedTypes[0]->id,
        ]);
        $response = $this->getJson('/api/search?city=' . $cityId);
        $response->assertStatus(200);
        $response->assertJsonPath('properties.data.0.apartments.0.beds_list', '3 ' . str($bedTypes[0]->name)->plural());

        // ----------------------
        // SIXTH: add another bed with a different type to that second room
        // ----------------------

        Bed::create([
            'room_id' => $secondRoom->id,
            'bed_type_id' => $bedTypes[1]->id,
        ]);
        $response = $this->getJson('/api/search?city=' . $cityId);
        $response->assertStatus(200);
        $response->assertJsonPath('properties.data.0.apartments.0.beds_list', '4 beds (3 ' . str($bedTypes[0]->name)->plural() . ', 1 ' . $bedTypes[1]->name . ')');

        // ----------------------
        // SEVENTH: add a second bed with that new type to that second room
        // ----------------------

        Bed::create([
            'room_id' => $secondRoom->id,
            'bed_type_id' => $bedTypes[1]->id,
        ]);
        $response = $this->getJson('/api/search?city=' . $cityId);
        $response->assertStatus(200);
        $response->assertJsonPath('properties.data.0.apartments.0.beds_list', '5 beds (3 ' . str($bedTypes[0]->name)->plural() . ', 2 ' . str($bedTypes[1]->name)->plural() . ')');

        // ----------------------
        // EIGHTH: add a third bed with a different type to that second room
        // ----------------------

        Bed::create([
            'room_id' => $secondRoom->id,
            'bed_type_id' => $bedTypes[2]->id,
        ]);
        $response = $this->getJson('/api/search?city=' . $cityId);
        $response->assertStatus(200);
        $response->assertJsonPath('properties.data.0.apartments.0.beds_list', '6 beds (3 ' . str($bedTypes[0]->name)->plural() . ', 2 ' . str($bedTypes[1]->name)->plural() . ', 1 ' . str($bedTypes[2]->name) . ')');

        // ----------------------
        // NINTH: add a third room with 1 bed
        // ----------------------
        $thirdRoom = Room::create([
            'apartment_id' => $apartment->id,
            'room_type_id' => $roomTypes[0]->id,
            'name' => 'Bedroom',
        ]);

        Bed::create([
            'room_id' => $thirdRoom->id,
            'bed_type_id' => $bedTypes[0]->id,
        ]);

        $response = $this->getJson('/api/search?city=' . $cityId);
        $response->assertStatus(200);
        $response->assertJsonPath('properties.data.0.apartments.0.beds_list', '7 beds (4 ' . str($bedTypes[0]->name)->plural() . ', 2 ' . str($bedTypes[1]->name)->plural() . ', 1 ' . str($bedTypes[2]->name) . ')');
    }

    public function test_property_search_returns_one_best_apartment_per_property()
    {
        $owner = User::factory()->create(['role_id' => Role::ROLE_OWNER]);
        $cityId = City::value('id');
        $property = Property::factory()->create([
            'owner_id' => $owner->id,
            'city_id' => $cityId,
        ]);
        $largeApartment = Apartment::factory()->create([
            'name' => 'Large apartment',
            'property_id' => $property->id,
            'capacity_adults' => 3,
            'capacity_children' => 2,
        ]);
        $midSizeApartment = Apartment::factory()->create([
            'name' => 'Mid size apartment',
            'property_id' => $property->id,
            'capacity_adults' => 2,
            'capacity_children' => 1,
        ]);
        $smallApartment = Apartment::factory()->create([
            'name' => 'Small apartment',
            'property_id' => $property->id,
            'capacity_adults' => 1,
            'capacity_children' => 0,
        ]);

        $property2 = Property::factory()->create([
            'owner_id' => $owner->id,
            'city_id' => $cityId,
        ]);
        Apartment::factory()->create([
            'name' => 'Large apartment 2',
            'property_id' => $property2->id,
            'capacity_adults' => 3,
            'capacity_children' => 2,
        ]);
        Apartment::factory()->create([
            'name' => 'Mid size apartment 2',
            'property_id' => $property2->id,
            'capacity_adults' => 2,
            'capacity_children' => 1,
        ]);
        Apartment::factory()->create([
            'name' => 'Small apartment 2',
            'property_id' => $property2->id,
            'capacity_adults' => 1,
            'capacity_children' => 0,
        ]);

        $response = $this->getJson('/api/search?city=' . $cityId . '&adults=2&children=1');

        $response->assertStatus(200);
        $response->assertJsonCount(2, 'properties.data');
        $response->assertJsonCount(1, 'properties.data.0.apartments');
        $response->assertJsonCount(1, 'properties.data.1.apartments');
        $response->assertJsonPath('properties.data.0.apartments.0.name', $midSizeApartment->name);
    }

    public function test_property_search_filters_by_facilities(): void
    {
        $owner = User::factory()->create(['role_id' => Role::ROLE_OWNER]);
        $cityId = City::value('id');
        $property = Property::factory()->create([
            'owner_id' => $owner->id,
            'city_id' => $cityId,
        ]);
        Apartment::factory()->create([
            'name' => 'Mid size apartment',
            'property_id' => $property->id,
            'capacity_adults' => 2,
            'capacity_children' => 1,
        ]);
        $property2 = Property::factory()->create([
            'owner_id' => $owner->id,
            'city_id' => $cityId,
        ]);
        Apartment::factory()->create([
            'name' => 'Mid size apartment',
            'property_id' => $property2->id,
            'capacity_adults' => 2,
            'capacity_children' => 1,
        ]);

        // First case -no facilities exist in query string
        $response = $this->getJson('/api/search?city=' . $cityId . '&adults=2&children=1');
        $response->assertStatus(200);
        $response->assertJsonCount(2, 'properties.data');

        // Second case -filter by facility, 0 properties returned
        $facility = Facility::create(['name' => 'Test facility']);
        $response = $this->getJson('/api/search?city=' . $cityId . '&adults=2&children=1' . '&facilities[]=' . $facility->id);
        $response->assertStatus(200);
        $response->assertJsonCount(0, 'properties.data');

        // Third case - attach facility to one property, filter by facility, 1 property returned
        $property->facilities()->attach($facility->id);
        $response = $this->getJson('/api/search?city=' . $cityId . '&adults=2&children=1' . '&facilities[]=' . $facility->id);
        $response->assertStatus(200);
        $response->assertJsonCount(1, 'properties.data');
        $response->assertJsonPath('properties.data.0.id', $property->id);

        // Fourth case - attach facility to a DIFFERENT property, filter by facility, 2 properties returned
        $property2->facilities()->attach($facility->id);
        $response = $this->getJson('/api/search?city=' . $cityId . '&adults=2&children=1' . '&facilities[]=' . $facility->id);
        $response->assertStatus(200);
        $response->assertJsonCount(2, 'properties.data');
        $response->assertJsonPath('properties.data.0.id', $property->id);
        $response->assertJsonPath('properties.data.1.id', $property2->id);
    }

    public function test_property_search_return_most_popular_property_facilities_count_in_desc_order(): void
    {
        $owner = User::factory()->create(['role_id' => Role::ROLE_OWNER]);
        $cityId = City::value('id');
        $property = Property::factory()->create([
            'owner_id' => $owner->id,
            'city_id' => $cityId,
        ]);
        $property2 = Property::factory()->create([
            'owner_id' => $owner->id,
            'city_id' => $cityId,
        ]);
        $property3 = Property::factory()->create([
            'owner_id' => $owner->id,
            'city_id' => $cityId,
        ]);

        $facility1 = Facility::create(['name' => 'Test facility 1']);
        $facility2 = Facility::create(['name' => 'Test facility 2']);
        $facility3 = Facility::create(['name' => 'Test facility 3']);

        // First case - no facilities attached to any property, no facilities returned
        $response = $this->getJson('/api/search?city=' . $cityId);
        $response->assertStatus(200);
        $response->assertJsonCount(0, 'facilities');

        // Second case - attach facility to one property, 1 facility returned
        $property->facilities()->attach($facility1->id);
        $response = $this->getJson('/api/search?city=' . $cityId);
        $response->assertStatus(200);
        $response->assertJsonCount(1, 'facilities');

        // Third case - attach same facility to different property, 1 facility return with 2 counts
        $property2->facilities()->attach($facility1->id);
        $response = $this->getJson('/api/search?city=' . $cityId);
        $response->assertStatus(200);
        $response->assertJsonCount(1, 'facilities');
        $response->assertJsonFragment([
            $facility1->name => 2,
        ]);

        // Forth case - attach facility2 to  property1, 2 facility return with facility 1 then facility 2 (desc order)
        $property->facilities()->attach($facility2->id);
        $response = $this->getJson('/api/search?city=' . $cityId);
        $response->assertStatus(200);
        $response->assertJsonCount(2, 'facilities');
        $response->assertJsonFragment([
            $facility1->name => 2,
            $facility2->name => 1,
        ]);

        // Fifth case - attach facility3 to property3, 3 facility return with facility 1 then facility 2 then facility 3 (desc order)
        $property3->facilities()->attach($facility3->id);
        $response = $this->getJson('/api/search?city=' . $cityId);
        $response->assertStatus(200);
        $response->assertJsonCount(3, 'facilities');
        $response->assertJsonFragment([
            $facility1->name => 2,
            $facility2->name => 1,
            $facility3->name => 1,
        ]);

        // Sixth case - attach facility1 to property3, attach facility2 to property3, 3 facility return with facility1->3, facility2->2, facility3->1 in desc order
        $property3->facilities()->attach($facility1->id);
        $property3->facilities()->attach($facility2->id);
        $response = $this->getJson('/api/search?city=' . $cityId);
        $response->assertStatus(200);
        $response->assertJsonCount(3, 'facilities');
        $response->assertJsonFragment([
            $facility1->name => 3,
            $facility2->name => 2,
            $facility3->name => 1,
        ]);
    }

    public function test_properties_show_correct_rating_and_ordered_by_it()
    {
        $owner = User::factory()->create(['role_id' => Role::ROLE_OWNER]);
        $cityId = City::value('id');
        $property = Property::factory()->create([
            'owner_id' => $owner->id,
            'city_id' => $cityId,
        ]);
        $apartment1 = Apartment::factory()->create([
            'name' => 'Cheap apartment',
            'property_id' => $property->id,
            'capacity_adults' => 2,
            'capacity_children' => 1,
        ]);
        $property2 = Property::factory()->create([
            'owner_id' => $owner->id,
            'city_id' => $cityId,
        ]);
        $apartment2 = Apartment::factory()->create([
            'name' => 'Mid size apartment',
            'property_id' => $property2->id,
            'capacity_adults' => 2,
            'capacity_children' => 1,
        ]);
        $apartment3 = Apartment::factory()->create([
            'name' => 'Premier size apartment',
            'property_id' => $property2->id,
            'capacity_adults' => 2,
            'capacity_children' => 1,
        ]);
        $user1 = User::factory()->create(['role_id' => Role::ROLE_USER]);
        $user2 = User::factory()->create(['role_id' => Role::ROLE_USER]);
        Booking::create([
            'apartment_id' => $apartment1->id,
            'user_id' => $user1->id,
            'start_date' => now()->addDay(),
            'end_date' => now()->addDays(2),
            'guests_adults' => 1,
            'guests_children' => 0,
            'rating' => 7
        ]);
        Booking::create([
            'apartment_id' => $apartment2->id,
            'user_id' => $user1->id,
            'start_date' => now()->addDay(),
            'end_date' => now()->addDays(2),
            'guests_adults' => 1,
            'guests_children' => 0,
            'rating' => 9
        ]);
        Booking::create([
            'apartment_id' => $apartment2->id,
            'user_id' => $user2->id,
            'start_date' => now()->addDay(),
            'end_date' => now()->addDays(2),
            'guests_adults' => 1,
            'guests_children' => 0,
            'rating' => 7
        ]);
        Booking::create([
            'apartment_id' => $apartment3->id,
            'user_id' => $user2->id,
            'start_date' => now()->addDay(4),
            'end_date' => now()->addDays(7),
            'guests_adults' => 1,
            'guests_children' => 0,
            'rating' => 8
        ]);

        $response = $this->getJson('/api/search?city=' . $cityId . '&adults=2&children=1');
        $response->assertStatus(200);
        $response->assertJsonCount(2, 'properties.data');
        
        $this->assertEquals(8, $response->json('properties')['data'][0]['avg_rating']);
        $this->assertEquals(7, $response->json('properties')['data'][1]['avg_rating']);
    }
}
