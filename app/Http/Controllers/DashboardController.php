<?php

namespace App\Http\Controllers;

use App\Models\Bill;
use App\Models\Kost;
use App\Models\Room;
use App\Models\RoomCategory;
use App\Models\Tenant;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        $today = Carbon::today();

        // ── Master Data ─────────────────────────────────────────
        $totalKosts          = Kost::count();
        $totalRoomCategories = RoomCategory::count();
        $totalRooms          = Room::count();

        // ── Room Status ─────────────────────────────────────────
        $availableRooms   = Room::where('status', 'available')->count();
        $occupiedRooms    = Room::where('status', 'occupied')->count();
        $maintenanceRooms = Room::where('status', 'maintenance')->count();
        $occupancyRate    = $totalRooms > 0
            ? round(($occupiedRooms / $totalRooms) * 100, 1)
            : 0;

        // ── Tenants ─────────────────────────────────────────────
        $totalTenants = Tenant::count();
        $totalUsers   = User::count();

        // ── Bills & Revenue ─────────────────────────────────────
        $unpaidBills       = Bill::where('status', 'unpaid')->count();
        $paidBills         = Bill::whereIn('status', ['paid', 'down_payment', 'finished_payment'])->count();
        $pendingSignatures = Bill::whereIn('status', ['paid', 'down_payment', 'finished_payment'])
            ->whereNull('signature')
            ->whereDate('start_date', '<=', $today)
            ->count();
        $refundRequests    = Bill::where('status', 'refund_request')->count();

        $totalRevenue   = (float) Bill::whereIn('status', ['paid', 'finished_payment'])->sum('total_price') + (float) Bill::where('status', 'down_payment')->sum('dp_amount');
        $pendingRevenue = (float) Bill::where('status', 'unpaid')->sum('total_price');

        // ── Transactions ────────────────────────────────────────
        $totalTransactions = Transaction::count();

        // ── Recent Bills (last 6) ──────────────────────────────
        $recentBills = Bill::with(['tenant', 'room.roomCategory.kost'])
            ->latest()
            ->limit(6)
            ->get()
            ->map(fn (Bill $bill) => [
                'id'          => $bill->id,
                'tenant_name' => $bill->tenant?->name ?? '-',
                'room_number' => $bill->room?->room_number ?? '-',
                'kost_name'   => $bill->room?->roomCategory?->kost?->name ?? '-',
                'total_price' => (float) $bill->total_price,
                'status'      => $bill->status,
                'due_date'    => $bill->due_date?->format('d M Y'),
                'created_at'  => $bill->created_at?->format('d M Y'),
            ]);

        return Inertia::render('Dashboard', [
            'stats' => [
                'total_kosts'           => $totalKosts,
                'total_room_categories' => $totalRoomCategories,
                'total_rooms'           => $totalRooms,
                'available_rooms'       => $availableRooms,
                'occupied_rooms'        => $occupiedRooms,
                'maintenance_rooms'     => $maintenanceRooms,
                'occupancy_rate'        => $occupancyRate,
                'total_tenants'         => $totalTenants,
                'total_users'           => $totalUsers,
                'unpaid_bills'          => $unpaidBills,
                'paid_bills'            => $paidBills,
                'pending_signatures'    => $pendingSignatures,
                'refund_requests'       => $refundRequests,
                'total_revenue'         => $totalRevenue,
                'pending_revenue'       => $pendingRevenue,
                'total_transactions'    => $totalTransactions,
            ],
            'recentBills' => $recentBills,
        ]);
    }
}
