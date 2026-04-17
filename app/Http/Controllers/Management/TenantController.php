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

        $tenants = Tenant::with(['rooms.roomCategory.kost', 'bills'])
            ->when($search, fn ($q) => $q->where(function ($q2) use ($search) {
                $q2->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone_number', 'like', "%{$search}%")
                    ->orWhereHas('rooms', fn ($q3) => $q3->where('room_number', 'like', "%{$search}%"));
            }))
            ->latest()
            ->paginate(15)
            ->through(fn (Tenant $tenant) => [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'email' => $tenant->email,
                'phone_number' => $tenant->phone_number,
                'gender' => $tenant->gender,
                'rooms' => $tenant->rooms->map(fn ($room) => [
                    'room_number' => $room->room_number,
                    'kost_name' => $room->roomCategory->kost->name,
                ])->toArray(),
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
            'room_ids' => 'required|array|min:1',
            'room_ids.*' => 'exists:rooms,id',
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

        $tenant = Tenant::create([
            'room_id' => $validated['room_ids'][0] ?? null,
            'email' => $validated['email'],
            'name' => $validated['name'],
            'nik' => $validated['nik'] ?? null,
            'ktp_number' => $validated['ktp_number'] ?? null,
            'birth_place' => $validated['birth_place'] ?? null,
            'birth_date' => $validated['birth_date'] ?? null,
            'gender' => $validated['gender'] ?? null,
            'address' => $validated['address'] ?? null,
            'phone_number' => $validated['phone_number'],
        ]);

        // Attach rooms via pivot
        $tenant->rooms()->sync($validated['room_ids']);

        // Mark rooms as occupied
        Room::whereIn('id', $validated['room_ids'])->update(['status' => 'occupied']);

        return to_route('management.tenants.index')->with('success', 'Tenant berhasil ditambahkan.');
    }

    public function show(Tenant $tenant): Response
    {
        $tenant->load(['rooms.roomCategory.kost', 'bills.transactions']);

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
            'rooms' => $tenant->rooms->map(fn ($room) => [
                'id' => $room->id,
                'room_number' => $room->room_number,
                'kost_name' => $room->roomCategory->kost->name,
                'category_name' => $room->roomCategory->name,
                'status' => $room->status,
            ])->toArray(),
        ];

        // Bills: only unpaid/pending + manual payment_type
        $bills = $tenant->bills()
            ->with(['transactions'])
            ->latest()
            ->get()
            ->map(fn ($bill) => [
                'id' => $bill->id,
                'total_price' => (float) $bill->total_price,
                'start_date' => $bill->start_date->format('Y-m-d'),
                'due_date' => $bill->due_date->format('Y-m-d'),
                'status' => $bill->status,
                'room_id' => $bill->room_id,
                'transactions' => $bill->transactions->map(fn ($t) => [
                    'id' => $t->id,
                    'order_id' => $t->order_id,
                    'payment_type' => $t->payment_type,
                    'status' => $t->status,
                    'total_price' => (float) $t->total_price,
                ])->toArray(),
            ]);

        // Get pricings from all rooms' categories
        $pricings = collect();
        foreach ($tenant->rooms as $room) {
            $roomPricings = $room->roomCategory->pricings()
                ->get()
                ->map(fn ($p) => [
                    'id' => $p->id,
                    'duration_days' => $p->duration_days,
                    'price' => (float) $p->price,
                    'room_id' => $room->id,
                    'room_number' => $room->room_number,
                ]);
            $pricings = $pricings->merge($roomPricings);
        }

        return Inertia::render('Management/Tenants/Show', [
            'tenant' => $tenantData,
            'bills' => $bills,
            'pricings' => $pricings->values(),
        ]);
    }

    public function edit(Tenant $tenant): Response
    {
        $tenant->load('rooms');

        $rooms = Room::with('roomCategory.kost')
            ->where(function ($q) use ($tenant) {
                $q->where('status', 'available')
                    ->orWhereIn('id', $tenant->rooms->pluck('id'));
            })
            ->get()
            ->map(fn (Room $room) => [
                'id' => $room->id,
                'room_number' => $room->room_number,
                'kost_name' => $room->roomCategory->kost->name,
            ]);

        return Inertia::render('Management/Tenants/Form', [
            'tenant' => [
                ...$tenant->only('id', 'email', 'name', 'nik', 'ktp_number', 'birth_place', 'birth_date', 'gender', 'address', 'phone_number'),
                'room_ids' => $tenant->rooms->pluck('id')->toArray(),
            ],
            'rooms' => $rooms,
        ]);
    }

    public function update(Request $request, Tenant $tenant): RedirectResponse
    {
        $validated = $request->validate([
            'room_ids' => 'nullable|array',
            'room_ids.*' => 'exists:rooms,id',
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

        $oldRoomIds = $tenant->rooms->pluck('id')->toArray();
        $newRoomIds = $validated['room_ids'] ?? null;

        // Prepare update payload; only change room_id if rooms submitted
        $updateData = [
            'email' => $validated['email'],
            'name' => $validated['name'],
            'nik' => $validated['nik'] ?? null,
            'ktp_number' => $validated['ktp_number'] ?? null,
            'birth_place' => $validated['birth_place'] ?? null,
            'birth_date' => $validated['birth_date'] ?? null,
            'gender' => $validated['gender'] ?? null,
            'address' => $validated['address'] ?? null,
            'phone_number' => $validated['phone_number'],
        ];

        if ($newRoomIds !== null) {
            $updateData['room_id'] = $newRoomIds[0] ?? null;
        }

        $tenant->update($updateData);

        // If room_ids present in request, sync and update room statuses
        if ($newRoomIds !== null) {
            $tenant->rooms()->sync($newRoomIds);

            // Free removed rooms
            $removedRoomIds = array_diff($oldRoomIds, $newRoomIds);
            if (! empty($removedRoomIds)) {
                Room::whereIn('id', $removedRoomIds)->update(['status' => 'available']);
            }

            // Mark new rooms as occupied
            $addedRoomIds = array_diff($newRoomIds, $oldRoomIds);
            if (! empty($addedRoomIds)) {
                Room::whereIn('id', $addedRoomIds)->update(['status' => 'occupied']);
            }
        }

        return to_route('management.tenants.index')->with('success', 'Tenant berhasil diperbarui.');
    }

    public function destroy(Tenant $tenant): RedirectResponse
    {
        $roomIds = $tenant->rooms->pluck('id')->toArray();
        $tenant->rooms()->detach();
        $tenant->delete();

        // Free all rooms
        Room::whereIn('id', $roomIds)->update(['status' => 'available']);

        return to_route('management.tenants.index')->with('success', 'Tenant berhasil dihapus.');
    }
}
