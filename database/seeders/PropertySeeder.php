<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Role;
use App\Models\Property;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PropertySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $owner = User::factory()->create([
            'role_id' => Role::ROLE_OWNER,
        ]);

        Property::factory()->count(2)->create([
            'owner_id' => $owner->id,
        ]);
    }
}
