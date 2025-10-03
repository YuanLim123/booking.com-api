<?php

namespace Database\Seeders;

use App\Models\ApartmentPrice;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ApartmentPriceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        ApartmentPrice::create([
            'apartment_id' => 1,
            'start_date' => '2023-01-10',
            'end_date' => '2023-10-01',
            'price' => 100,
        ]);

        ApartmentPrice::create([
            'apartment_id' => 1,
            'start_date' => '2023-10-02',
            'end_date' => '2024-01-01',
            'price' => 90,
        ]);
    }
}
