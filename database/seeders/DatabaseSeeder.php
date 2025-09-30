<?php

namespace Database\Seeders;

use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\CountrySeeder;
use Database\Seeders\CitySeeder;
use Database\Seeders\GeoobjectSeeder;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            PermissionSeeder::class,
            AdminUserSeeder::class,

            CountrySeeder::class,
            CitySeeder::class,
            GeoobjectSeeder::class,

            FacilityCategorySeeder::class,
            FacilitySeeder::class,
            
            PropertySeeder::class,
            ApartmentSeeder::class,
            RoomSeeder::class,
        ]);

    }
}
