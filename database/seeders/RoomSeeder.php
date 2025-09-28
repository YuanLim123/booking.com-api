<?php

namespace Database\Seeders;

use App\Models\RoomType;
use App\Models\Apartment;
use App\Models\Room;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RoomSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $room1 = Room::create([
            'name' => 'Bedroom',
            'apartment_id' => Apartment::first()->id,
            'room_type_id' => RoomType::find(1)->id,
        ]);

        $room2 = Room::create([
            'name' => 'Living Room',
            'apartment_id' => Apartment::first()->id,
            'room_type_id' => RoomType::find(1)->id,
        ]);

        $room1->beds()->createMany([
            ['bed_type_id' => 1],
            ['bed_type_id' => 2],
        ]);
    }
}
