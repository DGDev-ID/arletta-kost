<?php

namespace App\Http\Controllers\Management;

use App\Http\Controllers\Controller;
use App\Models\Room;
use App\Models\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TenantController extends Controller
{
    public function index(Request $request): Response
    {
        $search = $request->input('search', '');

        $tenants = Tenant::with(['room.roomCategory.kost', 'bills'])
            ->when($search, fn ($q) => $q->where(function ($q2) use ($search) {
                $q2->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone_number', 'like', "%{$search}%")
                    ->orWhereHas('room', fn ($q3) => $q3->where('room_number', 'like', "%{$search}%"));
            }))
            ->latest()
            ->paginate(15)
            ->through(fn (Tenant $tenant) => [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'email' => $tenant->email,
                'phone_number' => $tenant->phone_number,
                'gender' => $tenant->gender,
                'room_number' => $tenant->room->room_number,
                'kost_name' => $tenant->room->roomCategory->kost->name,
                'room_id' => $tenant->room_id,
                'bills_count' => $tenant->bills->count(),
                'unpaid_bills' => $tenant->bills->where('status', 'unpaid')->count(),
            ]);

        return Inertia::render('Management/Tenants/Index', [
            'tenants' => $tenants,
            'filters' => ['search' => $search],
        ]);
    }

    public function create(): Response
    {
        $rooms = Room::with('roomCategory.kost')
            ->where('status', 'available')
            ->get()
            ->map(fn (Room $room) => [
                'id' => $room->id,
                'room_number' => $room->room_number,
                'kost_name' => $room->roomCategory->kost->name,
            ]);

        return Inertia::render('Management/Tenants/Form', [
            'rooms' => $rooms,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'room_id' => 'required|exists:rooms,id',
            'email' => 'required|email|max:255',
            'name' => 'required|string|max:255',
            'nik' => 'nullable|string|max:50',
            'ktp_number' => 'nullable|string|max:50',
            'birth_place' => 'nullable|string|max:255',
            'birth_date' => 'nullable|date',
            'gender' => 'nullable|in:male,female',
            'address' => 'nullable|string',
            'phone_number' => 'required|string|max:20',
        ]);

        Tenant::create($validated);

        // Mark room as occupied
        Room::where('id', $validated['room_id'])->update(['status' => 'occupied']);

        return to_route('management.tenants.index')->with('success', 'Tenant berhasil ditambahkan.');
    }

    public function show(Tenant $tenant): Response
    {
        $tenant->load(['room.roomCategory.kost', 'bills']);

        $tenantData = [
            'id' => $tenant->id,
            'name' => $tenant->name,
            'email' => $tenant->email,
            'phone_number' => $tenant->phone_number,
            'nik' => $tenant->nik,
            'ktp_number' => $tenant->ktp_number,
            'birth_place' => $tenant->birth_place,
            'birth_date' => $tenant->birth_date?->format('Y-m-d'),
            'gender' => $tenant->gender,
            'address' => $tenant->address,
            'room_id' => $tenant->room_id,
            'room_number' => $tenant->room->room_number,
            'kost_name' => $tenant->room->roomCategory->kost->name,
            'category_name' => $tenant->room->roomCategory->name,
        ];

        $bills = $tenant->bills()
            ->latest()
            ->get()
            ->map(fn ($bill) => [
                'id' => $bill->id,
                'total_price' => (float) $bill->total_price,
                'start_date' => $bill->start_date->format('Y-m-d'),
                'due_date' => $bill->due_date->format('Y-m-d'),
                'status' => $bill->status,
            ]);

        $pricings = $tenant->room->roomCategory->pricings()
            ->get()
            ->map(fn ($p) => [
                'id' => $p->id,
                'duration_days' => $p->duration_days,
                'price' => (float) $p->price,
            ]);

        return Inertia::render('Management/Tenants/Show', [
            'tenant' => $tenantData,
            'bills' => $bills,
            'pricings' => $pricings,
        ]);
    }

    public function edit(Tenant $tenant): Response
    {
        $rooms = Room::with('roomCategory.kost')
            ->where(function ($q) use ($tenant) {
                $q->where('status', 'available')
                    ->orWhere('id', $tenant->room_id);
            })
            ->get()
            ->map(fn (Room $room) => [
                'id' => $room->id,
                'room_number' => $room->room_number,
                'kost_name' => $room->roomCategory->kost->name,
            ]);

        return Inertia::render('Management/Tenants/Form', [
            'tenant' => $tenant->only('id', 'room_id', 'email', 'name', 'nik', 'ktp_number', 'birth_place', 'birth_date', 'gender', 'address', 'phone_number'),
            'rooms' => $rooms,
        ]);
    }

    public function update(Request $request, Tenant $tenant): RedirectResponse
    {
        $validated = $request->validate([
            'room_id' => 'required|exists:rooms,id',
            'email' => 'required|email|max:255',
            'name' => 'required|string|max:255',
            'nik' => 'nullable|string|max:50',
            'ktp_number' => 'nullable|string|max:50',
            'birth_place' => 'nullable|string|max:255',
            'birth_date' => 'nullable|date',
            'gender' => 'nullable|in:male,female',
            'address' => 'nullable|string',
            'phone_number' => 'required|string|max:20',
        ]);

        $oldRoomId = $tenant->room_id;
        $tenant->update($validated);

        // Handle room status changes
        if ($oldRoomId !== (int) $validated['room_id']) {
            Room::where('id', $oldRoomId)->update(['status' => 'available']);
            Room::where('id', $validated['room_id'])->update(['status' => 'occupied']);
        }

        return to_route('management.tenants.index')->with('success', 'Tenant berhasil diperbarui.');
    }

    public function destroy(Tenant $tenant): RedirectResponse
    {
        $roomId = $tenant->room_id;
        $tenant->delete();

        // Free the room
        Room::where('id', $roomId)->update(['status' => 'available']);

        return to_route('management.tenants.index')->with('success', 'Tenant berhasil dihapus.');
    }
}
