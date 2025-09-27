<?php

namespace Database\Factories;


use App\Models\Geoobject;
use App\Models\City;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Property>
 */
class PropertyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $geoobject = Geoobject::first();

        return [
            'owner_id' => User::where('role_id', Role::ROLE_OWNER)->value('id'), // value('id') is a shorter way of doing ->first()->id.
            'name' => fake()->text(20),
            'city_id' => City::value('id'),
            'address_street' => fake()->streetAddress(),
            'address_postcode' => fake()->postcode(),
            'lat' => $geoobject->lat,
            'long' => $geoobject->long,
        ];
    }
}
