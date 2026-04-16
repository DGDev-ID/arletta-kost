<?php

namespace Database\Seeders;

use App\Models\Room;
use App\Models\Tenant;
use Illuminate\Database\Seeder;
use Faker\Factory as Faker;

class TenantSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create('id_ID');
        $occupiedRooms = Room::where('status', 'occupied')->get();

        foreach ($occupiedRooms as $room) {
            $tenant = Tenant::create([
                'room_id' => $room->id,
                'email' => $faker->unique()->safeEmail(),
                'name' => $faker->name(),
                'nik' => $faker->numerify('################'),
                'ktp_number' => $faker->numerify('################'),
                'birth_place' => $faker->city(),
                'birth_date' => $faker->dateTimeBetween('-35 years', '-18 years')->format('Y-m-d'),
                'gender' => $faker->randomElement(['male', 'female']),
                'address' => $faker->address(),
                'phone_number' => $faker->phoneNumber(),
            ]);

            // Also attach via pivot table
            $tenant->rooms()->attach($room->id);
        }
    }
}
