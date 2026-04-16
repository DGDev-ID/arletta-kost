<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\ApiBaseController;
use App\Models\Kost;
use App\Models\Room;
use App\Models\RoomCategory;
use App\Models\RoomPricing;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SearchApiController extends ApiBaseController
{
    /**
     * GET /api/search
     * Unified search across kosts, categories, rooms.
     */
    public function search(Request $request): JsonResponse
    {
        try {
            $q = $request->input('q', '');
            $type = $request->input('type', 'all');
            $minPrice = $request->input('min_price');
            $maxPrice = $request->input('max_price');
            $status = $request->input('status', 'available');
            $limit = min((int) $request->input('limit', 20), 50);

            $result = [];

            // Kosts
            if ($type === 'all' || $type === 'kost') {
                $kostsQuery = Kost::with(['owner:id,name', 'roomCategories.rooms', 'roomCategories.pricings']);

                if ($q) {
                    $kostsQuery->where(function ($query) use ($q) {
                        $query->where('name', 'like', "%{$q}%")
                              ->orWhere('address', 'like', "%{$q}%");
                    });
                }

                $kosts = $kostsQuery->limit($limit)->get()->map(function (Kost $kost) {
                    $rooms = $kost->roomCategories->flatMap->rooms;
                    $pricings = $kost->roomCategories->flatMap->pricings;

                    return [
                        'id' => $kost->id,
                        'name' => $kost->name,
                        'address' => $kost->address,
                        'description' => $kost->description,
                        'owner_name' => $kost->owner->name ?? null,
                        'total_rooms' => $rooms->count(),
                        'available_rooms' => $rooms->where('status', 'available')->count(),
                        'min_price' => $pricings->min('price') ? (float) $pricings->min('price') : 0,
                        'max_price' => $pricings->max('price') ? (float) $pricings->max('price') : 0,
                    ];
                });

                // Price filtering for kosts
                if ($minPrice || $maxPrice) {
                    $kosts = $kosts->filter(function ($kost) use ($minPrice, $maxPrice) {
                        if ($minPrice && $kost['max_price'] < (float) $minPrice) return false;
                        if ($maxPrice && $kost['min_price'] > (float) $maxPrice) return false;
                        return true;
                    })->values();
                }

                $result['kosts'] = $kosts;
            }

            // Categories
            if ($type === 'all' || $type === 'category') {
                $catsQuery = RoomCategory::with(['kost', 'pricings', 'rooms', 'images']);

                if ($q) {
                    $catsQuery->where(function ($query) use ($q) {
                        $query->where('name', 'like', "%{$q}%")
                              ->orWhereHas('kost', fn ($qk) => $qk->where('name', 'like', "%{$q}%"));
                    });
                }

                if ($minPrice || $maxPrice) {
                    $catsQuery->whereHas('pricings', function ($pq) use ($minPrice, $maxPrice) {
                        if ($minPrice) $pq->where('price', '>=', $minPrice);
                        if ($maxPrice) $pq->where('price', '<=', $maxPrice);
                    });
                }

                $result['categories'] = $catsQuery->limit($limit)->get()->map(function (RoomCategory $cat) {
                    return [
                        'id' => $cat->id,
                        'name' => $cat->name,
                        'description' => $cat->description,
                        'kost_id' => $cat->kost_id,
                        'kost_name' => $cat->kost->name,
                        'available_rooms' => $cat->rooms->where('status', 'available')->count(),
                        'total_rooms' => $cat->rooms->count(),
                        'min_price' => $cat->pricings->min('price') ? (float) $cat->pricings->min('price') : 0,
                        'image_url' => $cat->images->first() ? $this->resolveImageUrl($cat->images->first()->img_url) : null,
                    ];
                });
            }

            // Rooms
            if ($type === 'all' || $type === 'room') {
                $roomsQuery = Room::with(['roomCategory.kost']);

                if ($q) {
                    $roomsQuery->where(function ($query) use ($q) {
                        $query->where('room_number', 'like', "%{$q}%")
                              ->orWhereHas('roomCategory', fn ($qc) => $qc->where('name', 'like', "%{$q}%"))
                              ->orWhereHas('roomCategory.kost', fn ($qk) => $qk->where('name', 'like', "%{$q}%"));
                    });
                }

                if ($status) {
                    $roomsQuery->where('status', $status);
                }

                $result['rooms'] = $roomsQuery->limit($limit)->get()->map(fn (Room $room) => [
                    'id' => $room->id,
                    'room_number' => $room->room_number,
                    'status' => $room->status,
                    'category_name' => $room->roomCategory->name,
                    'kost_name' => $room->roomCategory->kost->name,
                    'kost_id' => $room->roomCategory->kost->id,
                ]);
            }

            return $this->success($result);
        } catch (\Throwable $e) {
            return $this->serverError($e);
        }
    }

    /**
     * GET /api/price-range
     * Global min/max price from all pricings.
     */
    public function priceRange(): JsonResponse
    {
        try {
            return $this->success([
                'min_price' => (float) (RoomPricing::min('price') ?? 0),
                'max_price' => (float) (RoomPricing::max('price') ?? 0),
            ]);
        } catch (\Throwable $e) {
            return $this->serverError($e);
        }
    }

    /**
     * GET /api/stats
     * Global landing page statistics.
     */
    public function stats(): JsonResponse
    {
        try {
            $totalKosts = Kost::count();
            $totalRooms = Room::count();
            $availableRooms = Room::where('status', 'available')->count();
            $totalCategories = RoomCategory::count();
            $avgPrice = (float) (RoomPricing::where('duration_days', 30)->avg('price') ?? RoomPricing::avg('price') ?? 0);

            // Featured kosts — those with most available rooms
            $featuredKosts = Kost::with(['owner:id,name', 'roomCategories.rooms', 'roomCategories.pricings'])
                ->limit(6)
                ->get()
                ->map(function (Kost $kost) {
                    $rooms = $kost->roomCategories->flatMap->rooms;
                    $pricings = $kost->roomCategories->flatMap->pricings;

                    return [
                        'id' => $kost->id,
                        'name' => $kost->name,
                        'address' => $kost->address,
                        'description' => $kost->description,
                        'available_rooms' => $rooms->where('status', 'available')->count(),
                        'min_price' => $pricings->min('price') ? (float) $pricings->min('price') : 0,
                    ];
                })
                ->sortByDesc('available_rooms')
                ->values();

            return $this->success([
                'total_kosts' => $totalKosts,
                'total_rooms' => $totalRooms,
                'available_rooms' => $availableRooms,
                'total_categories' => $totalCategories,
                'avg_price' => round($avgPrice, 2),
                'featured_kosts' => $featuredKosts,
            ]);
        } catch (\Throwable $e) {
            return $this->serverError($e);
        }
    }

    /**
     * GET /api/featured-kosts
     * Featured kosts for homepage.
     */
    public function featuredKosts(Request $request): JsonResponse
    {
        try {
            $limit = min((int) $request->input('limit', 6), 20);

            $kosts = Kost::with(['owner:id,name', 'roomCategories.rooms', 'roomCategories.pricings', 'roomCategories.images'])
                ->limit($limit)
                ->get()
                ->map(function (Kost $kost) {
                    $rooms = $kost->roomCategories->flatMap->rooms;
                    $pricings = $kost->roomCategories->flatMap->pricings;
                    $firstImage = $kost->roomCategories->flatMap->images->first();

                    return [
                        'id' => $kost->id,
                        'name' => $kost->name,
                        'address' => $kost->address,
                        'description' => $kost->description,
                        'image_url' => $firstImage ? $this->resolveImageUrl($firstImage->img_url) : null,
                        'available_rooms' => $rooms->where('status', 'available')->count(),
                        'min_price' => $pricings->min('price') ? (float) $pricings->min('price') : 0,
                    ];
                })
                ->sortByDesc('available_rooms')
                ->values();

            return $this->success($kosts);
        } catch (\Throwable $e) {
            return $this->serverError($e);
        }
    }

    /**
     * GET /api/featured-categories
     * Featured/popular categories for homepage.
     */
    public function featuredCategories(Request $request): JsonResponse
    {
        try {
            $limit = min((int) $request->input('limit', 6), 20);

            $categories = RoomCategory::with(['kost', 'images', 'pricings', 'rooms'])
                ->limit($limit)
                ->get()
                ->map(function (RoomCategory $cat) {
                    return [
                        'id' => $cat->id,
                        'name' => $cat->name,
                        'description' => $cat->description,
                        'kost_id' => $cat->kost_id,
                        'kost_name' => $cat->kost->name,
                        'image_url' => $cat->images->first() ? $this->resolveImageUrl($cat->images->first()->img_url) : null,
                        'available_rooms' => $cat->rooms->where('status', 'available')->count(),
                        'total_rooms' => $cat->rooms->count(),
                        'min_price' => $cat->pricings->min('price') ? (float) $cat->pricings->min('price') : 0,
                    ];
                });

            return $this->success($categories);
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
}
