<?php

namespace App\Http\Controllers\Management;

use App\Http\Controllers\Controller;
use App\Models\Bill;
use App\Models\Room;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CheckoutController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search', '');

        // Query active bills directly — more reliable than querying through tenant_rooms pivot,
        // and correctly captures bookings from both management and the landing page.
        $activeStatuses = ['unpaid', 'paid', 'down_payment', 'finished_payment'];

        $bills = Bill::with(['tenant', 'room.roomCategory.kost'])
            ->whereIn('status', $activeStatuses)
            ->whereHas('tenant')
            ->whereHas('room')
            ->when($search, function ($q) use ($search) {
                $q->whereHas('tenant', fn ($q2) => $q2->where('name', 'like', "%{$search}%"))
                  ->orWhereHas('room', fn ($q2) => $q2->where('room_number', 'like', "%{$search}%"));
            })
            ->latest()
            ->paginate(15)
            ->through(fn (Bill $bill) => [
                'tenant_id'   => $bill->tenant->id,
                'tenant_name' => $bill->tenant->name,
                'room_id'     => $bill->room->id,
                'room_number' => $bill->room->room_number,
                'kost_name'   => $bill->room->roomCategory->kost->name,
                'start_date'  => $bill->start_date->format('Y-m-d'),
                'due_date'    => $bill->due_date->format('Y-m-d'),
            ]);

        return Inertia::render('Management/Checkouts/Index', [
            'occupancies' => $bills,
            'filters' => ['search' => $search],
        ]);
    }

    public function process(Tenant $tenant, Room $room)
    {
        // Detach the room from tenant
        $tenant->rooms()->detach($room->id);

        // Update room status
        $room->update(['status' => 'available']);

        // Update bill status to checked_out
        $bill = Bill::where('tenant_id', $tenant->id)
            ->where('room_id', $room->id)
            ->whereIn('status', ['unpaid', 'paid', 'down_payment', 'finished_payment'])
            ->latest()
            ->first();

        if ($bill) {
            $bill->update(['status' => 'checked_out']);
        }

        return back()->with('success', 'Berhasil melakukan Check Out untuk kamar ' . $room->room_number);
    }
}
