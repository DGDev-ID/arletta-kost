<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\Kost;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class KostController extends Controller
{
    public function index(Request $request): Response
    {
        $search = $request->input('search', '');

        $kosts = Kost::with('owner')
            ->when($search, fn ($q) => $q->where(function ($q2) use ($search) {
                $q2->where('name', 'like', "%{$search}%")
                    ->orWhere('address', 'like', "%{$search}%")
                    ->orWhereHas('owner', fn ($q3) => $q3->where('name', 'like', "%{$search}%"));
            }))
            ->latest()
            ->paginate(15)
            ->through(fn (Kost $kost) => [
                'id' => $kost->id,
                'name' => $kost->name,
                'address' => $kost->address,
                'address_coordinate' => $kost->address_coordinate,
                'description' => $kost->description,
                'owner_name' => $kost->owner->name,
                'owner_id' => $kost->owner_id,
            ]);

        return Inertia::render('Master/Kost/Index', [
            'kosts' => $kosts,
            'filters' => ['search' => $search],
        ]);
    }

    public function create(): Response
    {
        $owners = User::whereHas('roles', fn ($q) => $q->where('name', 'owner'))
            ->select('id', 'name')
            ->get();

        return Inertia::render('Master/Kost/Form', [
            'owners' => $owners,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'owner_id' => 'required|exists:users,id',
            'name' => 'required|string|max:255',
            'address' => 'required|string',
            'address_coordinate' => 'nullable|string|max:255',
            'description' => 'nullable|string',
        ]);

        Kost::create($validated);

        return to_route('master.kosts.index')->with('success', 'Kost berhasil ditambahkan.');
    }

    public function edit(Kost $kost): Response
    {
        $owners = User::whereHas('roles', fn ($q) => $q->where('name', 'owner'))
            ->select('id', 'name')
            ->get();

        return Inertia::render('Master/Kost/Form', [
            'kost' => $kost->only('id', 'owner_id', 'name', 'address', 'address_coordinate', 'description'),
            'owners' => $owners,
        ]);
    }

    public function update(Request $request, Kost $kost): RedirectResponse
    {
        $validated = $request->validate([
            'owner_id' => 'required|exists:users,id',
            'name' => 'required|string|max:255',
            'address' => 'required|string',
            'address_coordinate' => 'nullable|string|max:255',
            'description' => 'nullable|string',
        ]);

        $kost->update($validated);

        return to_route('master.kosts.index')->with('success', 'Kost berhasil diperbarui.');
    }

    public function destroy(Kost $kost): RedirectResponse
    {
        $kost->delete();

        return to_route('master.kosts.index')->with('success', 'Kost berhasil dihapus.');
    }
}
