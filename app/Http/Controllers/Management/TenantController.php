<?php

namespace App\Http\Controllers\Management;

use App\Http\Controllers\Controller;
use App\Models\Room;
use App\Models\RoomCategory;
use App\Models\Tenant;
use Carbon\Carbon;
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
            ->when($search, fn($q) => $q->where(function ($q2) use ($search) {
                $q2->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone_number', 'like', "%{$search}%")
                    ->orWhereHas('rooms', fn($q3) => $q3->where('room_number', 'like', "%{$search}%"));
            }))
            ->latest()
            ->paginate(15)
            ->through(fn(Tenant $tenant) => [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'email' => $tenant->email,
                'phone_number' => $tenant->phone_number,
                'gender' => $tenant->gender,
                'start_date' => $tenant->start_date?->format('Y-m-d'),
                'end_date' => $tenant->end_date?->format('Y-m-d'),
                'rooms' => $tenant->rooms->map(fn($room) => [
                    'room_number' => $room->room_number,
                    'kost_name' => $room->roomCategory->kost->name,
                ])->toArray(),
                'bills_count' => $tenant->bills->count(),
                'unpaid_bills' => $tenant->bills()
                    ->where('status', 'unpaid')
                    ->whereHas('transactions', function ($q) {
                        $q->where('status', '!=', 'failed');
                    })
                    ->count(),
            ]);

        return Inertia::render('Management/Tenants/Index', [
            'tenants' => $tenants,
            'filters' => ['search' => $search],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Management/Tenants/Form');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => 'required|email|max:255',
            'name' => 'required|string|max:255',
            'nik' => 'nullable|string|max:50',
            'birth_place' => 'nullable|string|max:255',
            'birth_date' => 'nullable|date',
            'gender' => 'nullable|in:male,female',
            'address' => 'nullable|string',
            'phone_number' => 'required|string|max:20',
        ]);

        Tenant::create([
            'email' => $validated['email'],
            'name' => $validated['name'],
            'nik' => $validated['nik'] ?? null,
            'birth_place' => $validated['birth_place'] ?? null,
            'birth_date' => $validated['birth_date'] ?? null,
            'gender' => $validated['gender'] ?? null,
            'address' => $validated['address'] ?? null,
            'phone_number' => $validated['phone_number'],
        ]);

        return to_route('management.tenants.index')->with('success', 'Tenant berhasil ditambahkan.');
    }

    public function show(Tenant $tenant)
    {
        $tenant->load(['rooms.roomCategory.kost', 'bills.transactions']);

        $tenantData = [
            'id' => $tenant->id,
            'name' => $tenant->name,
            'email' => $tenant->email,
            'phone_number' => $tenant->phone_number,
            'nik' => $tenant->nik,
            'birth_place' => $tenant->birth_place,
            'birth_date' => $tenant->birth_date?->format('Y-m-d'),
            'gender' => $tenant->gender,
            'address' => $tenant->address,
            'rooms' => $tenant->bills
                ->filter(function ($bill) {
                    // Show all active bills where due_date hasn't passed yet.
                    // This includes future bookings (start_date > today) and current occupancies.
                    return in_array($bill->status, ['paid', 'down_payment', 'finished_payment'])
                        && Carbon::parse($bill->due_date)->gte(now()->startOfDay());
                })
                ->map(fn($bill) => [
                    'id' => $bill->room->id,
                    'room_number' => $bill->room->room_number,
                    'kost_name' => $bill->room->roomCategory->kost->name,
                    'category_name' => $bill->room->roomCategory->name,
                    'bill_id' => $bill->id,
                    'start_date' => $bill->start_date?->format('Y-m-d'),
                    'end_date' => $bill->due_date?->format('Y-m-d'),
                    'status' => $bill->status,
                ])
                ->values()
                ->toArray(),
        ];

        // Bills
        $bills = $tenant->bills()
            ->with(['transactions', 'room.roomCategory.kost'])
            ->latest()
            ->get()
            ->map(fn($bill) => [
                'id' => $bill->id,
                'total_price' => (float) $bill->total_price,
                'start_date' => $bill->start_date->format('Y-m-d'),
                'due_date' => $bill->due_date->format('Y-m-d'),
                'status' => $bill->status,
                'room_id' => $bill->room_id,
                'room_number' => $bill->room ? $bill->room->room_number : '-',
                'kost_name' => $bill->room && $bill->room->roomCategory && $bill->room->roomCategory->kost ? $bill->room->roomCategory->kost->name : '-',
                'payment_scheme' => $bill->payment_scheme,
                'dp_amount' => (float) $bill->dp_amount,
                'transactions' => $bill->transactions->map(fn($t) => [
                    'id' => $t->id,
                    'order_id' => $t->order_id,
                    'payment_type' => $t->payment_type,
                    'transaction_type' => $t->transaction_type,
                    'status' => $t->status,
                    'total_price' => (float) $t->total_price,
                ])->toArray(),
            ]);

        // Categories with their pricings (for the Create Bill form)
        $categoriesQuery = RoomCategory::with(['kost', 'pricings.promos'])
            ->whereHas('rooms', fn($q) => $q->where('status', '!=', 'maintenance'));

        // If tenant has a specific gender, only include categories that are either null (unisex) or match tenant gender
        if ($tenant->gender) {
            $categoriesQuery->where(function ($q) use ($tenant) {
                $q->whereNull('gender')
                  ->orWhere('gender', $tenant->gender);
            });
        }

        $categories = $categoriesQuery->get()
            ->map(fn(RoomCategory $cat) => [
                'id'          => $cat->id,
                'name'        => $cat->name,
                'kost_name'   => $cat->kost->name,
                // pricing dengan duration_days == 1 dianggap sebagai harga harian
                'daily_price' => (float) ($cat->pricings->firstWhere('duration_days', 1)?->price ?? 0),
                'pricings'    => $cat->pricings
                    ->filter(fn($p) => $p->duration_days !== 1) // sembunyikan pricing harian dari list paket
                    ->values()
                    ->map(function ($p) {
                        $fp = $p->getFinalPrice();
                        return [
                            'id'            => $p->id,
                            'duration_days' => $p->duration_days,
                            'price'         => (float) $p->price,
                            'final_price'   => (float) $fp['final_price'],
                            'bonus_days'    => $fp['bonus_days'],
                            'cashback'      => $fp['cashback'],
                            'applied_promos'=> $fp['applied_promos'],
                        ];
                    })->toArray(),
                'all_pricings' => $cat->pricings->map(function ($p) {
                    $fp = $p->getFinalPrice();
                    return [
                        'id'            => $p->id,
                        'duration_days' => $p->duration_days,
                        'price'         => (float) $p->price,
                        'final_price'   => (float) $fp['final_price'],
                        'bonus_days'    => $fp['bonus_days'],
                        'cashback'      => $fp['cashback'],
                        'applied_promos'=> $fp['applied_promos'],
                    ];
                })->toArray(),
            ]);

        // All non-maintenance rooms with their occupied periods for date-based filtering
        $availableRoomsQuery = Room::with(['roomCategory.kost', 'bills' => function ($q) {
            $q->whereIn('status', ['paid', 'unpaid', 'down_payment', 'finished_payment'])->select('id', 'room_id', 'start_date', 'due_date', 'status');
        }])
            ->where('status', '!=', 'maintenance');

        // If tenant has a specific gender, only include rooms whose category is unisex (null) or matches tenant gender
        if ($tenant->gender) {
            $availableRoomsQuery->whereHas('roomCategory', function ($q) use ($tenant) {
                $q->whereNull('gender')
                  ->orWhere('gender', $tenant->gender);
            });
        }

        $availableRooms = $availableRoomsQuery->get()
            ->map(fn(Room $room) => [
                'id' => $room->id,
                'room_number' => $room->room_number,
                'kost_name' => $room->roomCategory->kost->name,
                'category_id' => $room->room_category_id,
                'category_name' => $room->roomCategory->name,
                'occupied_periods' => $room->bills->map(fn($b) => [
                    'start_date' => $b->start_date->format('Y-m-d'),
                    'due_date' => $b->due_date->format('Y-m-d'),
                ])->toArray(),
            ]);

        return Inertia::render('Management/Tenants/Show', [
            'tenant' => $tenantData,
            'bills' => $bills,
            'categories' => $categories,
            'availableRooms' => $availableRooms,
        ]);
    }

    public function edit(Tenant $tenant): Response
    {
        return Inertia::render('Management/Tenants/Form', [
            'tenant' => [
                ...$tenant->only('id', 'email', 'name', 'nik', 'birth_place', 'birth_date', 'gender', 'address', 'phone_number'),
            ],
        ]);
    }

    public function update(Request $request, Tenant $tenant): RedirectResponse
    {
        $validated = $request->validate([
            'email' => 'required|email|max:255',
            'name' => 'required|string|max:255',
            'nik' => 'nullable|string|max:50',
            'birth_place' => 'nullable|string|max:255',
            'birth_date' => 'nullable|date',
            'gender' => 'nullable|in:male,female',
            'address' => 'nullable|string',
            'phone_number' => 'required|string|max:20',
        ]);

        $tenant->update([
            'email' => $validated['email'],
            'name' => $validated['name'],
            'nik' => $validated['nik'] ?? null,
            'birth_place' => $validated['birth_place'] ?? null,
            'birth_date' => $validated['birth_date'] ?? null,
            'gender' => $validated['gender'] ?? null,
            'address' => $validated['address'] ?? null,
            'phone_number' => $validated['phone_number'],
        ]);

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
