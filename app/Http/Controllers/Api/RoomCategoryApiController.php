<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\ApiBaseController;
use App\Models\Kost;
use App\Models\RoomCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class RoomCategoryApiController extends ApiBaseController
{
    /**
     * GET /api/kosts/{kost}/categories
     * List categories for a kost with images, details, pricings, room counts.
     */
    public function index(Request $request, Kost $kost): JsonResponse
    {
        try {
            $query = $kost->roomCategories()
                ->with(['images', 'details', 'pricings.promos', 'rooms']);

            $categories = $query->get()->map(function (RoomCategory $cat) {
                return [
                    'id' => $cat->id,
                    'name' => $cat->name,
                    'description' => $cat->description,
                    'kost_id' => $cat->kost_id,
                    'images' => $cat->images->map(fn ($img) => [
                        'id' => $img->id,
                        'url' => $this->resolveImageUrl($img->img_url),
                    ]),
                    'details' => $cat->details->map(fn ($d) => [
                        'id' => $d->id,
                        'detail' => $d->detail,
                        'icon' => $d->icon,
                    ]),
                    'pricings' => $cat->pricings->map(fn ($p) => [
                        'id' => $p->id,
                        'duration_days' => $p->duration_days,
                        'price' => (float) $p->price,
                        'price_display' => $this->formatPriceDisplay($p->price, $p->duration_days),
                    ]),
                    'available_rooms' => $cat->rooms->where('status', 'available')->count(),
                    'total_rooms' => $cat->rooms->count(),
                ];
            });

            return $this->success($categories);
        } catch (\Throwable $e) {
            return $this->serverError($e);
        }
    }

    /**
     * GET /api/categories/{roomCategory}
     * Single category detail with promos and final pricing.
     */
    public function show(RoomCategory $roomCategory): JsonResponse
    {
        try {
            $roomCategory->load(['kost', 'images', 'details', 'pricings.promos', 'rooms']);

            return $this->success([
                'id' => $roomCategory->id,
                'name' => $roomCategory->name,
                'description' => $roomCategory->description,
                'kost_id' => $roomCategory->kost_id,
                'kost_name' => $roomCategory->kost->name,
                'images' => $roomCategory->images->map(fn ($img) => [
                    'id' => $img->id,
                    'url' => $this->resolveImageUrl($img->img_url),
                ]),
                'details' => $roomCategory->details->map(fn ($d) => [
                    'id' => $d->id,
                    'detail' => $d->detail,
                    'icon' => $d->icon,
                ]),
                'pricings' => $roomCategory->pricings->map(function ($p) {
                    $finalPriceData = $p->getFinalPrice();

                    return [
                        'id' => $p->id,
                        'duration_days' => $p->duration_days,
                        'price' => (float) $p->price,
                        'price_display' => $this->formatPriceDisplay($p->price, $p->duration_days),
                        'promos' => $p->promos->map(fn ($promo) => [
                            'id' => $promo->id,
                            'type' => $promo->type,
                        ]),
                        'final_price' => $finalPriceData['final_price'],
                        'final_price_display' => 'Rp' . number_format($finalPriceData['final_price'], 0, ',', '.'),
                    ];
                }),
                'available_rooms' => $roomCategory->rooms->where('status', 'available')->count(),
                'total_rooms' => $roomCategory->rooms->count(),
            ]);
        } catch (\Throwable $e) {
            return $this->serverError($e);
        }
    }

    /**
     * GET /api/categories/{roomCategory}/images
     * Gallery images for a category.
     */
    public function images(RoomCategory $roomCategory): JsonResponse
    {
        try {
            $roomCategory->load('images');

            $images = $roomCategory->images->map(fn ($img) => [
                'id' => $img->id,
                'url' => $this->resolveImageUrl($img->img_url),
            ]);

            return $this->success($images);
        } catch (\Throwable $e) {
            return $this->serverError($e);
        }
    }

    private function resolveImageUrl(string $imgUrl): string
    {
        if (str_starts_with($imgUrl, 'http://') || str_starts_with($imgUrl, 'https://')) {
            return $imgUrl;
        }

        return Storage::disk('public')->url($imgUrl);
    }

    private function formatPriceDisplay(float|string $price, int $durationDays): string
    {
        $formatted = 'Rp' . number_format((float) $price, 0, ',', '.');

        return match (true) {
            $durationDays <= 1 => $formatted . ' / 1 hari',
            $durationDays <= 7 => $formatted . ' / 1 minggu',
            $durationDays <= 31 => $formatted . ' / 1 bulan',
            $durationDays <= 93 => $formatted . ' / 3 bulan',
            $durationDays <= 186 => $formatted . ' / 6 bulan',
            $durationDays <= 366 => $formatted . ' / 12 bulan',
            default => $formatted . ' / ' . $durationDays . ' hari',
        };
    }
}
