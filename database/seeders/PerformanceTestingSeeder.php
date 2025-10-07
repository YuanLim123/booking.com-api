<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Laravel\Telescope\Telescope;

class PerformanceTestingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Telescope::stopRecording();

        $this->call([
            RoleSeeder::class,
            AdminUserSeeder::class,
            PermissionSeeder::class
        ]);

        $this->callWith(Performance\UserSeeder::class, [
            'owners' => 1000,
            'users' => 1000
        ]);
        $this->callWith(Performance\CountrySeeder::class, [
            'count' => 100
        ]);
        $this->callWith(Performance\CitySeeder::class, [
            'count' => 1000
        ]);
        $this->callWith(Performance\GeoobjectSeeder::class, [
            'count' => 1000
        ]);
        $this->callWith(Performance\PropertySeeder::class, [
            'count' => 1000
        ]);
        $this->callWith(Performance\ApartmentSeeder::class, [
            'count' => 2000
        ]);
        $this->callWith(Performance\BookingSeeder::class, [
            'withRatings' => 2000,
            'withoutRatings' => 2000
        ]);
    }
}
