<?php

namespace App\Http\Controllers\Management;

use App\Http\Controllers\Controller;
use App\Models\Bill;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class BillController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'tenant_id' => 'required|exists:tenants,id',
            'room_id' => 'required|exists:rooms,id',
            'total_price' => 'required|numeric|min:0',
            'start_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:start_date',
        ]);

        $validated['status'] = 'unpaid';

        Bill::create($validated);

        return back()->with('success', 'Bill berhasil dibuat.');
    }

    public function updateStatus(Request $request, Bill $bill): RedirectResponse
    {
        $validated = $request->validate([
            'status' => 'required|in:paid,unpaid,cancelled',
        ]);

        $bill->update($validated);

        return back()->with('success', 'Status bill berhasil diperbarui.');
    }
}
