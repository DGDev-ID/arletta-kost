<?php

namespace Database\Seeders;

use App\Models\Room;
use App\Models\RoomCategory;
use Illuminate\Database\Seeder;

class RoomSeeder extends Seeder
{
    public function run(): void
    {
        $categories = RoomCategory::all();

        foreach ($categories as $category) {
            $roomCount = $category->name === 'Standard' ? 10 : 5;

            for ($i = 1; $i <= $roomCount; $i++) {
                $prefix = strtoupper(substr($category->name, 0, 1));
                Room::create([
                    'room_category_id' => $category->id,
                    'room_number' => $prefix . str_pad($i, 3, '0', STR_PAD_LEFT),
                    'status' => $i <= 2 ? 'occupied' : ($i === $roomCount ? 'maintenance' : 'available'),
                ]);
            }
        }
    }
}
