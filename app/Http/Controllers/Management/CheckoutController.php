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

        // Find tenants that have active rooms
        $tenants = Tenant::with(['rooms.roomCategory.kost', 'bills' => function($q) {
                // Get bills that are relevant to current occupancies
                $q->whereIn('status', ['paid', 'unpaid']);
            }])
            ->whereHas('rooms')
            ->when($search, function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhereHas('rooms', function ($q2) use ($search) {
                      $q2->where('room_number', 'like', "%{$search}%");
                  });
            })
            ->latest()
            ->paginate(15)
            ->through(function (Tenant $tenant) {
                $occupancies = [];
                
                foreach ($tenant->rooms as $room) {
                    // Find the most relevant bill for this room
                    $bill = $tenant->bills->where('room_id', $room->id)->sortByDesc('created_at')->first();
                    
                    $occupancies[] = [
                        'tenant_id' => $tenant->id,
                        'tenant_name' => $tenant->name,
                        'room_id' => $room->id,
                        'room_number' => $room->room_number,
                        'kost_name' => $room->roomCategory->kost->name,
                        'start_date' => $bill ? $bill->start_date->format('Y-m-d') : null,
                        'due_date' => $bill ? $bill->due_date->format('Y-m-d') : null,
                    ];
                }

                return $occupancies;
            });

        // Flatten the paginated data
        $items = collect($tenants->items())->flatten(1);
        
        // Reconstruct the paginator with flattened items
        $paginator = new \Illuminate\Pagination\LengthAwarePaginator(
            $items,
            $tenants->total(),
            $tenants->perPage(),
            $tenants->currentPage(),
            ['path' => \Illuminate\Pagination\Paginator::resolveCurrentPath()]
        );

        return Inertia::render('Management/Checkouts/Index', [
            'occupancies' => $paginator,
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
            ->whereIn('status', ['paid', 'unpaid'])
            ->latest()
            ->first();

        if ($bill) {
            $bill->update(['status' => 'checked_out']);
        }

        return back()->with('success', 'Berhasil melakukan Check Out untuk kamar ' . $room->room_number);
    }
}
