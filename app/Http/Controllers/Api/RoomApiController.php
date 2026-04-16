<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\ApiBaseController;
use App\Models\Kost;
use App\Models\Room;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RoomApiController extends ApiBaseController
{
    /**
     * GET /api/kosts/{kost}/rooms
     * List rooms for a kost with status, category info.
     */
    public function index(Request $request, Kost $kost): JsonResponse
    {
        try {
            $query = Room::with('roomCategory')
                ->whereHas('roomCategory', fn ($q) => $q->where('kost_id', $kost->id));

            if ($request->filled('category_id')) {
                $query->where('room_category_id', $request->input('category_id'));
            }

            if ($request->filled('status')) {
                $query->where('status', $request->input('status'));
            } else {
                $query->where('status', 'available');
            }

            $perPage = min((int) $request->input('per_page', 15), 50);

            $rooms = $query->latest()
                ->paginate($perPage)
                ->through(fn (Room $room) => [
                    'id' => $room->id,
                    'room_number' => $room->room_number,
                    'status' => $room->status,
                    'category_id' => $room->room_category_id,
                    'category_name' => $room->roomCategory->name,
                    'category_description' => $room->roomCategory->description,
                    'kost_id' => $kost->id,
                    'kost_name' => $kost->name,
                ]);

            return $this->success($rooms);
        } catch (\Throwable $e) {
            return $this->serverError($e);
        }
    }

    /**
     * GET /api/rooms/{room}
     * Single room detail with category and pricing info.
     */
    public function show(Room $room): JsonResponse
    {
        try {
            $room->load(['roomCategory.kost', 'roomCategory.pricings', 'roomCategory.images', 'roomCategory.details']);

            $pricings = $room->roomCategory->pricings;

            return $this->success([
                'id' => $room->id,
                'room_number' => $room->room_number,
                'status' => $room->status,
                'category' => [
                    'id' => $room->roomCategory->id,
                    'name' => $room->roomCategory->name,
                    'description' => $room->roomCategory->description,
                    'images' => $room->roomCategory->images->map(fn ($img) => [
                        'id' => $img->id,
                        'url' => $this->resolveImageUrl($img->img_url),
                    ]),
                    'details' => $room->roomCategory->details->map(fn ($d) => [
                        'id' => $d->id,
                        'detail' => $d->detail,
                        'icon' => $d->icon,
                    ]),
                ],
                'kost' => [
                    'id' => $room->roomCategory->kost->id,
                    'name' => $room->roomCategory->kost->name,
                    'address' => $room->roomCategory->kost->address,
                ],
                'minimum_price' => $pricings->min('price') ? (float) $pricings->min('price') : 0,
                'maximum_price' => $pricings->max('price') ? (float) $pricings->max('price') : 0,
            ]);
        } catch (\Throwable $e) {
            return $this->serverError($e);
        }
    }

    private function resolveImageUrl(string $imgUrl): string
    {
        if (str_starts_with($imgUrl, 'http://') || str_starts_with($imgUrl, 'https://')) {
            return $imgUrl;
        }

        return \Illuminate\Support\Facades\Storage::disk('public')->url($imgUrl);
    }
}
