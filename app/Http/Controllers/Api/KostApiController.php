<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\ApiBaseController;
use App\Models\Kost;
use App\Models\Room;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class KostApiController extends ApiBaseController
{
    /**
     * GET /api/kosts
     * List all kosts with basic info + room counts.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Kost::with(['owner:id,name', 'roomCategories.rooms']);

            if ($request->filled('search')) {
                $search = $request->input('search');
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('address', 'like', "%{$search}%");
                });
            }

            $perPage = min((int) $request->input('per_page', 15), 50);

            $kosts = $query->latest()
                ->paginate($perPage)
                ->through(function (Kost $kost) {
                    $rooms = $kost->roomCategories->flatMap->rooms;

                    return [
                        'id' => $kost->id,
                        'name' => $kost->name,
                        'address' => $kost->address,
                        'address_coordinate' => $kost->address_coordinate,
                        'description' => $kost->description,
                        'owner_id' => $kost->owner_id,
                        'owner_name' => $kost->owner->name ?? null,
                        'total_rooms' => $rooms->count(),
                        'available_rooms' => $rooms->where('status', 'available')->count(),
                        'created_at' => $kost->created_at?->toISOString(),
                    ];
                });

            return $this->success($kosts);
        } catch (\Throwable $e) {
            return $this->serverError($e);
        }
    }

    /**
     * GET /api/kosts/{kost}
     * Detailed kost with room statistics.
     */
    public function show(Kost $kost): JsonResponse
    {
        try {
            $kost->load(['owner:id,name,email', 'roomCategories.rooms']);

            $rooms = $kost->roomCategories->flatMap->rooms;

            return $this->success([
                'id' => $kost->id,
                'name' => $kost->name,
                'address' => $kost->address,
                'address_coordinate' => $kost->address_coordinate,
                'description' => $kost->description,
                'owner_id' => $kost->owner_id,
                'owner_name' => $kost->owner->name ?? null,
                'total_rooms' => $rooms->count(),
                'available_rooms' => $rooms->where('status', 'available')->count(),
                'occupied_rooms' => $rooms->where('status', 'occupied')->count(),
                'maintenance_rooms' => $rooms->where('status', 'maintenance')->count(),
                'categories_count' => $kost->roomCategories->count(),
                'avg_price' => $this->getAveragePrice($kost),
                'created_at' => $kost->created_at?->toISOString(),
            ]);
        } catch (\Throwable $e) {
            return $this->serverError($e);
        }
    }

    /**
     * GET /api/kosts/{kost}/stats
     * Detailed statistics for a kost.
     */
    public function stats(Kost $kost): JsonResponse
    {
        try {
            $kost->load(['roomCategories.rooms', 'roomCategories.pricings']);

            $rooms = $kost->roomCategories->flatMap->rooms;
            $pricings = $kost->roomCategories->flatMap->pricings;
            $totalRooms = $rooms->count();

            return $this->success([
                'kost_id' => $kost->id,
                'kost_name' => $kost->name,
                'total_rooms' => $totalRooms,
                'available' => $rooms->where('status', 'available')->count(),
                'occupied' => $rooms->where('status', 'occupied')->count(),
                'maintenance' => $rooms->where('status', 'maintenance')->count(),
                'categories' => $kost->roomCategories->count(),
                'min_price' => $pricings->min('price') ? (float) $pricings->min('price') : 0,
                'max_price' => $pricings->max('price') ? (float) $pricings->max('price') : 0,
                'avg_occupancy_rate' => $totalRooms > 0
                    ? round($rooms->where('status', 'occupied')->count() / $totalRooms * 100, 1)
                    : 0,
            ]);
        } catch (\Throwable $e) {
            return $this->serverError($e);
        }
    }

    /**
     * GET /api/kosts/{kost}/contact-info
     * Public contact info for a kost's owner.
     */
    public function contactInfo(Kost $kost): JsonResponse
    {
        try {
            $kost->load('owner:id,name,email');

            return $this->success([
                'owner_name' => $kost->owner->name ?? null,
                'email' => $kost->owner->email ?? null,
                'address' => $kost->address,
            ]);
        } catch (\Throwable $e) {
            return $this->serverError($e);
        }
    }

    private function getAveragePrice(Kost $kost): float
    {
        $pricings = $kost->roomCategories->flatMap->pricings;
        $monthlyPricings = $pricings->where('duration_days', 30);

        if ($monthlyPricings->isEmpty()) {
            return $pricings->avg('price') ? round((float) $pricings->avg('price'), 2) : 0;
        }

        return round((float) $monthlyPricings->avg('price'), 2);
    }
}
