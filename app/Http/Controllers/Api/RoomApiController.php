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
     * GET /api/rooms
     * List all rooms globally with category info, images, details, pricings.
     */
    public function list(Request $request): JsonResponse
    {
        try {
            $query = Room::with(['roomCategory.kost', 'roomCategory.pricings', 'roomCategory.images', 'roomCategory.details']);

            if ($request->filled('status')) {
                $query->where('status', $request->input('status'));
            }

            if ($request->filled('gender')) {
                $query->where('gender', $request->input('gender'));
            }

            if ($request->filled('category_id')) {
                $query->where('room_category_id', $request->input('category_id'));
            }

            if ($request->filled('q')) {
                $q = $request->input('q');
                $query->where(function ($qb) use ($q) {
                    $qb->where('room_number', 'like', "%{$q}%")
                       ->orWhereHas('roomCategory', fn ($qc) => $qc->where('name', 'like', "%{$q}%"))
                       ->orWhereHas('roomCategory.kost', fn ($qk) => $qk->where('name', 'like', "%{$q}%"));
                });
            }

            $perPage = min((int) $request->input('per_page', 20), 50);

            $rooms = $query->latest()
                ->paginate($perPage)
                ->through(fn (Room $room) => $this->formatRoom($room));

            return $this->success($rooms);
        } catch (\Throwable $e) {
            return $this->serverError($e);
        }
    }

    /**
     * GET /api/kosts/{kost}/rooms
     * List rooms for a kost with status, category info.
     */
    public function index(Request $request, Kost $kost): JsonResponse
    {
        try {
            $query = Room::with(['roomCategory.kost', 'roomCategory.pricings', 'roomCategory.images', 'roomCategory.details'])
                ->whereHas('roomCategory', fn ($q) => $q->where('kost_id', $kost->id));

            if ($request->filled('category_id')) {
                $query->where('room_category_id', $request->input('category_id'));
            }

            if ($request->filled('status')) {
                $query->where('status', $request->input('status'));
            } else {
                $query->where('status', 'available');
            }

            if ($request->filled('gender')) {
                $query->where('gender', $request->input('gender'));
            }

            $perPage = min((int) $request->input('per_page', 15), 50);

            $rooms = $query->latest()
                ->paginate($perPage)
                ->through(fn (Room $room) => $this->formatRoom($room));

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

            return $this->success($this->formatRoom($room));
        } catch (\Throwable $e) {
            return $this->serverError($e);
        }
    }

    /**
     * Format a Room model into API response.
     */
    private function formatRoom(Room $room): array
    {
        $pricings = $room->roomCategory->pricings;

        return [
            'id' => $room->id,
            'room_number' => $room->room_number,
            'status' => $room->status,
            'gender' => $room->gender,
            'category' => [
                'id' => $room->roomCategory->id,
                'name' => $room->roomCategory->name,
                'description' => $room->roomCategory->description,
            ],
            'kost' => [
                'id' => $room->roomCategory->kost->id,
                'name' => $room->roomCategory->kost->name,
                'address' => $room->roomCategory->kost->address,
            ],
            'images' => $room->roomCategory->images->map(fn ($img) => [
                'id' => $img->id,
                'url' => $this->resolveImageUrl($img->img_url),
            ]),
            'details' => $room->roomCategory->details->map(fn ($d) => [
                'id' => $d->id,
                'detail' => $d->detail,
                'icon' => $d->icon,
            ]),
            'pricings' => $pricings->map(fn ($p) => [
                'id' => $p->id,
                'duration_days' => $p->duration_days,
                'price' => (float) $p->price,
                'price_display' => $this->formatPriceDisplay($p->price, $p->duration_days),
            ]),
            'minimum_price' => $pricings->min('price') ? (float) $pricings->min('price') : 0,
            'maximum_price' => $pricings->max('price') ? (float) $pricings->max('price') : 0,
        ];
    }

    private function resolveImageUrl(string $imgUrl): string
    {
        if (str_starts_with($imgUrl, 'http://') || str_starts_with($imgUrl, 'https://')) {
            return $imgUrl;
        }

        return \Illuminate\Support\Facades\Storage::disk('public')->url($imgUrl);
    }

    private function formatPriceDisplay(float|string $price, int $durationDays): string
    {
        $formatted = 'Rp' . number_format((float) $price, 0, ',', '.');

        return match (true) {
            $durationDays <= 1 => $formatted . ' / hari',
            $durationDays <= 7 => $formatted . ' / minggu',
            $durationDays <= 31 => $formatted . ' / bulan',
            $durationDays <= 93 => $formatted . ' / 3 bulan',
            $durationDays <= 186 => $formatted . ' / 6 bulan',
            $durationDays <= 366 => $formatted . ' / tahun',
            default => $formatted . ' / ' . $durationDays . ' hari',
        };
    }
}
