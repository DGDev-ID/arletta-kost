<?php

namespace Database\Seeders;

use App\Models\Kost;
use App\Models\RoomCategory;
use App\Models\RoomCategoryDetail;
use App\Models\RoomCategoryImage;
use App\Models\RoomPricing;
use Illuminate\Database\Seeder;

class RoomCategorySeeder extends Seeder
{
    public function run(): void
    {
        $kost = Kost::first();

        $categories = [
            [
                'name' => 'Standard',
                'description' => 'Kamar standar dengan fasilitas dasar. Cocok untuk mahasiswa.',
                'details' => [
                    ['detail' => 'AC', 'icon' => 'snowflake'],
                    ['detail' => 'WiFi', 'icon' => 'wifi'],
                    ['detail' => 'Kamar Mandi Dalam', 'icon' => 'bath'],
                ],
                'pricings' => [
                    ['duration_days' => 30, 'price' => 1500000],
                    ['duration_days' => 90, 'price' => 4200000],
                    ['duration_days' => 365, 'price' => 15000000],
                ],
                'images' => [
                    'https://placehold.co/600x400?text=Standard+Room+1',
                    'https://placehold.co/600x400?text=Standard+Room+2',
                ],
            ],
            [
                'name' => 'Deluxe',
                'description' => 'Kamar deluxe dengan fasilitas premium. Cocok untuk pekerja.',
                'details' => [
                    ['detail' => 'AC', 'icon' => 'snowflake'],
                    ['detail' => 'WiFi', 'icon' => 'wifi'],
                    ['detail' => 'Kamar Mandi Dalam', 'icon' => 'bath'],
                    ['detail' => 'TV LED 32"', 'icon' => 'tv'],
                    ['detail' => 'Meja Kerja', 'icon' => 'desk'],
                ],
                'pricings' => [
                    ['duration_days' => 30, 'price' => 2500000],
                    ['duration_days' => 90, 'price' => 7000000],
                    ['duration_days' => 365, 'price' => 25000000],
                ],
                'images' => [
                    'https://placehold.co/600x400?text=Deluxe+Room+1',
                    'https://placehold.co/600x400?text=Deluxe+Room+2',
                ],
            ],
        ];

        foreach ($categories as $categoryData) {
            $category = RoomCategory::create([
                'kost_id' => $kost->id,
                'name' => $categoryData['name'],
                'description' => $categoryData['description'],
            ]);

            foreach ($categoryData['details'] as $detail) {
                RoomCategoryDetail::create([
                    'room_category_id' => $category->id,
                    'detail' => $detail['detail'],
                    'icon' => $detail['icon'],
                ]);
            }

            foreach ($categoryData['pricings'] as $pricing) {
                RoomPricing::create([
                    'room_category_id' => $category->id,
                    'duration_days' => $pricing['duration_days'],
                    'price' => $pricing['price'],
                ]);
            }

            foreach ($categoryData['images'] as $imgUrl) {
                RoomCategoryImage::create([
                    'room_category_id' => $category->id,
                    'img_url' => $imgUrl,
                ]);
            }
        }
    }
}
