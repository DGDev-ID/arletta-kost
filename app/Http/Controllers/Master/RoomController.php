<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\Kost;
use App\Models\Room;
use App\Models\RoomCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RoomController extends Controller
{
    public function index(Request $request): Response
    {
        $search = $request->input('search', '');
        $kostId = $request->input('kost_id', '');

        $rooms = Room::with('roomCategory.kost')
            ->when($kostId, fn ($q) => $q->whereHas('roomCategory', fn ($q2) => $q2->where('kost_id', $kostId)))
            ->when($search, fn ($q) => $q->where(function ($q2) use ($search) {
                $q2->where('room_number', 'like', "%{$search}%")
                    ->orWhereHas('roomCategory', fn ($q3) => $q3->where('name', 'like', "%{$search}%")
                        ->orWhereHas('kost', fn ($q4) => $q4->where('name', 'like', "%{$search}%")));
            }))
            ->latest()
            ->paginate(15)
            ->through(fn (Room $room) => [
                'id' => $room->id,
                'room_number' => $room->room_number,
                'status' => $room->status,
                'category_name' => $room->roomCategory->name,
                'kost_name' => $room->roomCategory->kost->name,
                'kost_id' => $room->roomCategory->kost->id,
                'room_category_id' => $room->room_category_id,
            ]);

        $categories = RoomCategory::with('kost')
            ->get()
            ->map(fn (RoomCategory $cat) => [
                'id' => $cat->id,
                'name' => $cat->name,
                'kost_name' => $cat->kost->name,
                'kost_id' => $cat->kost_id,
            ]);

        $kosts = Kost::select('id', 'name')->get();

        return Inertia::render('Master/Room/Index', [
            'rooms' => $rooms,
            'categories' => $categories,
            'kosts' => $kosts,
            'filters' => ['search' => $search, 'kost_id' => $kostId],
        ]);
    }

    public function create(): Response
    {
        $categories = RoomCategory::with('kost')
            ->get()
            ->map(fn (RoomCategory $cat) => [
                'id' => $cat->id,
                'name' => $cat->name,
                'kost_name' => $cat->kost->name,
                'kost_id' => $cat->kost_id,
            ]);

        $kosts = Kost::select('id', 'name')->get();

        return Inertia::render('Master/Room/Form', [
            'categories' => $categories,
            'kosts' => $kosts,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'room_category_id' => 'required|exists:room_categories,id',
            'room_number' => 'required|string|max:50',
            'status' => 'required|in:available,occupied,maintenance',
        ]);

        Room::create($validated);

        return to_route('master.rooms.index')->with('success', 'Room berhasil ditambahkan.');
    }

    public function edit(Room $room): Response
    {
        $categories = RoomCategory::with('kost')
            ->get()
            ->map(fn (RoomCategory $cat) => [
                'id' => $cat->id,
                'name' => $cat->name,
                'kost_name' => $cat->kost->name,
                'kost_id' => $cat->kost_id,
            ]);

        $kosts = Kost::select('id', 'name')->get();

        return Inertia::render('Master/Room/Form', [
            'room' => $room->only('id', 'room_category_id', 'room_number', 'status'),
            'categories' => $categories,
            'kosts' => $kosts,
        ]);
    }

    public function update(Request $request, Room $room): RedirectResponse
    {
        $validated = $request->validate([
            'room_category_id' => 'required|exists:room_categories,id',
            'room_number' => 'required|string|max:50',
            'status' => 'required|in:available,occupied,maintenance',
        ]);

        $room->update($validated);

        return to_route('master.rooms.index')->with('success', 'Room berhasil diperbarui.');
    }

    public function destroy(Room $room): RedirectResponse
    {
        $room->delete();

        return to_route('master.rooms.index')->with('success', 'Room berhasil dihapus.');
    }
}
