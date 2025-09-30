<?php

namespace Database\Seeders;

use App\Models\Apartment;
use App\Models\Facility;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ApartmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $facilities = Facility::take(3)->get();
        
        Apartment::factory()
            ->hasAttached($facilities)
            ->create();
    }
}
